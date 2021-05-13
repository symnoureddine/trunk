<?php

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDate;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDateTime;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypePositiveInt;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUnsignedInt;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContained;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeExtension;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypePeriod;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Description
 */
class CFHIRResourceAppointment extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = 'Appointment';

    /** @var string[] */
    public const PROFILE = ["http://interopsante.org/fhir/structuredefinition/resource/fr-appointment"];

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeContained[] */
    public $contained = [];

    /** @var CFHIRDataTypeExtension */
    public $extension;
    /** @var CFHIRDataTypeIdentifier */
    public $identifier;

    /** @var CFHIRDataTypeCode */
    public $status;

    /** @var CFHIRDataTypeCodeableConcept */
    public $serviceCategory;

    /** @var CFHIRDataTypeCodeableConcept */
    public $serviceType;

    /** @var CFHIRDataTypeCodeableConcept */
    public $specialty;

    /** @var CFHIRDataTypeCodeableConcept */
    public $appointmentType;

    /** @var CFHIRDataTypeCodeableConcept */
    public $reason;

    /** @var CFHIRDataTypeReference */
    public $indication;

    /** @var CFHIRDataTypeUnsignedInt */
    public $priority;

    /** @var CFHIRDataTypeString */
    public $description;

    /** @var CFHIRDataTypeReference */
    public $supportingInformation;

    /** @var CFHIRDataTypeInstant */
    public $start;

    /** @var CFHIRDataTypeInstant */
    public $end;

    /** @var CFHIRDataTypePositiveInt */
    public $minutesDuration;

    /** @var CFHIRDataTypeReference */
    public $slot;

    /** @var CFHIRDataTypeDateTime */
    public $created;

    /** @var CFHIRDataTypeString */
    public $comment;

    /** @var CFHIRDataTypeReference */
    public $incomingReferral;

    /** @var CFHIRDataTypeBackboneElement */
    public $participant;

    /** @var CFHIRDataTypePeriod */
    public $requestedPeriod;

    /**
     * @inheritdoc
     */
    public function getClass(): ?string
    {
        return CConsultation::class;
    }

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        parent::mapFrom($object);

        /** @var CConsultation $consultation */
        $consultation = $object;

        $this->identifier[] = CFHIRDataTypeIdentifier::build(
            [
                "system" => CAppUI::conf('base_url'),
                "value"  => $consultation->_id,
            ]
        );

        $this->extension = $this->addExtensions(
            [
                $this->formatExtension(
                    'fr-appointment-operator',
                    [
                        'valueReference' => CFHIRDataTypeReference::build(
                            [
                                "reference" => "Practitioner/",
                            ]
                        ),
                    ]
                ),
            ]
        );

        $this->status = $this->setStatus($consultation);

        $praticien = $consultation->loadRefPraticien();

        $this->participant[] = $this->setParticipants($consultation);

        $this->specialty = $this->setSpecialty($praticien);

        $this->description = new CFHIRDataTypeString($consultation->motif);

        $this->start = new CFHIRDataTypeInstant($consultation->_datetime);

        $this->end = new CFHIRDataTypeInstant($consultation->_date_fin);

        $this->minutesDuration = new CFHIRDataTypePositiveInt($consultation->_duree);

        $this->created = new CFHIRDataTypeDate($consultation->_date);

        $this->comment = new CFHIRDataTypeString($consultation->rques);

        $this->requestedPeriod = CFHIRDataTypePeriod::build(
            $this->formatPeriod(
                CFHIR::getTimeUtc($consultation->_datetime, false),
                CFHIR::getTimeUtc($consultation->_date_fin, false)
            )
        );
    }

    /**
     * Set status for resource
     *
     * @param CConsultation $object
     *
     * @return string|null
     */
    public function setStatus(CConsultation $object): ?string
    {
        if ($object->annule) {
            if ($object->motif_annulation && $object->motif_annulation == 'not_arrived') {
                return 'noshow';
            } else {
                return 'cancelled';
            }
        }

        switch ($object->chrono) {
            case CConsultation::DEMANDE:
                return 'proposed';
            case CConsultation::PLANIFIE:
                return 'booked';
            case CConsultation::PATIENT_ARRIVE:
            case CConsultation::EN_COURS:
                return 'arrived';
            case CConsultation::TERMINE:
                return 'fulfilled';
            default:
        }
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CConsultation) {
            throw  new CFHIRException("Object is not an appointment");
        }

        $this->identifier[] = CFHIRDataTypeIdentifier::build(
            [
                "system" => CAppUI::conf('base_url'),
                "value"  => $object->_id,
            ]
        );

        $this->description = $object->motif;

        $this->status = $this->setStatus($object);

        if ($object->categorie_id) {
            $this->serviceType = $this->setServiceType($object);
        }

        $this->start = CFHIR::getTimeUtc($object->_datetime, false);

        $this->end = CFHIR::getTimeUtc($object->_date_fin, false);

        $this->minutesDuration = $object->_duree;

        $this->created = $object->_date;

        $this->comment = $object->rques;

        $plage_consult = $object->loadRefPlageConsult();

        $slot_id   = $plage_consult->_guid . "-" . $plage_consult->getSlotId($object->_datetime); // getSlotID
        $idex_slot = CIdSante400::getMatch('CPlageConsult', $this->_receiver->_tag_fhir, $slot_id);

        $this->slot[] = CFHIRDataTypeReference::build(
            [
                "reference" => $idex_slot->_id ? "Slot/$idex_slot->id400" : "#" . $slot_id,
            ]
        );
        if (!$idex_slot->_id) {
            $this->contained = $this->addContained($plage_consult, new CFHIRResourceSlot());
        }

        $praticien = $object->loadRefPraticien();
        if ($praticien && $praticien->_id) {
            $role = $praticien->loadRefFunction();
        }
        $patient   = $object->loadRefPatient();

        $this->participant = $this->setParticipants($object, true);

        // Ajout local des ressources si besoin
        // todo attention les _tag_fhir n'existe pas sur un senderHttp lorsqu'on fait en local
        if (!CIdSante400::getMatchFor($praticien, $this->_receiver->_tag_fhir)->_id) {
            $this->contained = $this->addContained($praticien, new CFHIRResourcePractitioner());
        }
        if (!CIdSante400::getMatchFor($patient, $this->_receiver->_tag_fhir)->_id) {
            $this->contained = $this->addContained($patient, new CFHIRResourcePatient());
        }
        if (isset($role) && $role->_id && !CIdSante400::getMatchFor($role, $this->_receiver->_tag_fhir)->_id) {
            $this->contained = $this->addContained($role, new CFHIRResourcePractitionerRole());
        }

        $this->specialty = $this->setSpecialty($object->loadRefPraticien());

        $this->requestedPeriod[] = CFHIRDataTypePeriod::build(
            $this->formatPeriod(
                CFHIR::getTimeUtc($object->_datetime, false),
                CFHIR::getTimeUtc($object->_date_fin, false)
            )
        );
    }
}
