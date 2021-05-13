<?php

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Event;

use Ox\Core\CMbObject;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceAppointment;
use Ox\Interop\Fhir\Resources\CFHIRResourceSchedule;
use Ox\Interop\Fhir\Resources\CFHIRResourceSlot;

/**
 * Class CFHIREventSlot
 *
 * @package Ox\Interop\Fhir\Event
 */
class CFHIREventSlot extends CFHIREvent
{
    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->code = ""; //todo ?
        $this->type = "transaction";
    }

    /**
     * Build event
     *
     * @param CMbObject $object Object
     *
     * @return CFHIRResourceAppointment
     * @throws CFHIRException
     * @see parent::build()
     *
     */
    public function build(CMbObject $object): CFHIRResource
    {
        // Construction du Schedule
        $schedule = new CFHIRResourceSlot();
        $schedule->setSlotId(''); // todo find solution
        $schedule->build($object, $this);

        return $schedule;
    }
}
