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
use Ox\Core\CMbArray;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

CCanDo::checkAdmin();

$dry_run = CView::get('dry_run', 'bool default|1');

CView::checkin();

CApp::setTimeLimit(360);

$data = [];

if ($dry_run === "0") {
  try {
    $projects = CGitlabManager::importAllProjects();
    if (!empty($projects)) {
      $data['projects'] = CMbArray::pluck($projects, 'web_url');
    }
    $branches = CGitlabManager::importAllProjectBranches();
    if (!empty($branches)) {
      $data['branches'] = CMbArray::pluck($branches, 'web_url');
    }
    CApp::log('Gitlab commits projects and branches', $data, CLogger::LEVEL_INFO);
  } catch (Exception $e) {
    CApp::log($e->getMessage(), $data, CLogger::LEVEL_ERROR);
  }
}
else {
  $data = [
    'message' => 'Gitlab projects and branches import : Dry-run mode is on. No import was made.'
  ];
}

CApp::json($data);

