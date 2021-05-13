<?php
/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Request;
use DOMDocument;
use Ox\Interop\Fhir\CFHIR;

/**
 * FHIR JSON response
 */
class CFHIRRequestXML extends CFHIRRequest {
  /**
   * @inheritdoc
   */
  protected function _output() {
    $res = $this->resource;

    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->formatOutput  = true;
    $res->toXML($dom);

    $debug = false;
    if ($debug) {
      $dom->formatOutput = true;

      dump($_GET);
      dump($dom->saveXML());
      return $dom->saveXML();
    }
    else {
      ob_clean();
      header("Content-Type: ". CFHIR::CONTENT_TYPE_XML);
      return $dom->saveXML();
    }
  }
}