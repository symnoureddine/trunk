<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use Exception;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

/**
 * Class CGitlabJob
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabJob extends CMbObject
{
    public const SCOPE_SUCCESS    = 'success';
    public const SCOPE_FAILED     = 'failed';
    public const IMPORT_JOB_NAMES = [
        'test:phpunit_schedules',
        'schedules:phpunit',
        'test:phpunit',
    ];
    public const STATUS_SUCCESS        = 'success';
    public const STATUS_FAILED         = 'failed';
    public const STATUS_CANCELED       = 'canceled';
    public const STATUS_RUNNING        = 'running';
    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];
    public const ARTIFACT_DATA_UNIT_TESTS   = 'tmp/unit_testsuite_junit.xml';
    public const ARTIFACT_DATA_COVERAGE_XML = 'tmp/coverage.xml';

    /** @var int Primary key */
    public $ox_gitlab_job_id;

    /** @var int */
    public $ox_gitlab_pipeline_id;

    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $stage;

    /** @var string */
    public $status;

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

    /** @var CGitlabPipeline */
    public $_ref_gitlab_pipeline;

    /** @var CGitlabJobTestsReport[] */
    public $_ref_gitlab_job_tests_reports;

    /** @var array */
    public $_statuses_list;

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_job";
        $spec->key      = "ox_gitlab_job_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                          = parent::getProps();
        $props['ox_gitlab_pipeline_id'] = 'ref class|CGitlabPipeline notNull'
            . ' back|gitlab_pipeline_jobs autocomplete|_view cascade';
        $props['id']                    = 'num notNull';
        $props['status']                = 'str notNull';
        $props['name']                  = 'str notNull';
        $props['stage']                 = 'str notNull';
        $props['created_at']            = 'dateTime notNull';
        $props['finished_at']           = 'dateTime';
        $props['duration']              = 'num notNull';
        $props['coverage']              = 'float';
        $props['tag']                   = 'str';
        $props['web_url']               = 'str';
        $props['_statuses_list']        = 'set list|' . implode('|', self::STATUSES);

        return $props;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function updateFormFields(): void
    {
        parent::updateFormFields();
    }

    /**
     * Loads the linked Gitlab pipeline object
     *
     * @return CGitlabPipeline|CStoredObject|null
     * @throws Exception
     */
    public function loadRefGitlabPipeline(): ?CGitlabPipeline
    {
        return $this->_ref_gitlab_pipeline = $this->loadFwdRef('ox_gitlab_pipeline_id');
    }

    /**
     * Loads the linked Gitlab job tests report
     *
     * @return CGitlabJobTestsReport[]|array|null
     * @throws Exception
     */
    public function loadRefGitlabJobTestsReports(): ?array
    {
        return $this->_ref_gitlab_job_tests_reports = $this->loadBackRefs('gitlab_job_tests_reports');
    }

    /**
     * Formats data from Gitlab Jobs API resource to valid object specs data
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
     * Returns an array of coverage data per php class from pipeline job trace.log
     *
     * @sample [
     *    "namespace" => "\Ox\Api"
     *    "class" => "Ox\Api\CConfigurationAPI"
     *    "coverage" => "100.00"
     *    "lines_covered" => "3"
     *    "lines_all" => "3"
     * ]
     *
     * @param string $trace
     *
     * @return array|null
     */
    public static function getCoverageFullDataFromTrace(string $trace): ?array
    {
        $pattern = '/^(?<namespace>\\\\Ox\\\\.*)::(?<class>.*)\n.*'
            . 'Lines:\s+(?<coverage>.*)%\s+\(\s+(?<lines_covered>\d+)\/(\s+)?(?<lines_all>\d+)\)/m';
        preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);

        if (!is_array($matches)) {
            return null;
        }

        $results = [];
        foreach ($matches as $match) {
            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            if (is_array($match)) {
                $results[] = $match;
            }
        }

        return $results;
    }

    /**
     * Returns an array of coverage summary from pipeline job trace
     *
     * @param string $trace
     *
     * @return array
     */
    public static function getCoverageSummaryDataFromTrace(string $trace): array
    {
        $pattern = '/^\s+Code Coverage Report Summary:\s+\n\s+'
            . 'Classes:\s+(?<classes_ratio>\d+.\d+)%\s+\((?<classes_covered>\d+)\/(?<classes_all>\d+)\)\s+'
            . 'Methods:\s+(?<methods_ratio>\d+.\d+)%\s+\((?<methods_covered>\d+)\/(?<methods_all>\d+)\)\s+'
            . 'Lines:\s+(?<lines_ratio>\d+.\d+)%\s+\((?<lines_covered>\d+)\/(?<lines_all>\d+)\)$/m';
        preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);

        if (!is_array($matches)) {
            return [];
        }

        foreach ($matches as $match) {
            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            if (is_array($match)) {
                return $match;
            }
        }

        return [];
    }

    /**
     * Returns an array of unit tests summary data from pipeline job trace.log
     *
     * @sample [
     *    "tests"      => 999
     *    "assertions" => 9999
     *    "warnings"   => 1
     *    "failures"   => 0
     *    "skipped"    => 9
     *    "incomplete" => 2
     *    "risky"      => 1
     * ]
     *
     * @param string $trace
     *
     * @return array
     */
    public static function getUnitTestsSummaryDataFromTrace(string $trace): array
    {
        /* Failed tests display a complete summary */
        $pattern = '/^Tests:\s+(?<tests>\d+),\s+Assertions:\s+(?<assertions>\d+)'
            . '?(,\s+Warnings:\s+(?<warnings>\d+))?'
            . '(,\s+Failures:\s+(?<failures>\d+))?(,\s+Errors:\s+(?<errors>\d+))?'
            . '(,\s+Skipped:\s+(?<skipped>\d+))?(,\s+Incomplete:\s+(?<incomplete>\d+))?'
            . '(,\s+Risky:\s+(?<risky>\d+))?\.$/m';
        preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);

        if (empty($matches)) {
            /* Successful tests display a very lightweight summary */
            $pattern = '/^OK\s+\((?<tests>\d+)\s+tests,\s+(?<assertions>\d+)\s+assertions\)$/m';
            preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);

            if (empty($matches)) {
                return [];
            }
        }

        foreach ($matches as $match) {
            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            if (is_array($match)) {
                return $match;
            }
        }

        return [];
    }
}
