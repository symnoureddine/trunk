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
class CFHIRDataTypeInteger extends CFHIRDataType {
  /**
   * @inheritdoc
   */
  public function toJSON() {
    return (int)$this->_value;
  }

  /**
   * @inheritdoc
   */
  public function toXML(DOMElement $DOMElement) {
    return (int)$this->_value;
  }

  /**
   * @inheritdoc
   */
  public function fromJSON($value) {
    $this->_value = (int)$value;
  }

  /**
   * @inheritdoc
   */
  public function fromXML($value) {
    $this->_value = (int)$value;
  }
}
