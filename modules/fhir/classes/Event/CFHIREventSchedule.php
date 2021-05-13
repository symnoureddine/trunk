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
use Ox\Mediboard\Cabinet\CPlageconsult;

/**
 * Class CFHIREventSchedule
 *
 * @package Ox\Interop\Fhir\Event
 */
class CFHIREventSchedule extends CFHIREvent
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
     * @see parent::build()
     *
     */
    public function build(CMbObject $object): CFHIRResource
    {
        // Construction du Schedule
        $schedule = new CFHIRResourceSchedule();
        $schedule->build($object, $this);

        return $schedule;
    }
}
