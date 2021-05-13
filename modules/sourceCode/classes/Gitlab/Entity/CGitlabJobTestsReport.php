<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use Exception;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;
use Ox\Erp\SourceCode\PhpUnitTextCoverageParser;

/**
 * Class CGitlabJobTestsReport
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabJobTestsReport extends CMbObject
{
    public const COVERAGE_HTML_FILE = 'tmp/html_coverage';

    /** @var int Primary key */
    public $ox_gitlab_job_tests_report_id;

    /** @var int */
    public $ox_gitlab_job_id;

    /** @var int */
    public $tests;

    /** @var int */
    public $assertions;

    /** @var int */
    public $warnings;

    /** @var int */
    public $failures;

    /** @var int */
    public $errors;

    /** @var int */
    public $skipped;

    /** @var int */
    public $incomplete;

    /** @var int */
    public $risky;

    /** @var float */
    public $classes_ratio;

    /** @var int */
    public $classes_covered;

    /** @var int */
    public $classes_all;

    /** @var float */
    public $methods_ratio;

    /** @var int */
    public $methods_covered;

    /** @var int */
    public $methods_all;

    /** @var float */
    public $lines_ratio;

    /** @var int */
    public $lines_covered;

    /** @var int */
    public $lines_all;

    /** @var CGitlabJob */
    public $_ref_gitlab_job;

    /** @var CGitlabJobClassReport[] */
    public $_ref_gitlab_job_class_reports;

    /** @var string */
    public $_coverage_html_link;

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_job_tests_report";
        $spec->key      = "ox_gitlab_job_tests_report_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                     = parent::getProps();
        $props['ox_gitlab_job_id'] = 'ref class|CGitlabJob notNull'
            . ' back|gitlab_job_tests_reports autocomplete|_view cascade';
        $props['tests']            = 'num notNull default|0';
        $props['assertions']       = 'num notNull default|0';
        $props['warnings']         = 'num notNull default|0';
        $props['failures']         = 'num notNull default|0';
        $props['errors']           = 'num notNull default|0';
        $props['skipped']          = 'num notNull default|0';
        $props['incomplete']       = 'num notNull default|0';
        $props['risky']            = 'num notNull default|0';
        $props['classes_ratio']    = 'float notNull default|0.0';
        $props['classes_covered']  = 'num notNull default|0';
        $props['classes_all']      = 'num notNull default|0';
        $props['methods_ratio']    = 'float notNull default|0.0';
        $props['methods_covered']  = 'num notNull default|0';
        $props['methods_all']      = 'num notNull default|0';
        $props['lines_ratio']      = 'float notNull default|0.0';
        $props['lines_covered']    = 'num notNull default|0';
        $props['lines_all']        = 'num notNull default|0';

        return $props;
    }

    /**
     * Loads the linked Gitlab job object
     *
     * @return CGitlabJob|CStoredObject|null
     * @throws Exception
     */
    public function loadRefGitlabJob(): ?CGitlabJob
    {
        return $this->_ref_gitlab_job = $this->loadFwdRef('ox_gitlab_job_id');
    }

    /**
     * Loads the linked Gitlab job classes reports
     *
     * @return CGitlabJobClassReport|CStoredObject|null
     * @throws Exception
     */
    public function loadRefGitlabJobClassReports(): ?array
    {
        return $this->_ref_gitlab_job_class_reports = $this->loadBackRefs('gitlab_job_tests_classes_reports');
    }

    /**
     * @param CGitlabProject $project ,
     * @param CGitlabJob     $job
     * @param string         $resource
     *
     * @return CGitlabJobTestsReport|null
     * @throws CMbException
     * @throws Exception
     */
    public static function generateTestsReport(
        CGitlabProject $project,
        CGitlabJob $job,
        string $resource
    ): ?CGitlabJobTestsReport {
        $tests_summary_data    = CGitlabJob::getUnitTestsSummaryDataFromTrace($resource);
        $coverage_summary_data = CGitlabJob::getCoverageSummaryDataFromTrace($resource);

        if (empty($tests_summary_data) && empty($coverage_summary_data)) {
            return null;
        }

        $data = array_merge(
            $tests_summary_data,
            $coverage_summary_data
        );

        $report = new CGitlabJobTestsReport();
        $report->bind($data);
        $report->ox_gitlab_job_id = $job->ox_gitlab_job_id;

        /* Persist data */
        if ($msg = $report->store()) {
            throw new CMbException("CGitlabJobTestsReport-error-cannot_be_created", $msg);
        }

        /* Coverage per class / namespace report storage */
        CGitlabJobClassReport::generateClassesReports(
            $report,
            CGitlabManager::importJobCoverageXml($project, $job)
        );

        return $report;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function generateCoverageHtmlLink(): ?string
    {
        $job = $this->loadRefGitlabJob();
        if ($job instanceof CGitlabJob && $job->id) {
            $project = $job->loadRefGitlabPipeline()->loadRefGitlabProject();
            if ($project instanceof CGitlabProject && $project->name) {
                return $this->_coverage_html_link = CGitlabManager::IO_URL . '/-/'
                    . $project->name . '/-/jobs/' . $job->id
                    . '/artifacts/' . self::COVERAGE_HTML_FILE . '/index.html';
            }
        }
        return null;
    }
}
