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
use Ox\Interop\Fhir\Resources\CFHIRResourceBundle;
use Ox\Interop\Fhir\Resources\CFHIRResourceConceptMap;

/**
 * Description
 */
class CFHIREventConceptMap extends CFHIREvent
{
    /**
     * Construct
     *
     * @return void
     */
    function __construct()
    {
        parent::__construct();

        $this->code = "iti-65";
        $this->type = "transaction";
    }

    /**
     * Build event
     *
     * @param CMbObject $object Object
     *
     * @return CFHIRResourceConceptMap
     * @throws CFHIRException
     * @see parent::build()
     *
     */
    public function build(CMbObject $object): CFHIRResource
    {
        // Construction du ConceptMap
        $concept_map = new CFHIRResourceConceptMap();
        $concept_map->build($object, $this);

        return $concept_map;
    }
}
