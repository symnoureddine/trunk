<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\Chronometer;
use Ox\Core\CModelObject;
use Ox\Core\CSmartyDP;
use Ox\Core\SHM;

CCanDo::checkRead();

$chrono = new Chronometer();
$chrono->start();

$classes = CApp::getChildClasses(CModelObject::class);

foreach ($classes as $_class) {
  try {
    /** @var CModelObject $object */
    $object = new $_class;
    $object->makeAllBackSpecs();
    $chrono->step("make");
  }
  catch (Throwable $e) {
    continue;
  }
}

foreach ($classes as $_class) {
  $ballot = array(
    "spec"      => CModelObject::$spec[$_class],
    "props"     => CModelObject::$props[$_class],
    "specs"     => CModelObject::$specs[$_class],
    "backProps" => CModelObject::$backProps[$_class],
    "backSpecs" => CModelObject::$backSpecs[$_class],
  );
  SHM::put("ballot-$_class", $ballot, true);
  $chrono->step("put");
}

foreach ($classes as $_class) {
  SHM::get("ballot-$_class");
  $chrono->step("get");
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("chrono", $chrono);
$smarty->display("cache_tester_metamodel.tpl");

