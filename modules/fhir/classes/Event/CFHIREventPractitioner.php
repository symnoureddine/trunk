<?php

/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Event;

use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourcePractitioner;

/**
 * Description
 */
class CFHIREventPractitioner extends CFHIREvent
{
    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->code = "practitioner";
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
        // Construct practitioner resource
        $practitioner = new CFHIRResourcePractitioner();
        $practitioner->build($object, $this);

        return $practitioner;
    }
}
