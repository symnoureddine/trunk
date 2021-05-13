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
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabPipeline;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkRead();

$start      = CView::get('start', 'num default|0');
$limit      = CView::get('limit', 'num default|100');
$_order     = CView::get('_order', 'str default|finished_at');
$_way       = CView::get('_way', 'str default|DESC');
$from_date  = CView::get('from_date', 'str');
$to_date    = CView::get('to_date', 'str');
$project_id = CView::get('project_id', 'str');
$id         = CView::get('id', 'str');
$statuses   = CView::get('_statuses_list', 'set list|' . implode('|', CGitlabPipeline::STATUSES));

CView::checkin();

$total     = 0;
$pipelines = [];

try {
    /* Build query */
    $pipeline = new CGitlabPipeline();
    $pipeline->needsEdit();
    $ds       = $pipeline->getDS();
    $where    = [];
    $l_join   = [];
    $group_by = [];
    $order_by = ["{$_order} {$_way}"];

    /* Id */
    if ($id) {
        $where['gitlab_pipeline.id'] = $ds->prepareLike("%$id%");
    }

    /* Finish date */
    if ($from_date && $to_date) {
        $where['gitlab_pipeline.finished_at'] = $ds->prepare('BETWEEN ?1 AND ?2', $from_date, $to_date);
    } elseif ($from_date) {
        $where['gitlab_pipeline.finished_at'] = $ds->prepare('> ?', $from_date);
    } elseif ($to_date) {
        $where['gitlab_pipeline.finished_at'] = $ds->prepare('< ?', $to_date);
    }

    /* Branches */
    if ($project_id) {
        $where['gitlab_pipeline.ox_gitlab_project_id'] = $ds->prepare("= ?", $project_id);
    }

    /* Statuses */
    if ($statuses) {
        $where['gitlab_pipeline.status'] = $ds::prepareIn(explode('|', $statuses));
    }

    /* Performing database query */
    $pipelines = $pipeline->loadList($where, $order_by, "{$start}, {$limit}", $group_by, $l_join);

    CStoredObject::massLoadFwdRef($pipelines, 'ox_gitlab_project_id');
    CStoredObject::massLoadBackRefs($pipelines, 'gitlab_pipeline_jobs');

    foreach ($pipelines as $pipeline) {
        $pipeline->loadRefGitlabProject();
        $pipeline->loadJobTestsReport();
    }

    /* Performing count query */
    $total = $pipeline->countList($where, $group_by, $l_join);
} catch (Exception $e) {
    CAppUI::stepAjax($e, UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign('lines', $pipelines);
$smarty->assign('total', $total);
$smarty->assign('start', $start);
$smarty->assign('limit', $limit);
$smarty->assign('_order', $_order);
$smarty->assign('_way', $_way);
$smarty->display('inc_vw_gitlab_pipelines_search_results.tpl');
