<?php
/**
 * @package Mediboard\MonitoringPatient
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\MonitoringPatient;

use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * Observation value unit, based on the HL7 OBX specification
 * http://www.interfaceware.com/hl7-standard/hl7-segment-OBX.html
 */
class CObservationValueCodingSystem extends CMbObject {
  public $code;
  public $label;
  public $desc;
  public $coding_system;
  public $group_id;

  static $_list = array(
    "MB"   => "Standard Mediboard",
    "MDIL" => "MDIL",
  );

  protected $_countBackProps = array();
  public $_usages_self = array();
  public $_usages_other = array();

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                  = parent::getProps();
    $props["code"]          = "str notNull seekable";
    $props["label"]         = "str notNull seekable";
    $props["desc"]          = "str seekable";
    $props["coding_system"] = "str notNull";
    $props["group_id"]      = "ref class|CGroups";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    if ($this->desc) {
      $this->_view = "$this->desc [$this->label]";
    }
    else {
      $this->_view = $this->label;
    }
  }

  /**
   * Load a matching coding system, creates one if unknown
   *
   * @param string $code          The code
   * @param string $coding_system The coding system
   * @param string $label         Label
   * @param string $desc          Optional description
   *
   * @return int The cding system ID
   */
  function loadMatch($code, $coding_system, $label, $desc = null) {
    $ds = $this->_spec->ds;

    $where = array(
      "code"          => $ds->prepare("=%", $code),
      "coding_system" => $ds->prepare("=%", $coding_system),
    );

    if (!$this->loadObject($where)) {
      $this->code          = $code;
      $this->coding_system = $coding_system;
      $this->label         = $label;
      $this->desc          = $desc;
      if ($this instanceof CObservationValueType) {
        $this->datatype = "NM";
      }
      $this->store();
    }

    return $this->_id;
  }

  /**
   * @inheritdoc
   */
  function getAutocompleteList($keywords, $where = null, $limit = null, $ljoin = null, $order = null, $group_by = null, bool $strict = true) {
    $group_id = CGroups::loadCurrent()->_id;
    $where[]  = "group_id IS NULL OR group_id = '$group_id'";

    return parent::getAutocompleteList($keywords, $where, $limit, $ljoin, $order, $group_by, $strict);
  }

  /**
   * Count usage in different tables
   *
   * @return void
   */
  function countUsages() {
    $counts_self  = array();
    $counts_other = array();

    $group_id = CGroups::loadCurrent()->_id;

    foreach ($this->_countBackProps as $_backProp) {
      $_backSpec = $this->makeBackSpec($_backProp);
      $_class    = $_backSpec->class;

      /** @var CMbObject $_obj */
      $_obj   = new $_class;
      $_table = $_obj->_spec->table;

      $where = array(
        "$_table.$_backSpec->field" => "= '$this->_id'",
      );

      switch ($_backProp) {
        case "observation_results":
          $ljoin = array(
            "observation_result_set" =>
              "observation_result_set.observation_result_set_id = observation_result.observation_result_set_id",
            "operations"             =>
              "operations.operation_id = observation_result_set.context_id AND observation_result_set.context_class = 'COperations'",
            "sejour"                 =>
              "sejour.sejour_id = operations.sejour_id",
          );

          $where["sejour.group_id"] = " != '$group_id'";
          $counts_other[$_backProp] = $_obj->countList($where, null, $ljoin);

          $where["sejour.group_id"] = " = '$group_id'";
          $counts_self[$_backProp]  = $_obj->countList($where, null, $ljoin);
          break;

        case "supervision_graph_series":
          $ljoin = array(
            "supervision_graph_axis" =>
              "supervision_graph_series.supervision_graph_axis_id = supervision_graph_axis.supervision_graph_axis_id",
            "supervision_graph"      =>
              "supervision_graph_axis.supervision_graph_id = supervision_graph.supervision_graph_id",
          );

          $where["supervision_graph.owner_class"] = " = 'CGroups'";

          $where["supervision_graph.owner_id"] = " != '$group_id'";
          $counts_other[$_backProp]            = $_obj->countList($where, null, $ljoin);

          $where["supervision_graph.owner_id"] = " = '$group_id'";
          $counts_self[$_backProp]             = $_obj->countList($where, null, $ljoin);
          break;

        case "supervision_timed_data":
        case "supervision_instant_data":
        case "supervision_timed_pictures":
          $ljoin = array(
            "operations" =>
              "operations.operation_id = $_table.owner_id AND $_table.owner_class = 'COperations'",
            "sejour"     =>
              "sejour.sejour_id = operations.sejour_id",
          );

          $where["sejour.group_id"] = " != '$group_id'";
          $counts_other[$_backProp] = $_obj->countList($where, null, $ljoin);

          $where["sejour.group_id"] = " = '$group_id'";
          $counts_self[$_backProp]  = $_obj->countList($where, null, $ljoin);
          break;

        default:
          trigger_error("This back prop : '$_backProp' is not taken into account in " . __METHOD__, E_USER_WARNING);
      }
    }

    $this->_usages_other = $counts_other;
    $this->_usages_self  = $counts_self;
  }
}

if (PHP_SAPI !== 'cli') {
  if (CModule::getActive("monitoringPatient")) {
    CObservationValueCodingSystem::$_list["Kheops-Concentrator"] = "Concentrateur Kheops";
  }
}
