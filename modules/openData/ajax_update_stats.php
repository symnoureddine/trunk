<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkEdit();

$nb_news      = CView::get('nb_news', 'num default|0');
$nb_exists    = CView::get('nb_exists', 'num default|0');
$nb_conflicts = CView::get('nb_conflicts', 'num default|0');
$nb_used      = CView::get('nb_used', 'num default|0');
$nb_unused    = CView::get('nb_unused', 'num default|0');
$nb_rpps      = CView::get('nb_rpps', 'num default|0');
$nb_tel_error = CView::get('nb_tel_error', 'num default|0');
$time         = CView::get('time', 'float default|0');

CView::checkin();

$cache = new Cache('CMedecinImport', 'stats', Cache::OUTER | Cache::DISTR);
$total_stats = $cache->get();
$total_time  = number_format($total_stats['duration']/60);

$friendly_total_time = CMbDT::getHumanReadableDuration($total_time);

$smarty = new CSmartyDP();
$smarty->assign('total_stats', $total_stats);
$smarty->assign('nb_news', $nb_news);
$smarty->assign('nb_exists', $nb_exists);
$smarty->assign('nb_conflicts', $nb_conflicts);
$smarty->assign('nb_used', $nb_used);
$smarty->assign('nb_unused', $nb_unused);
$smarty->assign('nb_rpps', $nb_rpps);
$smarty->assign('nb_tel_error', $nb_tel_error);
$smarty->assign('time', $time);
$smarty->assign('total_time', $friendly_total_time);
$smarty->display('inc_stats_import.tpl');