<?php
/**
 * @package Mediboard\MonitoringPatient
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\MonitoringPatient;

use Exception;

/**
 * Observation value unit, based on the HL7 OBX specification
 * http://www.interfaceware.com/hl7-standard/hl7-segment-OBX.html
 */
class CObservationValueUnit extends CObservationValueCodingSystem {
  public $observation_value_unit_id;

  /** @var string The text displayed in the graphs or tables */
  public $display_text;

  protected $_countBackProps = array(
    "observation_results",
    "supervision_graph_series",
    "supervision_instant_data",
  );

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "observation_value_unit";
    $spec->key   = "observation_value_unit_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  public function getProps() {
    $props = parent::getProps();

    $props["group_id"]    .= " back|observation_value_units";
    $props['display_text'] = 'str';

    return $props;
  }

  /**
   * Get a unit by its ID
   *
   * @param int $unit_id The unit ID
   *
   * @return CObservationValueUnit
   * @throws Exception
   */
  static function get($unit_id) {
    static $cache = array();

    if (isset($cache[$unit_id])) {
      return $cache[$unit_id];
    }

    $unit = new self;
    $unit->load($unit_id);

    return $cache[$unit_id] = $unit;
  }
}