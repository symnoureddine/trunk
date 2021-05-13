<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CLogger;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;

$objects_id     = CValue::post("_objects_id"); // array
$objects_class  = CValue::post("_objects_class");
$base_object_id = CValue::post("_base_object_id");
$del            = CValue::post("del");
$fast           = CValue::post("fast");

CApp::setMemoryLimit("512M");

// If the class is valid
if (class_exists($objects_class)) {

  if ($objects_class == "CSejour" && !CSejour::getAllowMerge()) {
    CAppUI::commonError('CSejour-merge-warning-Not allowed');
    return;
  }

  $objects = array();
  $do = new CDoObjectAddEdit($objects_class);
  
  // If alt mode, load the specified object
  if ($base_object_id) {
    $do->_obj->load($base_object_id);
  }
  
  // Cr�ation du nouvel objet
  if (intval($del)) {
    $do->errorRedirect("Fusion en mode suppression impossible");
  }
  
  // Unset the base_object from the list
  if ($do->_obj->_id) {
    foreach ($objects_id as $key => $object_id) {
      if ($do->_obj->_id == $object_id) {
        unset($objects_id[$key]);
        unset($_POST["_merging"][$base_object_id]);
      }
    }
    // Only one objet to merge if not admin
    $objects_id = (CMediusers::get()->isAdmin() && $objects_class != 'CPatients' && $objects_class != 'CSejour')
      ? $objects_id : array(reset($objects_id));
  }
  
  foreach ($objects_id as $object_id) {
    /** @var CMbObject $object */
    $object = new $objects_class;

    if ($fast && $object->_spec->merge_type == 'check') {
      $fast = false;
    }
    
    // the CMbObject is loaded
    if (!$object->load($object_id)) {
      $do->errorRedirect("Chargement impossible de l'objet [$object_id]");
      continue;
    }
    $objects[] = $object;
  }

  // Check merge
  if ($msg = $do->_obj->checkMerge(array_merge($objects, array($do->_obj)))) {
    CAppUI::setMsg($msg, UI_MSG_ERROR);
    return;
  }
  
  // the result data is binded to the new CMbObject
  $do->doBind();
  
  // the objects are merged with the result
  if ($msg = $do->_obj->merge($objects, $fast)) {
    CApp::log('Merge error', $msg, CLogger::LEVEL_ERROR);
    $do->errorRedirect($msg);
  }

  $do->doRedirect();
}
