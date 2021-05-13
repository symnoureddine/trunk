<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Response;

use DOMDocument;
use Ox\Interop\Fhir\CFHIR;
use Symfony\Component\HttpFoundation\Response;

/**
 * FHIR JSON response
 */
class CFHIRResponseXML extends CFHIRResponse {
  /**
   * @inheritdoc
   * @return Response
   */
  protected function _output() {
    $res = $this->resource;

    $dom               = new DOMDocument("1.0", "UTF-8");
    $dom->formatOutput = true;
    $res->toXML($dom);

    $response = new Response();
    $response->headers->set("content-type", CFHIR::CONTENT_TYPE_XML);
    $response->setStatusCode($this::HTTP_CODE);

    foreach ($this::$headers as $_header_key => $_header_value) {
      $response->headers->set($_header_key, $_header_value);
    }

    //$response->headers->set("HTTP/1.0", \CFHIRResponse::$http_code);

    $xml = $dom->saveXML();

    // TODO : Dans le cas d'un 201 created, on ne met pas de contenu pour le moment.
    // TODO  Dans l'idéal, il faudrait faire un read en interne de notre ressource et retourner le résultat
    if ($this::HTTP_CODE != 201) {
      $response->setContent($xml);
    }

    return $response;
  }
}
