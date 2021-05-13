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
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkRead();

$start       = CView::get('start', 'num default|0');
$limit       = CView::get('limit', 'num default|100');
$_order      = CView::get('_order', 'str default|authored_date');
$_way        = CView::get('_way', 'str default|DESC');
$from_date   = CView::get('from_date', 'str');
$to_date     = CView::get('to_date', 'str');
$project_id  = CView::get('project_id', 'str');
$branch_id   = CView::get('branch_id', 'str');
$title       = CView::get('title', 'str');
$user_id     = CView::get('user_id', 'str');
$id          = CView::get('id', 'str');
$task_link   = CView::get('task_link', "bool default|0");
$task_create = CView::get('task_create', "bool default|0");
$no_task     = CView::get('no_task', 'str default|off') === 'on';
$no_user     = CView::get('no_user', 'str default|off') === 'on';
$types       = CView::get(
    '_types_list_multi',
    'set list|' . implode('|', CGitlabCommit::TYPES)
);

CView::checkin();

$total   = 0;
$commits = [];

try {
    /* Build query */
    $commit = new CGitlabCommit();
    $commit->needsEdit();
    $ds       = $commit->getDS();
    $where    = [];
    $l_join   = [];
    $group_by = [];
    $order_by = ["{$_order} {$_way}"];

    /* Id */
    if ($id) {
        $where['gitlab_commit.id'] = $ds->prepareLike("%$id%");
    }

    /* Title */
    if ($title) {
        $title                        = stripslashes($title);
        $where['gitlab_commit.title'] = $ds->prepareLike("%$title%");
    }

    /* Authored date */
    if ($from_date && $to_date) {
        $where['gitlab_commit.authored_date'] = $ds->prepare('BETWEEN ?1 AND ?2', $from_date, $to_date);
    } elseif ($from_date) {
        $where['gitlab_commit.authored_date'] = $ds->prepare('> ?', $from_date);
    } elseif ($to_date) {
        $where['gitlab_commit.authored_date'] = $ds->prepare('< ?', $to_date);
    }

    /* Branches */
    if ($branch_id) {
        $where['gitlab_commit.ox_gitlab_branch_id'] = $ds->prepare("= ?", $branch_id);
    } elseif ($project_id) {
        /* Search in all project branches */
        $project = new CGitlabProject();
        $project->load($project_id);
        if ($project->ox_gitlab_project_id) {
            $branches = $project->loadRefBranches();
            if (empty($branches)) {
                CAppUI::stepAjax(
                    CAppUI::tr('CGitlabProject-error-No branches for project : %s', $project->name),
                    UI_MSG_ERROR
                );
            } else {
                $branch_ids = CMbArray::pluck($branches, 'ox_gitlab_branch_id');
                if (!empty($branch_ids)) {
                    $where['gitlab_commit.ox_gitlab_branch_id'] = $ds->prepareIn($branch_ids);
                }
            }
        }
    }

    /* Type */
    if ($types) {
        $where['gitlab_commit.type'] = $ds::prepareIn(explode('|', $types));
    }

    /* User */
    if ($user_id) {
        $where['gitlab_commit.ox_user_id'] = $ds->prepare("= ?", $user_id);
    } elseif ($no_user) {
        /* Has no assigned user */
        $where['gitlab_commit.ox_user_id'] = "IS NULL";
    }

    /* Task */
    if ($no_task) {
        $l_join['tasking_ticket_commit']          = "`tasking_ticket_commit`.`commit_id` = `gitlab_commit`.`ox_gitlab_commit_id`";
        $where['tasking_ticket_commit.commit_id'] = "IS NULL";
    }

    /* Performing database query */
    $commits = $commit->loadList($where, $order_by, "{$start}, {$limit}", $group_by, $l_join);

    CStoredObject::massLoadFwdRef($commits, 'ox_gitlab_branch_id');

    foreach ($commits as $commit) {
        $commit->loadRefProject();
        $commit->loadRefTaskingTickets();
    }

    /* Performing count query */
    $total = $commit->countList($where, $group_by, $l_join);
} catch (Exception $e) {
    CAppUI::stepAjax($e, UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign('lines', $commits);
$smarty->assign('total', $total);
$smarty->assign('start', $start);
$smarty->assign('limit', $limit);
$smarty->assign('_order', $_order);
$smarty->assign('_way', $_way);
$smarty->assign('task_link', $task_link);
$smarty->assign('task_create', $task_create);
$smarty->display('inc_vw_gitlab_commits_search_results.tpl');
