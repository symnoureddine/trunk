<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes;
use DOMElement;

/**
 * FHIR data type
 */
class CFHIRDataTypeXhtml extends CFHIRDataTypeString {
  /**
   * @inheritdoc
   */
  public function toJSON() {
    return "<div xmlns='http://www.w3.org/1999/xhtml'>" . utf8_encode($this->_value) . "</div>";
  }

  /**
   * @inheritdoc
   */
  public function toXML(DOMElement $DOMElement) {
    return "<div xmlns='http://www.w3.org/1999/xhtml'>" . utf8_encode($this->_value) . "</div>";
  }
}
