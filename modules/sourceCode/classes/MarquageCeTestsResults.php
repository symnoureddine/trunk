<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use DateTime;
use Ox\Core\CMbException;
use Ox\Core\HttpClient\Client;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceHTTP;
use SimpleXMLElement;


/**
 * Helper to get tests results on :
 *      - project mediboard
 *      - branch master
 *      - success schedules pipelines
 *      - jobs marquage_ce
 */
class MarquageCeTestsResults
{
    const JOB_NAME = 'test:marquage_ce';
    const ARTIFACT_PATH = 'tmp/junit.xml';

    private $api_client;

    public function __construct()
    {
        $source = CExchangeSource::get('gitlab_api', CSourceHTTP::TYPE);
        if (!$source->_id) {
            throw new CMbException('CExchangeSource-error-Missing source');
        }

        // client
        $client = new Client($source);
        $this->api_client = new CGitLabApiClient($client);
    }


    public function getJobs(DateTime $since, ?DateTime $until = null)
    {
        // Schedules pipelines
        $schedules_pipelines = $this->api_client->getSchedulesPipelines(
            CGitLabApiClient::MEDIBOARD_PROJECT_ID,
            CGitLabApiClient::DEFAULT_BRANCH,
            $since,
            $until
        );

        // Jobs
        $jobs = [];
        foreach ($schedules_pipelines as $pipeline) {
            $job = $this->api_client->getJobs(
                CGitLabApiClient::MEDIBOARD_PROJECT_ID,
                $pipeline['id'],
                true,
                self::JOB_NAME
            );

            if (!isset($job['id'])) {
                continue;
            }

            $jobs[] = [
                'id' => $job['id'],
                'status' => $job['status'],
                'created_at' => $job['created_at'],
                'started_at' => $job['started_at'],
                'finished_at' => $job['finished_at'],
                'duration' => $job['duration'],
                'pipeline_id' => $job['pipeline']['id'],
                'web_url' => $job['web_url'],
            ];
        }

        return $jobs;
    }

    public function getJobResults(int $job_id)
    {
        try {
            $artifact = $this->api_client->getJobArtifact(
                CGitLabApiClient::MEDIBOARD_PROJECT_ID,
                $job_id,
                self::ARTIFACT_PATH,
                true
            );
        } catch (CMbException $e) {
            return [];
        }

        try {
            $xml = simplexml_load_string($artifact);
        } catch (\Exception $exception) {
            trigger_error("Invalid job artifact", E_USER_WARNING);
        }


        return $this->parseJunit($xml);
    }

    private function parseJunit(SimpleXMLElement $xml)
    {
        $results = [];
        $project = $xml->testsuite->testsuite;
        foreach ($project->testsuite as $testsuite) {
            $testsuite_name = (string)$testsuite['name'];

            $results[$testsuite_name] = [
                'tests' => (int)$testsuite['tests'],
                'assertions' => (int)$testsuite['assertions'],
                'errors' => (int)$testsuite['errors'],
                'warnings' => (int)$testsuite['warnings'],
                'failures' => (int)$testsuite['failures'],
                'skipped' => (int)$testsuite['skipped'],
                'time' => (string)$testsuite['time'],
                'testcase' => [],
            ];

            foreach ($testsuite->testcase as $testcase) {
                $testcase_name = (string)$testcase['name'];

                $results[$testsuite_name]['testcase'][$testcase_name] = [
                    'line' => (int)$testcase['line'],
                    'assertions' => (int)$testcase['assertions'],
                    'time' => (string)$testcase['time'],
                ];
            }
        }
        return $results;
    }


    public function getChronoReport()
    {
        return $this->api_client->getChronoReport();
    }

}
