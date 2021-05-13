<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabPipeline;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;
use Ox\Mediboard\Admin\CUser;

CCanDo::checkAdmin();

$_way                 = CView::get('_way', 'str default|DESC');
$_limit               = CView::get('_limit', 'str default|50');
$title                = CView::get('title', 'str');
$user_id              = CView::get('user_id', 'str');
$tasking_ticket_id    = CView::get('tasking_ticket_id', 'str');
$no_task              = CView::get('no_task', 'bool default|0');
$only_display_commits = CView::get('only_display_commits', 'bool default|0');
$task_link            = CView::get('task_link', "bool default|0");
$task_create          = CView::get('task_create', "bool default|0");
$to_date              = CValue::get("to_date", CMbDT::date("+1 day"));
$from_date            = CMbDT::date("-1 week", CValue::get("from_date", $to_date));

CView::checkin();

$user = new CUser();
try {
    if (!empty($user_id)) {
        $user->load($user_id);
    }
} catch (Exception $e) {
    CApp::error(new CMbException($e->getMessage()));
}

/* Display */
$smarty = new CSmartyDP();
$smarty->assign('commit', new CGitlabCommit());
$smarty->assign('project', new CGitlabProject());
$smarty->assign('from_date', $from_date);
$smarty->assign('to_date', $to_date);
$smarty->assign('way', $_way);
$smarty->assign('limit', $_limit);
$smarty->assign('title', $title);
$smarty->assign('user', $user->_id ? $user : false);
$smarty->assign('no_task', $no_task);
$smarty->assign('only_display_commits', $only_display_commits);
$smarty->assign('tasking_ticket_id', $tasking_ticket_id);
$smarty->assign('task_link', $task_link);
$smarty->assign('task_create', $task_create);
$smarty->display('vw_gitlab.tpl');
