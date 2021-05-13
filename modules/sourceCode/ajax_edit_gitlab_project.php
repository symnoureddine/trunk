<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkEdit();

$project_id = CView::get('project_id', 'str notNull');

CView::checkin();

$project = new CGitlabProject();
$project->needsEdit();
$project->load($project_id);

if ($project->ox_gitlab_project_id) {
  $project->loadRefBranches();
}

$smarty = new CSmartyDP();
$smarty->assign('project', $project);
$smarty->display('inc_edit_gitlab_project.tpl');