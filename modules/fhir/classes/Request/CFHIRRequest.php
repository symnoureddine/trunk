<?php
/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Request;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;

/**
 * FHIR generic resource
 */
class CFHIRRequest implements IShortNameAutoloadable
{
    const NS = "http://hl7.org/fhir";

    /**
     * @var string HTTP code to output
     */

    /** @var CFHIRResource */
    protected $resource;

    function __construct(CFHIRResource $resource)
    {
        $this->resource = $resource;
    }

    public function output($format)
    {
        if (!in_array(strtolower($format), ["xml", "json"], true)) {
            if (!preg_match("@application/fhir\+(\w+)@", $format, $matches)) {
                throw new CFHIRException("Unsupported format '$format'");
            }

            $format = $matches[1];
        }

        $class = "CFHIRRequest" . strtoupper($format);

        if (!class_exists($class)) {
            throw new CFHIRException("Unsupported format '$format'");
        }

        /** @var self $output */
        $output = new $class($this->resource);

        return $output->_output();
    }

    /**
     * Output method implemented in child classes
     *
     * @return string
     */
    protected function _output()
    {
    }
}

