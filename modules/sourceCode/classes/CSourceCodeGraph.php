<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CModelObject;
use Ox\Core\CRequest;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Graph representation class
 */
class CSourceCodeGraph extends CModelObject {

  /** @var CMbDT $start_date */
  public $start_date;

  /** @var CMbDT $end_date */
  public $end_date;

  /** @var array $commitInfosList */
  public $commitInfosList;

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps(): array {
    $props               = parent::getProps();
    $props["start_date"] = "date";
    $props["end_date"]   = "date";
    $props["stacked"]    = "bool default|1";

    return $props;
  }

  /**
   * Void method called by CModelObject
   *
   * @return void
   */
  function loadRefModule() {
  }

  /**
   * Initialize all graph attribute with a specific start and end date
   *
   * @param string $startDate Start date
   * @param string $endDate   End date
   *
   * @return void
   * @throws Exception
   */
  function initGraph(string $startDate, string $endDate) {
    $this->start_date = $startDate;
    $this->end_date   = $endDate;
    $this->loadCommitList();
  }

  /**
   * Set $commitInfosList array with commit informations
   *
   * @return array
   *
   * @throws Exception
   */
  function loadCommitList(): array {

    $data = [];

    $startDate = $this->start_date;
    $endDate   = $this->end_date;

    $commit = new CGitlabCommit();
    $ds     = $commit->getDS();
    $where  = [];

    /* Authored date */
    if ($startDate && $endDate) {
      $where['gitlab_commit.authored_date'] = $ds->prepare('BETWEEN ?1 AND ?2', $startDate, $endDate);
    }
    elseif ($startDate) {
      $where['gitlab_commit.authored_date'] = $ds->prepare('> ?', $startDate);
    }
    elseif ($endDate) {
      $where['gitlab_commit.authored_date'] = $ds->prepare('< ?', $endDate);
    }

    $commits = $commit->loadList($where);

    /* Load users, branches and projects */
    CStoredObject::massLoadFwdRef($commits, 'ox_user_id');
    $branches   = CStoredObject::massLoadFwdRef($commits, 'ox_gitlab_branch_id');
    $projects   = CStoredObject::massLoadFwdRef($branches, 'ox_gitlab_project_id');

    /* loop over commits */
    foreach ($commits as $commit) {

      $commit->loadRefUser();

      $user_name = CAppUI::tr('common-undefined');
      if ($commit->_ref_user instanceof CMediusers && $commit->_ref_user->user_id) {
        $user_name = $commit->_ref_user->_view;
      }

      $branch_name = $project_name = CAppUI::tr('common-undefined');
      $branch      = $branches[$commit->ox_gitlab_branch_id];
      if ($branch instanceof CGitlabBranch && $branch->ox_gitlab_branch_id) {
        $branch_name = $branch->name;
        $project     = $projects[$branch->ox_gitlab_project_id];
        if ($project instanceof CGitlabProject && $branch->ox_gitlab_project_id) {
          $project_name = $project->name;
        }
      }

      $data[] = [
        "authored_date" => $commit->authored_date,
        "user_name"     => $user_name,
        "type"          => $commit->type,
        "branch_name"   => $branch_name,
        "project_name"  => $project_name,
      ];
    }

    return $this->commitInfosList = $data;
  }
}
