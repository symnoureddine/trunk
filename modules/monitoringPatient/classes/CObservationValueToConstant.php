<?php
/**
 * @package Mediboard\MonitoringPatient
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\MonitoringPatient;

use Ox\Core\CMbObject;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * Description
 */
class CObservationValueToConstant extends CMbObject {
  /**
   * @var integer Primary key
   */
  public $observation_value_to_constant_id;

  /** @var integer The CObservationValueType's id */
  public $value_type_id;
  /** @var integer The CObservationValueUnit's id */
  public $value_unit_id;
  /** @var string The name of the constant in the CConstantesMedicales class */
  public $constant_name;
  /** @var integer The ratio to convert the value from the concentrator to the unit used in Mediboard */
  public $conversion_ratio;
  /** @var integer The operation to perform for the conversion (multiplication or division) */
  public $conversion_operation;

  /** @var CObservationValueType */
  public $_ref_value_type;
  /** @var CObservationValueUnit */
  public $_ref_value_unit;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "observation_values_to_constant";
    $spec->key   = "observation_value_to_constant_id";

    return $spec;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();

    $props['value_type_id']        = 'ref class|CObservationValueType notNull autocomplete|_view back|observation_value_to_constant';
    $props['value_unit_id']        = 'ref class|CObservationValueUnit notNull autocomplete|_view back|observation_value_to_constant';
    $props['constant_name']        = 'str notNull';
    $props['conversion_ratio']     = 'float default|1';
    $props['conversion_operation'] = 'enum list|*|/ default|*';

    return $props;
  }

  /**
   * Convert the given value with setted conversion operation and ratio
   *
   * @param float $value The value to convert
   *
   * @return float
   */
  public function convertValue($value) {
    if ($this->conversion_ratio) {
      if ($this->conversion_operation === '/') {
        $value = $value / $this->conversion_ratio;
      }
      else {
        $value = $value * $this->conversion_ratio;
      }
    }

    return $value;
  }

  /**
   * Load the CObservationValueType object
   *
   * @return CObservationValueType
   */
  public function loadRefValueType() {
    if (!$this->_ref_value_type) {
      $this->_ref_value_type = $this->loadFwdRef('value_type_id');
    }

    return $this->_ref_value_type;
  }

  /**
   * Load the CObservationValueUnit object
   *
   * @return CObservationValueUnit
   */
  public function loadRefValueUnit() {
    if (!$this->_ref_value_unit) {
      $this->_ref_value_unit = $this->loadFwdRef('value_unit_id');
    }

    return $this->_ref_value_unit;
  }

  /**
   * Load all the CObservationValueToConstant for the given group.
   * The return is an array in the format [type_code][unit_code] => object
   *
   * @param CGroups $group The group
   *
   * @return array
   */
  public static function loadForGroup($group = null) {
    if (!$group || !$group->_id) {
      $group = CGroups::loadCurrent();
    }

    $conversion = new CObservationValueToConstant();
    $ljoin      = array(
      'observation_value_type' => 'observation_value_type.observation_value_type_id = observation_values_to_constant.value_type_id'
    );
    $where      = array(
      "observation_value_type.group_id = {$group->_id} OR observation_value_type.group_id IS NULL"
    );

    /** @var CObservationValueToConstant[] $list */
    $list = $conversion->loadList($where, null, null, 'observation_value_to_constant_id', $ljoin);
    CMbObject::massLoadFwdRef($list, 'value_type_id');
    CMbObject::massLoadFwdRef($list, 'value_unit_id');

    $conversions = array();
    foreach ($list as $_conversion) {
      $_type = $_conversion->loadRefValueType();
      $_unit = $_conversion->loadRefValueUnit();

      if (!array_key_exists($_type->code, $conversions)) {
        $conversions[$_type->code] = array();
      }

      $conversions[$_type->code][$_unit->code] = $_conversion;
    }

    return $conversions;
  }
}
