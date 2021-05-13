<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;

CCanDo::checkRead();

$limit       = CView::get('limit', 'num default|30');
$order       = CView::get('order', 'str default|ox_gitlab_branch_id');
$way         = CView::get('way', 'enum list|DESC|ASC default|DESC');
$input_field = CView::get('input_field', 'str');
$str         = trim(CView::get($input_field, 'str'));
$project_id  = CView::get('project_id', 'str');

CView::checkin();
CView::enableSlave();

$ds = CSQLDataSource::get('std');
$branch = new CGitlabBranch();

$where = array();

if ($str) {
  $where['name'] = $ds->prepareLike("%{$str}%");
}

if ($project_id) {
  $where['ox_gitlab_project_id'] = $ds->prepare('= ?', $project_id);
}

$matches = $branch->loadList($where, "{$order} {$way}", $limit);

/** @var CGitlabBranch $match */
foreach ($matches as $match) {
  $match->loadRefGitlabProject();
}

$smarty = new CSmartyDP();
$smarty->assign('matches', $matches);
$smarty->display('CGitlabBranch_autocomplete.tpl');
