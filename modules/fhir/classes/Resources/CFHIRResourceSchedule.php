<?php

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CMbObject;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBoolean;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDateTime;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContained;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeExtension;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Class CFHIRResourceSchedule
 */
class CFHIRResourceSchedule extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = 'Schedule';

    /** @var string[] */
    public const PROFILE = ["http://interopsante.org/fhir/structuredefinition/resource/fr-schedule"];

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeContained[] */
    public $contained = [];

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeExtension */
    public $extension;

    /** @var CFHIRDataTypeBoolean */
    public $active;

    /** @var CFHIRDataTypeCodeableConcept */
    public $serviceCategory;

    /** @var CFHIRDataTypeCodeableConcept */
    public $specialty;

    /** @var CFHIRDataTypeReference */
    public $actor;

    /**
     * return CPlageconsult
     */
    public function getClass(): ?string
    {
        return CPlageConsult::class;
    }

    /**
     * @param CMbObject $object
     *
     * @throws CFHIRExceptionNotFound
     */
    public function mapFrom(CMbObject $object): void
    {
        /** @var CPlageconsult $plage_consult */
        $plage_consult = $object;

        parent::mapFrom($plage_consult);

        // active
        $this->active = new CFHIRDataTypeBoolean(!$plage_consult->locked);

        // FrPractitionerRoleExercice && FrPractitionerRoleProfession && FrPractitioner
        $practitioner = $plage_consult->loadRefChir();
        $this->actor  = [
            CFHIRDataTypeReference::build(
                ['reference' => $this->getIdentifier(CFHIRResourcePractitioner::class, $practitioner)]
            ),
        ];

        // speciality
        $practitioner = $plage_consult->loadRefChir();
        if ($practitioner && $practitioner->_id) {
            $this->specialty = $this->setSpecialty($practitioner);
        }

        // availabilityTime
        $this->addAvailabilityTime($plage_consult);
    }

    /**
     * @param CPlageconsult $plage_consult
     *
     * @return void
     */
    private function addAvailabilityTime(CPlageconsult $plage_consult): void
    {
        $this->extension = $this->addExtensions(
            [
                $this->formatExtension(
                    'http://interopsante.org/fhir/StructureDefinition/schedule/fr-availabilty-time',
                    [
                        'extension' => $this->addExtensions(
                            [
                                $this->formatExtension(
                                    'type',
                                    [
                                        'valueCoding' => $this->first(
                                            $this->addCoding(
                                                'http://interopsante.org/codesystem/schedule-type',
                                                $plage_consult->locked ? 'busy-unavailable' : 'free',
                                                $plage_consult->locked ? 'Indisponibilité' : 'Disponibilité'
                                            )
                                        ),
                                    ]
                                ),
                                $this->formatExtension(
                                    'start',
                                    [
                                        'valueDateTime' => new CFHIRDataTypeDateTime(
                                            "$plage_consult->date $plage_consult->debut"
                                        ),
                                    ]
                                ),
                                $this->formatExtension(
                                    'end',
                                    [
                                        'valueDateTime' => new CFHIRDataTypeDateTime(
                                            "$plage_consult->date $plage_consult->fin"
                                        ),
                                    ]
                                ),
                                $this->formatExtension(
                                    'identifier',
                                    [
                                        'valueIdentifier' => CFHIRDataTypeIdentifier::build(
                                            ['value' => $plage_consult->_id]
                                        ),
                                    ]
                                ),
                            ]
                        ),
                    ]
                ),
            ]
        );
    }

    /**
     * @param CMbObject $object
     *
     * @throws CFHIRException
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CPlageconsult) {
            throw new CFHIRException("Object is not an schedule");
        }

        /** @var CPlageconsult $plage_consult */
        $plage_consult = $object;

        // active
        $this->active = new CFHIRDataTypeBoolean(!$plage_consult->locked);

        // availabilityTime
        $this->addAvailabilityTime($plage_consult);

        // FrPractitionerRoleExercice && FrPractitionerRoleProfession && FrPractitioner
        $practitioner = $plage_consult->loadRefChir();

        if (!$practitioner || !$practitioner->_id) {
            return;
        }

        // speciality
        $this->specialty = $this->setSpecialty($practitioner);

        // actor
        $idex_chir   = CIdSante400::getMatchFor($practitioner, $this->_receiver->_tag_fhir);
        $this->actor = [
            CFHIRDataTypeReference::build(
                [
                    'reference' => $this->getIdentifier(CFHIRResourcePractitioner::class, $plage_consult, $idex_chir),
                ]
            ),
        ];

        // add contained for practitioner
        if (!$idex_chir->_id) {
            $this->contained = $this->addContained($practitioner, new CFHIRResourcePractitioner());
        }
    }
}
