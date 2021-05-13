<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\Chronometer;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Core\SHM;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$purge = CView::get("purge", "bool default|0");

CView::checkin();

$chrono = new Chronometer();
$chrono->start();

if ($purge) {
  SHM::rem("mediusers");
  $chrono->step("purge");
}

if (!SHM::exists("mediusers")) {
  $chrono->step("acquire (not yet)");
  $mediuser = new CMediusers();
  $mediusers = $mediuser->loadListFromType();
  $chrono->step("load");
  SHM::put("mediusers", $mediusers, true);
  $chrono->step("put");
}

/** @var CMediusers[] $mediusers */
$mediusers = SHM::get("mediusers");
$chrono->step("get");

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("mediusers", $mediusers);
$smarty->assign("chrono", $chrono);
$smarty->display("cache_tester_users.tpl");

