<?php

/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Eai\CInteropActor;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBoolean;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDate;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAddress;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAttachment;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContactPoint;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContained;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeHumanName;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeNarrative;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypePeriod;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * FIHR patient resource
 */
class CFHIRResourcePractitionerRole extends CFHIRResource
{
    /** @var string Resource type */
    public const RESOURCE_TYPE = "PractitionerRole";

    /** @var string[] */
    public const PROFILE = [
        'http://interopsante.org/fhir/structuredefinition/resource/fr-practitioner-role-profession',
    ];

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeContained[] */
    public $contained = [];

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CFHIRDataTypeBoolean */
    public $active;

    /** @var CFHIRDataTypePeriod */
    public $period;

    /** @var CFHIRDataTypePeriod */
    public $practitioner;

    /** @var CFHIRDataTypePeriod */
    public $organization;

    /** @var CFHIRDataTypeCodeableConcept[] */
    public $code;

    /** @var CFHIRDataTypeCodeableConcept[] */
    public $specialty;

    /**
     * return CMediusers
     */
    public function getClass(): ?string
    {
        return CMediusers::class;
    }

    /**
     * @inheritdoc
     *
     * @param CMediusers $object
     */
    public function mapFrom(CMbObject $object): void
    {
        $mediuser = $object;
        $function = $mediuser->loadRefFunction();

        /** @var CInteropActor $actor */
        $actor = $this->_receiver ? $this->_receiver : $this->_sender;

        parent::mapFrom($mediuser);

        $this->active = new CFHIRDataTypeBoolean($mediuser->actif);

        $this->period = CFHIRDataTypePeriod::build(
            $this->formatPeriod($mediuser->deb_activite, $mediuser->fin_activite)
        );

        $idex_prat = CIdSante400::getMatchFor($mediuser, $actor->_tag_fhir);
        $this->practitioner = CFHIRDataTypeReference::build(
            [
                "reference" => $idex_prat->_id ? "Practitioner/$idex_prat->id400" : "#" . $mediuser->_guid,
            ]
        );
        if (!CIdSante400::getMatchFor($mediuser, $actor->_tag_fhir)->_id) {
            $this->contained = $this->addContained($mediuser, new CFHIRResourcePractitioner());
        }

        $group = $mediuser->loadRefFunction()->loadRefGroup();
        $idex_group = CIdSante400::getMatchFor($praticien, $actor->_tag_fhir);
        $this->organization = CFHIRDataTypeReference::build(
            [
                "reference" => $idex_group->_id ? "Organization/$idex_group->id400" : "#" . $group->_guid,
            ]
        );
        if (!CIdSante400::getMatchFor($group, $actor->_tag_fhir)->_id) {
            $this->contained = $this->addContained($group, new CFHIRResourcePractitioner());
        }

        $this->specialty[] = $this->setSpecialty($object->loadRefPraticien());
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
    }
}
