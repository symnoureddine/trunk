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
$to_date              = CValue::get("to_date", CMbDT::date("+1 day"));
$from_date            = CMbDT::date("-1 month", CValue::get("from_date", $to_date));

CView::checkin();

$user = new CUser();
try {
    if (!empty($user_id)) {
        $user->load($user_id);
    }
} catch (Exception $e) {
    CApp::error(new CMbException($e->getMessage()));
}

$url_clear_pipelines = 'index.php?m=sourceCode&tab=vw_gitlab_clear_pipelines&interval=P1M&debug=1';

/* Display */
$smarty = new CSmartyDP();
$smarty->assign('pipeline', new CGitlabPipeline());
$smarty->assign('from_date', $from_date);
$smarty->assign('to_date', $to_date);
$smarty->assign('way', $_way);
$smarty->assign('limit', $_limit);
$smarty->assign('url_clear_pipelines', $url_clear_pipelines);
$smarty->display('vw_gitlab_ci.tpl');
