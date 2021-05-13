<?php
/**
 * @package Mediboard\MonitoringPatient
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\MonitoringPatient;

/**
 * Observation value type, based on the HL7 OBX specification
 * http://www.interfaceware.com/hl7-standard/hl7-segment-OBX.html
 */
class CObservationValueType extends CObservationValueCodingSystem {
  public $observation_value_type_id;

  public $datatype;

  protected $_countBackProps = array(
    "observation_results",
    "supervision_graph_series",
    "supervision_timed_data",
    "supervision_timed_pictures",
    "supervision_instant_data",
  );

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "observation_value_type";
    $spec->key   = "observation_value_type_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["group_id"] .= " back|observation_value_types";
    // AD|CF|CK|CN|CP|CWE|CX|DT|DTM|ED|FT|MO|NM|PN|RP|SN|ST|TM|TN|TX|XAD|XCN|XON|XPN|XTN
    $props["datatype"] = "enum notNull list|NM|ST|FILE"; // TX|

    return $props;
  }
}
