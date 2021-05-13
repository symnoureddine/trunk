<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabJobClassReport;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkRead();

$tests_report_id = CView::get('tests_report_id', 'str');
$namespace       = CView::get('namespace', 'str');
$class           = CView::get('class', 'str');
$_coverage_from  = CView::get('_coverage_from', 'float');
$_coverage_to    = CView::get('_coverage_to', 'float');
$start           = CView::get('start', 'num default|0');
$limit           = CView::get('limit', 'num default|100');
$_order          = CView::get('_order', 'str default|namespace');
$_way            = CView::get('_way', 'str default|ASC');

CView::checkin();

$total         = 0;
$class_reports = [];

try {
    /* Build query */
    $class_report = new CGitlabJobClassReport();
    $class_report->needsRead();
    $ds            = $class_report->getDS();
    $where         = [];
    $l_join        = [];
    $group_by      = [];
    $order_by      = ["{$_order} {$_way}"];

    /* Liaison Rapport Tests */
    if ($tests_report_id) {
        $where['gitlab_job_class_report.ox_gitlab_job_tests_report_id'] = $ds->prepare(
            "= ?",
            $tests_report_id
        );
    }

    /* Namespace */
    if ($namespace) {
        $where['gitlab_job_class_report.namespace'] = $ds->prepareLike("%$namespace%");
    }

    /* Class */
    if ($class) {
        $where['gitlab_job_class_report.class'] = $ds->prepareLike("%$class%");
    }

    /* Coverage */
    if ($_coverage_from && $_coverage_to) {
        $where['gitlab_job_class_report.coverage'] = $ds->prepare('BETWEEN ?1 AND ?2', $_coverage_from, $_coverage_to);
    } elseif ($_coverage_from) {
        $where['gitlab_job_class_report.coverage'] = $ds->prepare('>= ?', $_coverage_from);
    } elseif ($_coverage_to) {
        $where['gitlab_job_class_report.coverage'] = $ds->prepare('<= ?', $_coverage_to);
    }

    /* Performing database query */
    $class_reports = $class_report->loadList($where, $order_by, "{$start}, {$limit}", $group_by, $l_join);

    /* Performing count query */
    $total = $class_report->countList($where, $group_by, $l_join);
} catch (Exception $e) {
    CAppUI::stepAjax($e, UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign('lines', $class_reports);
$smarty->assign('total', $total);
$smarty->assign('start', $start);
$smarty->assign('limit', $limit);
$smarty->assign('_order', $_order);
$smarty->assign('_way', $_way);
$smarty->display('inc_vw_gitlab_job_class_reports_search_results.tpl');
