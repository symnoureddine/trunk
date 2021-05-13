<?php

/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Response;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * FHIR generic resource
 */
class CFHIRResponse implements IShortNameAutoloadable
{
    /** @var string */
    public const NS = "http://hl7.org/fhir";

    /** @var string HTTP code to output */
    public const HTTP_CODE = 200;

    /** @var int  */
    public const SEARCH_MAX_ITEMS = 10;

    /** @var array  */
    public static $headers = [];

    /** @var CFHIRResource */
    protected $resource;

    /**
     * CFHIRResponse constructor.
     *
     * @param CFHIRResource $resource
     */
    public function __construct(CFHIRResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * FHIR response output
     *
     * @param string $format Format
     *
     * @return Response
     *
     * @throws CFHIRException
     */
    public function output($format)
    {
        if (!in_array(strtolower($format), ["xml", "json"], true)) {
            if (!preg_match("@application/fhir\+(\w+)@", $format, $matches)) {
                throw new CFHIRException("Unsupported format '$format'");
            }

            $format = $matches[1];
        }

        $class = "CFHIRResponse" . strtoupper($format);

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
     * @return Response
     */
    protected function _output()
    {
    }
}
