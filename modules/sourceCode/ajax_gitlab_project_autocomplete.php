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
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;

CCanDo::checkRead();

$ready       = CView::get('ready', 'bool default|1');
$limit       = CView::get('limit', 'num default|30');
$input_field = CView::get('input_field', 'str');
$str         = trim(CView::get($input_field, 'str'));

CView::checkin();
CView::enableSlave();

$ds = CSQLDataSource::get('std');
$project = new CGitlabProject();

if ($ready) {
  $where['ready'] = $ds->prepare('= ?', '1');
}

if ($str) {
  $where['name_with_namespace'] = $ds->prepareLike("%{$str}%");
}

$matches = $project->loadList($where, null, $limit);

$smarty = new CSmartyDP();
$smarty->assign('matches', $matches);
$smarty->display('CGitlabProject_autocomplete.tpl');