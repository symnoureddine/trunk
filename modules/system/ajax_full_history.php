<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Mediboard\System\CHistoryViewer;
use Ox\Mediboard\System\CUserLog;

CCanDo::check();

$object_class = CValue::get("object_class");
$object_id    = CValue::get("object_id");
$path         = CValue::get("path");

/** @var CStoredObject $object */
$object = new $object_class();
$object->load($object_id);

/** @var CStoredObject[] $current_objects */

if (!$path) {
  $current_objects = array($object);
}
else {
  $resolve = array();

  $parts = explode(" ", $path);

  $current_objects = array($object);

  while (count($parts)) {
    $first = array_shift($parts);
    list($type, $_field) = explode(":", $first);

    switch ($type) {
      // Fwd ref
      case "f":
        $_new_objects = array();
        foreach ($current_objects as $_current_obj) {
          $_new_objects[] = $_current_obj->loadFwdRef($_field);
        }

        $current_objects = $_new_objects;
        break;

      // Back ref
      case "b":
        $_new_objects = array();
        foreach ($current_objects as $_current_obj) {
          $_new_objects = array_merge($_new_objects, $_current_obj->loadBackRefs($_field));
        }

        $current_objects = $_new_objects;
        break;
    }
  }
}

$logs = array();
foreach ($current_objects as $_current_obj) {
  $logs = array_merge($logs, $_current_obj->loadBackRefs("user_logs", null, null, null, null, "object_id"));
}

$history = array();
$users   = array();
$objects = array();

$_empty_change = array(
  "rawbefore"  => null,
  "objbefore"  => null,
  "viewbefore" => null,

  "rawafter"   => null,
  "objafter"   => null,
  "viewafter"  => null,
);

$object_states = array();

foreach ($logs as $_log) {
  $_fields = null;

  if ($_log->type == "store" || $_log->type == "merge") {
    $_fields = array_fill_keys($_log->_fields, $_empty_change);

    $old_values = $_log->getOldValues();
    foreach ($_fields as $_field => $_value) {
      $_fields[$_field]["rawbefore"] = $old_values[$_field];
    }
  }

  $history[$_log->_id] = array(
    "id"           => $_log->_id,
    "type"         => $_log->type,
    "date"         => $_log->date,
    "user_id"      => $_log->user_id,
    "object_class" => $_log->object_class,
    "object_id"    => $_log->object_id,
    "changes"      => $_fields,
    "log"          => $_log,
  );

  CHistoryViewer::getObject("CMediusers", $_log->user_id);
  CHistoryViewer::getObject($_log->object_class, $_log->object_id);

  $guid = "$_log->object_class-$_log->object_id";
  if (!isset($object_states[$guid])) {
    $_object = $_log->loadTargetObject();
    $object_states[$guid] = $_object->getPlainFields();
  }
}

krsort($history);

foreach ($history as &$_history) {
  /** @var CUserLog $_log */
  $_log = $_history["log"];
  $guid = "$_log->object_class-$_log->object_id";

  $_object = $_log->loadTargetObject();

  foreach ($_history["changes"] as $_field => $_change) {
    $_history["changes"][$_field]["rawafter"] = $object_states[$guid][$_field];
    $object_states[$guid][$_field] = $_change["rawbefore"];

    if ($_object->_specs[$_field] instanceof CRefSpec) {
      $_class = $_object->_specs[$_field]->class;

      // before
      if ((string)$_change["rawbefore"] !== "") {
        $_history["changes"][$_field]["objbefore"] = array(
          "class" => $_class,
          "id"    => $_change["rawbefore"],
        );
        CHistoryViewer::getObject($_class, $_change["rawbefore"]);
      }

      // after
      if ((string)$_history["changes"][$_field]["rawafter"] !== "") {
        $_history["changes"][$_field]["objafter"] = array(
          "class" => $_class,
          "id"    => $_history["changes"][$_field]["rawafter"],
        );
        CHistoryViewer::getObject($_class, $_history["changes"][$_field]["rawafter"]);
      }
    }
    else {
      if ((string)$_history["changes"][$_field]["rawbefore"] !== "") {
        $_object->$_field = $_history["changes"][$_field]["rawbefore"];
        $_history["changes"][$_field]["viewbefore"] = $_object->getFormattedValue($_field);
      }

      if ((string)$_history["changes"][$_field]["rawafter"] !== "") {
        $_object->$_field = $_history["changes"][$_field]["rawafter"];
        $_history["changes"][$_field]["viewafter"] = $_object->getFormattedValue($_field);
      }
    }
  }

  unset($_history["log"]);
}

$data = array(
  "objects" => CHistoryViewer::$objects,
  "history" => array_values($history),
);

ob_clean();
header("Content-Type: application/json");
echo CMbArray::toJSON($data);
CApp::rip();