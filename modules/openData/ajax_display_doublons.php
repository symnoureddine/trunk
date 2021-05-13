<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkRead();

$start = CView::get('start', 'num default|0');
$step = CView::get('step', 'num default|20');

CView::checkin();

$cache = new Cache('CMedecin-doublons', 'import', Cache::OUTER | Cache::DISTR);
$doublons = $cache->get();

if (!$doublons) {
  $doublons = array();
}
$total = count(array_keys($doublons));

$display_doublons = array();
$medecin = new CMedecin();

$medecins = array_slice($doublons, $start, $step);

foreach ($medecins as $_key => $_medecins_ids) {
  if (!isset($display_doublons[$_key])) {
    $display_doublons[$_key] = array();
  }

  $_meds = $medecin->loadAll(array_keys($_medecins_ids));

  foreach ($_meds as $_id => $_med) {
    $display_doublons[$_key][] = $_med;
  }
}

$smarty = new CSmartyDP();
$smarty->assign('start', $start);
$smarty->assign('step', $step);
$smarty->assign('total', $total);
$smarty->assign('doublons', $display_doublons);
$smarty->display('inc_vw_medecin_doublons.tpl');