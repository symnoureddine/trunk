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
use Ox\Interop\Fhir\Resources\CFHIRResourcePatient;
use Ox\Interop\Fhir\Resources\CFHIRResourcePractitioner;

/**
 * Description
 */
class CFHIREventPatient extends CFHIREvent
{
    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->code = "patient";
        $this->type = "transaction";
    }

    /**
     * Build event
     *
     * @param CMbObject $object Object
     *
     * @return CFHIRResourcePatient
     * @throws CFHIRException
     * @see parent::build()
     *
     */
    public function build(CMbObject $object): CFHIRResource
    {
        // Construct patient resource
        $patient = new CFHIRResourcePatient();
        $patient->build($object, $this);

        return $patient;
    }
}
