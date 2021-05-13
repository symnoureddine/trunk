<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\System\Forms\CExClass;
use Ox\Mediboard\System\Forms\CExClassEvent;
use Ox\Mediboard\System\Forms\CExObject;

$event_name   = CView::post("event_name", "str");
$object_class = CView::post("object_class", "str");
$form_name    = CView::post("form_name", "str");
$ids          = CView::post("ids", "str");
CView::checkin();

//CExObject::$_load_lite = true;

$ex_class_event = new CExClassEvent();
$ds             = $ex_class_event->getDS();
$group_id       = CGroups::loadCurrent()->_id;

$where = array(
  "ex_class_event.host_class" => $ds->prepare("=%", $object_class),
  "ex_class_event.event_name" => $ds->prepare("=%", $event_name),
  "ex_class.conditional"      => $ds->prepare("=%", 0),
  $ds->prepare("ex_class.group_id = % OR ex_class.group_id IS NULL", $group_id),
);
$ljoin = array(
  "ex_class" => "ex_class.ex_class_id = ex_class_event.ex_class_id",
);

/** @var CExClassEvent[] $ex_class_events */
$ex_class_events = $ex_class_event->loadList($where, null, null, null, $ljoin);

$ex_classes = array();
$ex_objects = array();

$count_available = count($ex_class_events);


foreach ($ex_class_events as $_ex_class_event) {
  $_ex_class = $_ex_class_event->loadRefExClass();
  $_ex_class->getFormulaField();

  $ex_classes[$_ex_class->_id] = $_ex_class;
}

$object_data = array();

foreach ($ids as $_id_element => $_id) {
  $_count_available = $count_available;
  $_count           = 0;

  /** @var CMbObject $object */
  $object = new $object_class;
  $object->load($_id);

  $_ex_objects_by_class = array();

  foreach ($ex_class_events as $_ex_class_event) {
    $_ex_class = $_ex_class_event->_ref_ex_class;

    if ($_ex_class_event->disabled || !$_ex_class_event->checkConstraints($object) || !$_ex_class->canPerm("c")) {
      $_count_available--;
    }

    $_ex_objects = $_ex_class_event->getExObjectForHostObject($object);

    // Only keep first if in "pre fill" mode
    if ($form_name && count($_ex_objects)) {
      $_ex_objects = array(reset($_ex_objects));
    }

    foreach ($_ex_objects as $_ex_object) {
      $_ex_object->getCreateDate();
    }

    $_count += count($_ex_objects);

    if (count($_ex_objects)) {
      $_ex_objects = array_map(
        function ($ex_object) use ($_ex_class) {
          /** @var CExClass $_ex_class */
          $_formula_field = $_ex_class->_formula_field;
          $_formula_value = null;
          if ($_formula_field) {
            $_formula_value = $ex_object->$_formula_field;
          }

          /** @var CExObject $ex_object */
          return array(
            "id"              => $ex_object->_id,
            "view"            => $ex_object->_view,
            "owner"           => $ex_object->loadRefOwner()->_view,
            "datetime_create" => $ex_object->getFormattedValue("datetime_create"),
            "formula_value"   => $_formula_value,
          );
        },
        $_ex_objects
      );

      $_ex_objects_by_class[$_ex_class->_id] = array_values($_ex_objects);
    }
  }

  // Complétude des formulaires pour la préparation entrée avant validation
  $color = "grey";

  if ($object->_class == 'CSejour' || $object->_class == 'COperation') {
    /** @var CSejour $object */
    $object->getColorCompletenessLastForm($event_name);
    $color = $object->_completeness_color_form ?: "grey";

    if (!CAppUI::conf("forms CExClass show_color_score_form") && ($event_name == "preparation_entree" || $event_name == "sortie_preparee")) {
      $color = "grey";
    }
  }

  $object_data[$_id_element] = array(
    "id"           => $_id,
    "count"        => $_count,
    "count_avl"    => $_count_available,
    "ex_objects"   => $_ex_objects_by_class,
    "completeness" => $color,
  );
}

$ex_classes_array = array();
foreach ($ex_classes as $_ex_class) {
  $ex_classes_array[$_ex_class->_id] = $_ex_class->name;
}

$data = array(
  "ex_classes"   => $ex_classes_array,
  "form_name"    => $form_name,
  "event_name"   => $event_name,
  "object_class" => $object_class,
  "objects"      => $object_data,
);

CApp::json($data);
