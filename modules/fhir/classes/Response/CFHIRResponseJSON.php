<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Response;

use DOMDocument;
use DOMElement;
use DOMNode;
use Ox\Core\CMbArray;
use Ox\Interop\Fhir\CFHIR;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * FHIR JSON response
 */
class CFHIRResponseJSON extends CFHIRResponse {

  /**
   * @inheritdoc
   * @return Response
   */
  protected function _output() {
    $response = new Response();
    $response->headers->set("content-type", CFHIR::CONTENT_TYPE_JSON);
    $response->setStatusCode($this::HTTP_CODE);

    foreach ($this::$headers as $_header_key => $_header_value) {
      $response->headers->set($_header_key, $_header_value);
    }

    // todo rajouter le http code
    //$response->headers->set("HTTP/1.0", \CFHIRResponse::$http_code);
    $json = CMbArray::toJSON($this->resource, false);

    // TODO : Dans le cas d'un 201 created, on ne met pas de contenu pour le moment.
    // TODO  Dans l'idéal, il faudrait faire un read en interne de notre ressource et retourner le résultat
    if ($this::HTTP_CODE != 201) {
      $response->setContent($json);
    }
    return $response;
  }

  /**
   * Convert to XML
   * 
   * @param string $data JSON data to convert to XML
   *
   * @return DOMDocument
   */
  static function toXML($data) {
    $data = json_decode($data, false);
    
    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->formatOutput = true;
    
    self::handleElement($data, $dom);
    
    return $dom;
  }

  /**
   * Handles an element from the JSON object
   * 
   * @param stdClass|array|string $data Data 
   * @param DOMNode|DOMElement    $node Node to insert data into
   * @param string                $name Element name
   *                                  
   * @return void
   */
  static function handleElement($data, DOMNode $node, $name = null) {
    /** @var DOMDocument $doc */
    $doc = $node->ownerDocument ?: $node;
    
    if (is_object($data)) {
      if (isset($data->resourceType)) {
        if ($name) {
          $element = $doc->createElement($name);
          $node->appendChild($element);
          
          $node = $element;
        }
        
        $resource = $doc->createElementNS(CFHIRResponseXML::NS, $data->resourceType);
        $node->appendChild($resource);
        
        unset($data->resourceType);

        foreach ($data as $_name => $_data) {
          self::handleElement($_data, $resource, $_name);
        }
      }
      else {
        $value = $doc->createElement($name);
        $node->appendChild($value);

        foreach ($data as $_name => $_data) {
          self::handleElement($_data, $value, $_name);
        }
      }
    }
    elseif (is_array($data)) {
      foreach ($data as $_data) {
        self::handleElement($_data, $node, $name);
      }
    }
    else {
      if ($name && is_string($name)) {
        $value = $doc->createElement($name);
        $value->setAttribute("value", $data);
        $node->appendChild($value);
      }
    }
  }
}
