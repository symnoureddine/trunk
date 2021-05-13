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
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeHumanName;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeNarrative;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * FIHR patient resource
 */
class CFHIRResourcePractitioner extends CFHIRResource
{
    /** @var string Resource type */
    public const RESOURCE_TYPE = "Practitioner";

    /** @var string[] */
    public const PROFILE = ["http://interopsante.org/fhir/structuredefinition/resource/fr-practitioner"];

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeNarrative */
    public $text;

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CFHIRDataTypeBoolean */
    public $active;

    /** @var CFHIRDataTypeHumanName[] */
    public $name;

    /** @var CFHIRDataTypeContactPoint[] */
    public $telecom;

    /** @var CFHIRDataTypeAddress[] */
    public $address;

    /** @var CFHIRDataTypeCode */
    public $gender;

    /** @var CFHIRDataTypeDate */
    public $birthDate;

    /** @var CFHIRDataTypeAttachment[] */
    public $photo;

    /** @var CFHIRDataTypeBackBoneElement[] */
    public $qualification;

    /** @var CFHIRDataTypeCodeableConcept[] */
    public $communication;

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
     * @param CMediusers $mediuser
     */
    public function mapFrom(CMbObject $mediuser): void
    {
        parent::mapFrom($mediuser);

        // ids
        $this->setIdentifiers($mediuser);

        // active
        $this->active = new CFHIRDataTypeBoolean($mediuser->actif);

        // telecom
        $this->setTelecom($mediuser);

        // address
        if ($mediuser->_user_adresse || $mediuser->_user_ville || $mediuser->_user_cp) {
            $this->address[] = CFHIRDataTypeAddress::build(
                [
                    "use"        => "work",
                    "type"       => "postal",
                    "line"       => preg_split('/[\r\n]+/', $mediuser->_user_adresse),
                    "city"       => $mediuser->_user_ville,
                    "postalCode" => $mediuser->_user_cp,
                ]
            );
        }

        // gender
        $this->gender = new CFHIRDataTypeCode($this->formatGender($mediuser->_user_sexe));
    }

    /**
     * @param CMbObject $mediuser
     *
     * @return void
     */
    public function mapFromLight(CMbObject $mediuser): void
    {
        parent::mapFromLight($mediuser);
        /** @var CMediusers $mediuser */
        $this->name = $this->addName(
            $mediuser->_user_last_name,
            $mediuser->_user_first_name,
            'usual',
            $mediuser->_view
        );
    }

    /**
     * @param CMediusers $mediuser
     *
     * @return void
     */
    private function setIdentifiers(CMediusers $mediuser): void
    {
        // id rpps
        if ($mediuser->rpps) {
            $codeableConcepts = $this->addCodeableConcepts(
                $this->addCoding('http://interopsante.org/CodeSystem/v2-0203', 'RPPS', 'N&#176; RPPS')
            );
            $this->identifier = $this->addIdentifier(
                $mediuser->rpps,
                $this->first($codeableConcepts),
                'official',
                'urn:oid:1.2.250.1.71.4.2.1'
            );
        }

        // id adeli
        if ($mediuser->adeli) {
            $codeableConcepts  = $this->addCodeableConcepts(
                $this->addCoding('http://interopsante.org/CodeSystem/v2-0203', 'ADELI', 'N&#176; ADELI')
            );
            $this->identifier = $this->addIdentifier(
                $mediuser->adeli,
                $this->first($codeableConcepts),
                'official',
                'urn:oid:1.2.250.1.71.4.2.1'
            );
        }
    }

    /**
     * @param CMediusers $mediuser
     *
     * @return void
     */
    private function setTelecom(CMediusers $mediuser): void
    {
        if ($mediuser->_user_phone) {
            $this->telecom[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "phone",
                    "value"  => $mediuser->_user_phone,
                ]
            );
        }

        // telecom
        if ($mediuser->_user_email) {
            $this->telecom[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "email",
                    "value"  => $mediuser->_user_email,
                ]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CMediusers) {
            throw new CFHIRException("Object is not an practitioner");
        }

        $this->mapFrom($object);
    }
}
