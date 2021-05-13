<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

CCanDo::checkAdmin();

$project_id = CView::get('project_id', 'str');
$branch_id  = CView::get('branch_id', 'str');
$init       = CView::get('init', 'str default|off');
$bind       = CView::get('bind', 'str default|on');
$continue   = CView::get('continue', 'bool default|0');
$page       = CView::get('page', 'num default|1');

CView::checkin();

try {
  $project = new CGitlabProject();
  $project->load($project_id);

  if ($project->ox_gitlab_project_id) {
    $branch = new CGitlabBranch();
    $branch->load($branch_id);
    if ($branch->ox_gitlab_branch_id && $branch->hasProject($project)) {
      $commits = CGitlabManager::importCommits(
        $project,
        $branch,
        $init === 'on',
        $bind === 'on',
        $page
      );

      $message = "Gitlab commits imported : ".count($commits).PHP_EOL;
      $message .= "Project : ".$project->name_with_namespace.PHP_EOL;
      $message .= "Branch : ".$branch->name.PHP_EOL;
      $message .= "Commits total : ".CGitlabManager::countCommits($branch);

      CAppUI::setMsg($message, UI_MSG_OK);

      if (count($commits) < CGitlabCommit::API_PAGE_LIMIT) {
        CAppUI::stepAjax("CGitlabCommit-msg-Import has ended", UI_MSG_OK);
        CAppUI::js('setTimeout("Gitlab.stopCommitsImport()", 2000)');
      }
      else {
        $page++;
        CAppUI::js('setTimeout("Gitlab.nextCommitsImport('.$page.')", 2000)');
      }
    }
    else {
      CAppUI::setMsg("CGitlabBranch-error-Import No branch specified", UI_MSG_ERROR);
    }
  }
  else {
    CAppUI::setMsg("CGitlabProject-error-Import No project specified", UI_MSG_ERROR);
  }
} catch (Exception $e) {
  CAppUI::setMsg($e->getMessage(), UI_MSG_ERROR);
}

echo CAppUI::getMsg();

