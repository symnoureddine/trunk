<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use DOMElement;
use Ox\Core\CMbArray;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataType;
use Ox\Interop\Fhir\Resources\CFHIRResource;

/**
 * FHIR data type
 */
class CFHIRDataTypeComplex extends CFHIRDataType {
  /**
   * Builds a component from data
   * 
   * @param array $data data 
   *
   * @return static
   */
  static function build(array $data) {
    $object = new static;

    foreach ($data as $_key => $_value) {
      if ($_value === null) {
        continue;
      }

      if (is_array($_value)) {
        CMbArray::removeValue(null, $_value, true);
        CMbArray::removeValue("", $_value, true);

        if (count($_value) === 0) {
          continue;
        }

        foreach ($_value as $_value_key => $_type) {
          if ($_type instanceof CFHIRDataTypeComplex) {
            self::build($_value);
          }
          elseif (is_string($_type)) {
            $_value[$_value_key] = utf8_encode($_type);
          }
        }
      }
      elseif (is_string($_value)) {
        $_value = utf8_encode($_value);
      }

      $object->$_key = $_value;
    }

    return $object;
  }

  /**
   * @return array
   */
  function jsonSerialize() {
    return CFHIR::filterData($this);
  }

  /**
   * @inheritdoc
   */
  function toXML(DOMElement $DOMElement) {
    foreach ($this as $_name => $_element) {
      if ($_element === null) {
        continue;
      }
      /*if ($_name === "value" && $_element instanceof CFHIRDataType) {
        $DOMElement->setAttribute("value", $_element->getValue());
      } else {*/

      CFHIRResource::appendToDOM($_name, $_element, $DOMElement);
    }
  }
}
