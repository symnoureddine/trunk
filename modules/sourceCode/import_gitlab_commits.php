<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CLogger;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

CCanDo::checkAdmin();

$project_id = CView::get('project_id', 'ref class|CGitlabProject');
$branch_id  = CView::get('branch_id', 'ref class|CGitlabBranch');
$init       = CView::get('init', 'bool default|0') === "1";
$bind       = CView::get('bind', 'bool default|1') === "1";
$dry_run    = CView::get('dry_run', 'bool default|1');

CView::checkin();
CApp::setTimeLimit(3600);

$data = [];

if ($dry_run === "0") {
  try {
    /* Load all projects marked as ready */
    $projects = CGitlabManager::loadProjects(true);

    foreach ($projects as $project) {

      if (!empty($project_id) && intval($project->ox_gitlab_project_id) !== intval($project_id)) {
        continue;
      }

      $project_data = [];

      $project->loadRefBranches();

      $branches = CGitlabBranch::orderBranches($project->_ref_branches);

      foreach ($branches as $branch) {
        if (!empty($branch_id) && $branch->ox_gitlab_branch_id !== $branch_id) {
          continue;
        }

        $total = 0;
        if ($branch->hasProject($project)) {
          $page = 1;
          $counter = CGitlabCommit::API_PAGE_LIMIT;
          while (CGitlabCommit::API_PAGE_LIMIT === $counter) {
            $imported_commits = CGitlabManager::importCommits($project, $branch, $init, $bind, $page);
            $counter = count($imported_commits);
            $total += $counter;
            if (!$init) {
              $page++;
            }
          }
        }
        $project_data[$branch->name] = $total;
      }

      $data[$project->name_with_namespace] = $project_data;

      CApp::log('Gitlab commits projects and branches', $data, CLogger::LEVEL_INFO);
    }

  } catch (Exception $e) {
    CApp::log($e->getMessage(), $data, CLogger::LEVEL_ERROR);
  }
}
else {
  $data = [
    'message' => 'Gitlab commits import : Dry-run mode is on. No import was made.'
  ];
}


CApp::json($data);

