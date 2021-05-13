<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes;
use DOMElement;
use JsonSerializable;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Interop\Fhir\CFHIR;

/**
 * FHIR data type
 */
class CFHIRDataType implements JsonSerializable,IShortNameAutoloadable {
  static $map = array(
    "base64Binary" => "CFHIRDataTypeBase64Binary",
    "boolean"      => "CFHIRDataTypeBoolean",
    "code"         => "CFHIRDataTypeCode",
    "date"         => "CFHIRDataTypeDate",
    "dateTime"     => "CFHIRDataTypeDateTime",
    "decimal"      => "CFHIRDataTypeDecimal",
    "instant"      => "CFHIRDataTypeInstant",
    "integer"      => "CFHIRDataTypeInteger",
    "positiveInt"  => "CFHIRDataTypePositiveInt",
    "string"       => "CFHIRDataTypeString",
    "unsignedInt"  => "CFHIRDataTypeUnsignedInt",
    "uri"          => "CFHIRDataTypeUri",
    "id"           => "CFHIRDataTypeId",
    "xhtml"        => "CFHIRDataTypeXhtml",

    "Address"      => "CFHIRDataTypeAddress",
    "Attachment"   => "CFHIRDataTypeAttachment",
    "ContactPoint" => "CFHIRDataTypeContactPoint",
    "HumanName"    => "CFHIRDataTypeHumanName",
    "Identifier"   => "CFHIRDataTypeIdentifier",
  );

  /** @var mixed */
  protected $_value;

  public function __construct($value = null) {
    $this->_value = $value;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->_value;
  }

  /**
   * Output to DOM XML
   *
   * @param DOMElement $DOMElement DOM element to append the data to
   *
   * @return mixed|null
   */
  public function toXML(DOMElement $DOMElement) {
    return $this->_value;
  }

  public function fromJSON($value) {
    $this->_value = $value;
  }

  public function fromXML($value) {
    $this->_value = $value;
  }

    /**
     * @inheritdoc
     */
    public function toJSON()
    {
        return CFHIR::toJSON($this->_value);
    }

  function jsonSerialize() {
    return $this::toJSON();
  }
}
