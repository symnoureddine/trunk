<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use DateTime;
use Exception;
use Ox\Core\CMbDT;
use Ox\Core\CMbModelNotFoundException;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;
use Ox\Erp\Tasking\CTaskingBranch;

/**
 * Class CGitlabBranch
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabBranch extends CMbObject
{

    const OX_RELEASE_NAME_PATTERN = '^release\/(?P<year>\d{4})_(?P<month>\d{2})$';

    /** @var int Primary key */
    public $ox_gitlab_branch_id;

    /** @var int */
    public $ox_gitlab_project_id;

    /** @var string */
    public $name;

    /** @var string */
    public $web_url;

    /** @var CGitlabProject */
    public $_ref_gitlab_project;

    /** @var CGitlabCommit */
    public $_ref_commits;

    /** @var string */
    public $_readable_title;

    /** @var string */
    public $_code;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_branch";
        $spec->key      = "ox_gitlab_branch_id";
        $spec->loggable = false;

        $spec->uniques['branch'] = ['ox_gitlab_project_id', 'name'];

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                         = parent::getProps();
        $props['ox_gitlab_project_id'] = 'ref class|CGitlabProject notNull back|gitlab_project_branches autocomplete|_view';
        $props['name']                 = 'str notNull seekable';
        $props['web_url']              = 'str';

        return $props;
    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function updateFormFields()
    {
        parent::updateFormFields();

        $this->_view = $this->getReadableTitle();
        $this->_code = $this->getBranchCode();
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function store(): ?string
    {
        /** On first store, update the existing tasking branch real release date for Mediboard project */
        if (!$this->_id && CGitlabProject::getMediboardProject()->_id === $this->ox_gitlab_project_id) {
            $tasking_branch              = new CTaskingBranch();
            $tasking_branch->branch_code = $this->getBranchCode();
            $tasking_branch->loadMatchingObject();

            // We do not check if branch exists anymore, in order to create it otherwise.

            /* The datetime depends on when the branches are loaded from API
                *  No real creation date can be extracted from API */
            $tasking_branch->branch_real_release_date = CMbDT::dateTime();
            $tasking_branch->store();
        }

        return parent::store();
    }

    /**
     * Loads the linked Gitlab project object
     *
     * @return CGitlabProject|CStoredObject
     * @throws Exception
     */
    public function loadRefGitlabProject(): CGitlabProject
    {
        if (
            $this->_ref_gitlab_project instanceof CGitlabProject
            && !empty($this->_ref_gitlab_project->ox_gitlab_project_id)
        ) {
            return $this->_ref_gitlab_project;
        }

        return $this->_ref_gitlab_project = $this->loadFwdRef('ox_gitlab_project_id');
    }

    /**
     * Load all the commits on current branch
     *
     * @return CGitlabCommit[]|CStoredObject[]
     * @throws Exception
     */
    public function loadRefCommits()
    {
        return $this->_ref_commits = $this->loadBackRefs("gitlab_branch_commits");
    }

    /**
     * Returns if the current branch is owned by the target CGitlabProject
     *
     * @param CGitlabProject $project
     *
     * @return bool
     */
    public function hasProject(CGitlabProject $project): bool
    {
        return $this->ox_gitlab_project_id === $project->ox_gitlab_project_id;
    }

    /**
     * Returns a readable title of the branch
     *
     * @param bool $include_project_title
     *
     * @return string
     * @throws Exception
     */
    public function getReadableTitle(bool $include_project_title = false): string
    {
        if (preg_match('/' . self::OX_RELEASE_NAME_PATTERN . '/', $this->name, $result)) {
            $readable_title = "";
            $date           = DateTime::createFromFormat(
                'd-m-Y',
                '01-' . intval($result['month']) . '-' . intval($result['year'])
            );

            if (true === $include_project_title) {
                $project = $this->loadRefGitlabProject();
                if (!empty($project->ox_gitlab_project_id)) {
                    $readable_title .= ucfirst($project->name . ' - ');
                }
            }

            $readable_title .= ucfirst(CMbDT::format($date->format('d-m-Y'), '%B %Y'));

            return $this->_readable_title = $readable_title;
        }

        return $this->_readable_title = $this->name;
    }

    /**
     * Returns the branch code
     *
     * @return string
     */
    public function getBranchCode(): string
    {
        if (preg_match('/' . self::OX_RELEASE_NAME_PATTERN . '/', $this->name, $result)) {
            return $this->_code = $result['year'] . '_' . $result['month'];
        }

        return $this->_code = $this->name;
    }

    /**
     * @param CGitlabBranch[] $branches
     *
     * @return CGitlabBranch[]
     */
    public static function orderBranches(array $branches): array
    {
        usort($branches, [self::class, "compare"]);

        return $branches;
    }

    /**
     * @param CGitlabBranch $branch_from
     * @param CGitlabBranch $branch_to
     *
     * @return int
     */
    public function compare(CGitlabBranch $branch_from, CGitlabBranch $branch_to): int
    {
        if ($branch_from->name === CGitlabManager::DEFAULT_BRANCH) {
            return -1;
        }
        if ($branch_to->name === CGitlabManager::DEFAULT_BRANCH) {
            return 1;
        }

        return strcmp($branch_to->name, $branch_from->name);
    }

    public static function getMasterBranch(CGitlabProject $project): self
    {
        if (!$project || !$project->_id) {
            throw new CMbModelNotFoundException('common-error-Object not found');
        }

        $branch                       = new self();
        $branch->ox_gitlab_project_id = $project->_id;
        $branch->name                 = CGitlabManager::DEFAULT_BRANCH;

        if ($branch->loadMatchingObjectEsc()) {
            return $branch;
        }

        throw new CMbModelNotFoundException('common-error-Object not found');
    }
}
