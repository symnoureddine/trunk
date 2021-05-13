<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes;
use DOMElement;
use Ox\Core\CMbDT;

/**
 * FHIR data type
 */
class CFHIRDataTypeDate extends CFHIRDataType {
  /**
   * @inheritdoc
   */
  public function toJSON() {
    return CMbDT::date($this->_value);
  }

  /**
   * @inheritdoc
   */
  public function toXML(DOMElement $DOMElement) {
    return CMbDT::date($this->_value);
  }
}
