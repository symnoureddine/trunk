<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\SvnClient;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class DOM extends DOMDocument {
  /**
   * @param             $query
   * @param DOMElement $node
   *
   * @return DOMNodeList|DOMElement[]
   */
  function xpath($query, DOMElement $node = null) {
    $xpath = new DOMXPath($this);
    return $xpath->query($query, $node);
  }

  /**
   * Parse an XML document to DOM
   *
   * @param string $xml CML content
   *
   * @return DOM
   */
  static function parse($xml) {
    $dom = new self;
    $dom->loadXML($xml);
    return $dom;
  }
}