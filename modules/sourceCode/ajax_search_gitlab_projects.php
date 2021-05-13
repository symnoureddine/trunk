<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkRead();

$start               = CView::get('start', 'num default|0');
$limit               = CView::get('limit', 'num default|100');
$_order              = CView::get('_order', 'str default|name');
$_way                = CView::get('_way', 'str default|DESC');
$name                = CView::get('name', 'str');
$name_with_namespace = CView::get('name_with_namespace', 'str');
$ready               = CView::get('ready', "bool default|1");

CView::checkin();

$total    = 0;
$projects = [];

try {
  /* Build query */
  $project = new CGitlabProject();
  $project->needsEdit();
  $ds = $project->getDS();
  $where = [];
  $l_join = [];
  $group_by = [];
  $order_by = ["{$_order} {$_way}"];

  /* Name */
  if ($name) {
    $where['gitlab_project.name'] = $ds->prepareLike("%$name%");
  }

  /* Name with namespace */
  if ($name_with_namespace) {
    $where['gitlab_project.name_with_namespace'] = $ds->prepareLike("%$name_with_namespace%");
  }

  /* Ready state */
  $where['gitlab_project.ready'] = $ds->prepareLike("%$ready%");

  /* Performing database query */
  $projects = $project->loadList($where, $order_by, "{$start}, {$limit}", $group_by, $l_join);

  /** @var CGitlabProject $_project */
  foreach ($projects as $_project) {
    $_project->loadRefBranches();
  }

  $total = $project->countList($where, $group_by, $l_join);

} catch (Exception $e) {
  CAppUI::stepAjax($e, UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign('lines', $projects);
$smarty->assign('total', $total);
$smarty->assign('start', $start);
$smarty->assign('limit', $limit);
$smarty->assign('_order', $_order);
$smarty->assign('_way', $_way);
$smarty->display('inc_vw_gitlab_projects_search_results.tpl');