<?php

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBoolean;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Patients\CPatient;

/**
 * Class CFHIRResourceSlot
 * @package Ox\Interop\Fhir\Resources
 */
class CFHIRResourceSlot extends CFHIRResource
{
    /** @var string  */
    public const RESOURCE_TYPE = "Slot";

    /** @var string[]  */
    public const PROFILE = ["http://www.interopsante.org/fhir/structuredefinition/resource/fr-slot"];

    /** @var string  */
    public const STATUS_BUSY = 'busy';

    /** @var string  */
    public const STATUS_FREE = 'free';

    /** @var int */
    private $slot_id;

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeReference */
    public $schedule;

    /** @var CFHIRDataTypeCode */
    public $status;

    /** @var CFHIRDataTypeInstant */
    public $start;

    /** @var CFHIRDataTypeInstant */
    public $end;

    /** @var CFHIRDataTypeBoolean */
    public $overbooked;

    /**
     * @return string
     */
    public function getClass(): ?string
    {
        return CPlageConsult::class;
    }

    /**
     * Perform a read query based on the current object data
     *
     * @param mixed $data Data to handle
     *
     * @return CStoredObject
     * @throws CFHIRExceptionNotFound
     */
    public function interactionRead(?array $data): CStoredObject
    {
        $_id         = CMbArray::get($data, "_id");
        $resource_id = $_id[0][1];
        if (!$resource_id || !count($resource_id)) {
            throw new CFHIRExceptionNotFound();
        }

        // special id
        [$plage_consult_id, $slot_id] = explode('-', $resource_id);

        $this->slot_id     = $slot_id;
        $data['_id'][0][1] = $plage_consult_id;

        return parent::interactionRead($data);
    }

    /**
     * @param CMbObject $object
     */
    public function mapFrom(CMbObject $object): void
    {
        if (!$this->slot_id) {
            return;
        }

        /** @var CPlageconsult $object */
        parent::mapFrom($object);

        $consultations = $object->loadRefsConsultations();
        $overbooked = count($consultations) > 1 ?: false;

        // overbooked
        $this->overbooked = new CFHIRDataTypeBoolean($overbooked);
    }

    /**
     * @param CMbObject $object
     */
    public function mapFromLight(CMbObject $object): void
    {
        parent::mapFromLight($object);
        /** @var CPlageconsult $object */
        $offset_minutes = $object->_freq_minutes * $this->slot_id;
        $start          = CMbDT::dateTime("+$offset_minutes MINUTES", "$object->date $object->debut");
        $end            = CMbDT::dateTime("+$object->_freq_minutes MINUTES", $start);

        $this->id = new CFHIRDataTypeId($object->_guid . "-" . $object->getSlotId($start));

        $keys_slot_empty = array_keys($object->getEmptySlots());
        $status          = isset($keys_slot_empty[$this->slot_id - 1]) ? self::STATUS_FREE : self::STATUS_BUSY;

        // Schedule
        $this->schedule = CFHIRDataTypeReference::build(
            ['reference' => "Schedule/$object->_id"]
        );

        // start
        $this->start = new CFHIRDataTypeInstant($start);

        // end
        $this->end = new CFHIRDataTypeInstant($end);

        // status
        $this->status = new CFHIRDataTypeCode($status);
    }

    /**
     * @param CMbObject $object
     *
     * @throws CFHIRException
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CPatient) {
            throw new CFHIRException("Object is not an slot");
        }

        $this->mapFrom($object);
    }

    /**
     * @param int $slot_id
     */
    public function setSlotId(int $slot_id): void
    {
        $this->slot_id = $slot_id;
    }
}
