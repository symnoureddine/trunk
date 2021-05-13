<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use Exception;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

/**
 * Class CGitlabPipeline
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabPipeline extends CMbObject
{
    public const API_IMPORT_START_DATE = '2021-04-01 00:00:00';
    public const API_PAGE_LIMIT        = 25;
    public const ADMIN_USER_NAME       = 'admin';
    public const SCOPE_FINISHED        = 'finished';
    public const STATUS_SUCCESS        = 'success';
    public const STATUS_FAILED         = 'failed';
    public const STATUS_CANCELED       = 'canceled';
    public const STATUS_RUNNING        = 'running';
    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];

    /** @var int Primary key */
    public $ox_gitlab_pipeline_id;

    /** @var int */
    public $ox_gitlab_project_id;

    /** @var int */
    public $id;

    /** @var string */
    public $status;

    /** @var string */
    public $ref;

    /** @var string */
    public $sha;

    /** @var string */
    public $tag;

    /** @var string */
    public $created_at;

    /** @var string */
    public $finished_at;

    /** @var string */
    public $coverage;

    /** @var string */
    public $duration;

    /** @var string */
    public $web_url;

    /** @var CGitlabProject */
    public $_ref_gitlab_project;

    /** @var CGitlabJob[] */
    public $_ref_gitlab_jobs;

    /** @var array */
    public $_statuses_list;

    /** @var CGitlabJobTestsReport|null */
    public $_report;

    /** @var string */
    public $_hr_duration;

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_pipeline";
        $spec->key      = "ox_gitlab_pipeline_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                         = parent::getProps();
        $props['ox_gitlab_project_id'] = 'ref class|CGitlabProject notNull back|gitlab_project_pipelines autocomplete|_view';
        $props['id']                   = 'num notNull';
        $props['status']               = 'str notNull';
        $props['ref']                  = 'str notNull seekable';
        $props['sha']                  = 'str notNull minLength|7 maxLength|40 seekable';
        $props['tag']                  = 'str';
        $props['created_at']           = 'dateTime notNull';
        $props['finished_at']          = 'dateTime';
        $props['duration']             = 'num default|0';
        $props['coverage']             = 'float';
        $props['web_url']              = 'str';
        $props['_statuses_list']       = 'set list|' . implode('|', self::STATUSES);
        $props['_hr_duration']         = 'str';

        return $props;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function updateFormFields(): void
    {
        parent::updateFormFields();

        $this->_hr_duration = gmdate("H:i:s", $this->duration);
    }

    /**
     * Formats data from Gitlab Pipeline API resource to valid object specs data
     *
     * @param array $resource
     *
     * @return array
     * @throws Exception
     */
    public static function formatResource(array $resource): array
    {
        if (array_key_exists('created_at', $resource)) {
            $resource['created_at'] = CGitlabManager::convertGitlabDate($resource['created_at']);
        }
        if (array_key_exists('finished_at', $resource)) {
            $resource['finished_at'] = CGitlabManager::convertGitlabDate($resource['finished_at']);
        }
        return $resource;
    }

    /**
     * Loads the linked Gitlab project object
     *
     * @return CGitlabProject|CStoredObject|null
     * @throws Exception
     */
    public function loadRefGitlabProject(): ?CGitlabProject
    {
        return $this->_ref_gitlab_project = $this->loadFwdRef('ox_gitlab_project_id');
    }

    /**
     * Loads the linked Gitlab pipeline jobs
     *
     * @return CGitlabJob[]|CStoredObject[]|null
     * @throws Exception
     */
    public function loadRefGitlabJobs(): ?array
    {
        return $this->_ref_gitlab_jobs = $this->loadBackRefs('gitlab_pipeline_jobs');
    }

    /**
     * @return CGitlabJobTestsReport|null
     * @throws Exception
     */
    public function loadJobTestsReport(): ?CGitlabJobTestsReport
    {
        $jobs = $this->loadRefGitlabJobs();
        foreach ($jobs as $job) {
            $reports = $job->loadRefGitlabJobTestsReports();
            foreach ($reports as $report) {
                $report->generateCoverageHtmlLink();
                $report->loadRefGitlabJob();
                /* Return only the first job return found (for now) */
                return $this->_report = $report;
            }
        }

        return null;
    }
}
