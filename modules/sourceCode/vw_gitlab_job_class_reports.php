<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use GuzzleHttp\Client;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabJobClassReport;

CCanDo::checkAdmin();

$start           = CView::get('start', 'num default|0');
$limit           = CView::get('limit', 'num default|100');
$_order          = CView::get('_order', 'str default|namespace');
$_way            = CView::get('_way', 'str default|ASC');
$tests_report_id = CView::get('tests_report_id', 'str');

CView::checkin();

$class_report = new CGitlabJobClassReport();

$smarty = new CSmartyDP();
$smarty->assign('start', $start);
$smarty->assign('limit', $limit);
$smarty->assign('order', $_order);
$smarty->assign('way', $_way);
$smarty->assign('class_report', $class_report);
$smarty->assign('tests_report_id', $tests_report_id);
$smarty->display('inc_vw_gitlab_job_class_reports.tpl');
