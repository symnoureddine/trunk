<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use DOMDocument;
use Exception;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;
use SimpleXMLElement;
use XMLReader;

/**
 * Class CGitlabJobClassReport
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabJobClassReport extends CMbObject
{
    /** @var int Primary key */
    public $ox_gitlab_job_class_report_id;

    /** @var int */
    public $ox_gitlab_job_tests_report_id;

    /** @var string */
    public $namespace;

    /** @var string */
    public $class;

    /** @var float */
    public $coverage;

    /** @var int */
    public $lines_covered;

    /** @var int */
    public $lines_all;

    /** @var CGitlabJob */
    public $_ref_gitlab_job_tests_report;

    /** @var float */
    public $_coverage_from = 0.0;

    /** @var float */
    public $_coverage_to = 100.0;

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_job_class_report";
        $spec->key      = "ox_gitlab_job_class_report_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                                  = parent::getProps();
        $props['ox_gitlab_job_tests_report_id'] = 'ref class|CGitlabJobTestsReport notNull'
            . ' back|gitlab_job_tests_classes_reports autocomplete|_view cascade';
        $props['namespace']                     = 'str notNull';
        $props['class']                         = 'str';
        $props['coverage']                      = 'float notNull default|0.0';
        $props['lines_covered']                 = 'num notNull default|0';
        $props['lines_all']                     = 'num notNull default|0';
        $props['_coverage_from']                = 'float default|0.0 min|0 max|100';
        $props['_coverage_to']                  = 'float default|100.0 min|0 max|100';

        return $props;
    }

    /**
     * Loads the linked Gitlab job tests report object
     *
     * @return CGitlabJobTestsReport|CStoredObject|null
     * @throws Exception
     */
    public function loadRefGitlabJobTestsReport(): ?CGitlabJobTestsReport
    {
        return $this->_ref_gitlab_job_tests_report = $this->loadFwdRef('ox_gitlab_job_tests_report_id');
    }

    /**
     * @param CGitlabJobTestsReport $job_tests_report
     * @param string|null           $resource
     *
     * @return CGitlabJobClassReport[]|null
     * @throws Exception
     */
    public static function generateClassesReports(
        CGitlabJobTestsReport $job_tests_report,
        ?string $resource
    ): ?array {

        if (empty($resource)) {
            return null;
        }

        $namespaces_coverage_data = [];
        $reports                  = [];

        /*
        $data_sample[] = [
            'namespace'     => 'Ox\Sample',
            'coverage'      => 0,
            'lines_covered' => 0,
            'lines_all'     => 0
        ];
        */

        $xml = new XMLReader();
        $doc = new DOMDocument();

        $xml->XML($resource);
        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'class') {
                $classname = $xml->getAttribute('name');
                $group     = self::getNamespaceGroup($classname);

                /* Ignore class if its namespace does not contains at least 2 levels
                * (also if namespace is not defined in class) */
                if (count(explode('\\', $group)) <= 1) {
                    continue;
                }

                $class     = simplexml_import_dom($doc->importNode($xml->expand(), true));
                $metrics   = $class->metrics[0]->attributes();

                $elements         = (int) $metrics['elements'];
                $covered_elements = (int) $metrics['coveredelements'];

                if (!array_key_exists($group, $namespaces_coverage_data)) {
                    $namespaces_coverage_data[$group] = [
                        'namespace'     => $group,
                        'lines_all'         => $elements,
                        'lines_covered' => $covered_elements,
                    ];
                } else {
                    $namespaces_coverage_data[$group]['lines_all']     += $elements;
                    $namespaces_coverage_data[$group]['lines_covered'] += $covered_elements;
                }
            }
        }

        foreach ($namespaces_coverage_data as $namespace_coverage_data) {
            /* Calculate coverage ratio (avois calculating within template if simple database extraction is needed) */
            $namespace_coverage_data['coverage'] = $namespace_coverage_data['lines_all'] > 0 ? round(
                100 * $namespace_coverage_data['lines_covered'] / $namespace_coverage_data['lines_all'],
                2
            ) : 0;

            /* Persist data */
            $report = new CGitlabJobClassReport();
            $report->ox_gitlab_job_tests_report_id = $job_tests_report->ox_gitlab_job_tests_report_id;
            $report->bind($namespace_coverage_data, false);
            if ($msg = $report->store()) {
                throw new CMbException("CGitlabJobClassReport-error-cannot_be_created", $msg);
            } else {
                $reports[] = $report;
            }
        }

        return $reports;
    }

    /**
     * @param string $class_namespace
     *
     * @return string
     */
    public static function getNamespaceGroup(string $class_namespace): string
    {
        return implode(
            '\\',
            array_slice(
                explode(
                    '\\',
                    $class_namespace
                ),
                0,
                2
            )
        );
    }
}
