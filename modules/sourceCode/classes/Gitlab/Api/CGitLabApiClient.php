<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Api;

use DateInterval;
use DateTime;
use Exception;
use Ox\Core\Chronometer;
use Ox\Core\CMbException;
use Ox\Core\HttpClient\Client;
use Ox\Core\HttpClient\ClientException;
use Ox\Core\HttpClient\Response;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabJob;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabPipeline;

/**
 * Class CGitLabApiClient
 *
 * @package Ox\Erp\SourceCode
 */
class CGitLabApiClient
{

    public const  MEDIBOARD_PROJECT_ID = '13736756';
    public const  JOB_NAME_TU = 'test:phpunit_schedules';
    public const  JOB_NAME_TF = 'schedules:selenium';
    public const  JOB_NAME_DEPLOY = 'deploy';
    public const  ENDPOINT_WEB = 'https://gitlab.com';
    public const  ENDPOINT_RUNNER = 'http://51.75.250.48';
    public const  ADMIN_NAME = 'admin';
    public const  RATE_LIMIT_REACHED_HTTP_CODE = 429;
    public const  NOT_FOUND_HTTP_CODE = 404;
    private const DEFAULT_TIMEOUT = 10;
    public const DEFAULT_BRANCH = 'master';

    /** @var Chronometer */
    private $chrono;

    /** @var Client $client */
    private $client;

    /** @var string $token */
    private $token;

    /** @var bool $debug */
    private $debug;

    /**
     * CGitLab constructor.
     *
     * @param Client $client
     * @param bool $debug
     */
    public function __construct(Client $client, bool $debug = false)
    {
        $this->client = $client;
        $this->client->setOptions(
            [
                'timeout' => self::DEFAULT_TIMEOUT,
            ]
        );
        $this->debug = $debug;
        $this->token = $client->getSourceHttp()->getToken();
        $this->chrono = new Chronometer();
    }

    /**
     * Get a list of all visible projects across GitLab for the authenticated user
     *
     * @return Response
     */
    public function getProjects(): Response
    {
        $path = 'projects';
        $path .= '?' . http_build_query(
                [
                    'visibility' => 'private',
                    'simple' => true,
                    'per_page' => 100,
                ]
            );

        return $this->request(Client::METHOD_GET, $path);
    }

    /**
     * @param integer $project_id
     *
     * @return Response
     */
    public function getProject(int $project_id): Response
    {
        return $this->request(Client::METHOD_GET, "projects/{$project_id}");
    }

    /**
     * Gets the list of branches names from a project, filtered with the search query parameter
     *
     * @param integer $project_id
     * @param string $search
     *
     * @return Response
     */
    public function getBranches(int $project_id, string $search): Response
    {
        $path = 'projects/' . $project_id . '/repository/branches';
        $path .= '?' . http_build_query(
                [
                    'search' => $search,
                ]
            );

        return $this->request(Client::METHOD_GET, $path);
    }

    /**
     * Gets the list of commits names from a project and branch
     *
     * @param int $project_id
     * @param string $branch_name
     * @param int $page
     * @param int $limit
     *
     * @return Response
     */
    public function getCommits(
        int $project_id,
        string $branch_name,
        int $page = 1,
        int $limit = CGitlabCommit::API_PAGE_LIMIT
    ): Response
    {
        $path = 'projects/' . $project_id . '/repository/commits';
        $path .= '?' . http_build_query(
                [
                    'id' => $project_id,
                    'ref_name' => $branch_name,
                    'page' => $page,
                    'per_page' => $limit,
                    'first_parent' => true,
                ]
            );

        return $this->request(Client::METHOD_GET, $path);
    }

    /**
     * Gets the list of pipelines from a project and branch
     *
     * @param int $project_id
     * @param string $branch_name
     * @param int $page
     * @param int $limit
     * @param array $params
     *
     * @return Response
     * @throws Exception
     */
    public function getPipelines(
        int $project_id,
        string $branch_name,
        int $page = 1,
        int $limit = CGitlabPipeline::API_PAGE_LIMIT,
        array $params = []
    ): Response
    {
        $path = 'projects/' . $project_id . '/pipelines/';
        $path .= '?' . http_build_query(
                array_merge(
                    [
                        'ref' => $branch_name,
                        'page' => $page,
                        'per_page' => $limit,
                    ],
                    $params
                )
            );

        return $this->request(Client::METHOD_GET, $path);
    }


    public function getSchedulesPipelines(
        int $project_id,
        string $branch,
        DateTime $since,
        ?DateTime $until = null,
        bool $only_success = true
    ): array
    {
        $schedules_pipelines = [];
        $until = $until ?: new DateTime();
        $status = $only_success ? CGitlabPipeline::STATUS_SUCCESS : null;
        $per_page = 25;

        $page = 1;
        while ($page) {
            $response = $this->getPipelines(
                $project_id,
                $branch,
                $page,
                CGitlabPipeline::API_PAGE_LIMIT,
                [
                    'name' => CGitlabPipeline::ADMIN_USER_NAME,
                    'scope' => CGitlabPipeline::SCOPE_FINISHED,
                    'status' => $status,
                    'per_page' => $per_page,
                    'updated_after' => $since->format(DateTime::ATOM),
                    'updated_before' => $until->format(DateTime::ATOM),
                ]
            );
            $resources = $response->getBody();

            if (($response->getStatusCode() === 200) && (count($resources) === $per_page)) {
                $schedules_pipelines = array_merge($schedules_pipelines, $resources);
                $page++;
            } else {
                $page = false;
            }
        }

        return $schedules_pipelines;
    }

    /**
     * Gets a single pipeline data from a project and pipeline
     *
     * @param int $project_id
     * @param int $pipeline_id
     *
     * @return Response
     */
    public function getPipeline(int $project_id, int $pipeline_id): Response
    {
        $path = 'projects/' . $project_id . '/pipelines/' . $pipeline_id;

        return $this->request(Client::METHOD_GET, $path);
    }

    /**
     * Gets a single pipeline jobs data
     *
     * @param int $project_id
     * @param int $pipeline_id
     * @param bool $finished
     * @param string $job_name
     *
     * @return Mixed
     */
    public function getJobs(int $project_id, int $pipeline_id, bool $finished = true, ?string $job_name = null)
    {
        $path = 'projects/' . $project_id . '/pipelines/' . $pipeline_id . '/jobs/';
        if (true === $finished) {
            /* @see https://docs.gitlab.com/ee/api/jobs.html e.g. not using http_build_query() */
            $path .= '?' . 'scope[]=' . CGitlabJob::SCOPE_SUCCESS;
            $path .= '&' . 'scope[]=' . CGitlabJob::SCOPE_FAILED;
        }

        /** @var Response $response */
        $response = $this->request(Client::METHOD_GET, $path);

        if ($response->getStatusCode() === 200 && $job_name) {
            $jobs = $response->getBody();
            foreach ($jobs as $job_key => $job) {
                if ($job['name'] !== $job_name) {
                    unset($jobs[$job_key]);
                }
            }

            return reset($jobs);
        }

        return $response;
    }


    /**
     * Gets a pipeline job artifact file
     *
     * @param int $project_id
     * @param int $job_id
     * @param string $artifact_path
     * @param bool $return_content
     *
     * @return Mixed
     * @throws CMbException
     */
    public function getJobArtifact(
        int $project_id,
        int $job_id,
        string $artifact_path = '',
        bool $return_content = false
    )
    {
        $path = 'projects/' . $project_id
            . '/jobs/' . $job_id
            . '/artifacts/' . $artifact_path;

        $response = $this->request(Client::METHOD_GET, $path);

        if ($return_content) {
            if ($response->getStatusCode() !== 200) {
                throw new CMbException('Failed to download artifact (' . $response->getStatusCode() . ')');
            }
            return $response->getBody();
        }

        return $response;
    }

    /**
     * Gets a pipeline job trace (standard output)
     *
     * @param int $project_id
     * @param int $job_id
     *
     * @return Response
     */
    public function getJobTrace(
        int $project_id,
        int $job_id
    ): Response
    {
        $path = 'projects/' . $project_id . '/jobs/' . $job_id . '/trace/';

        return $this->request(Client::METHOD_GET, $path);
    }

    /**
     * @todo : from here to the end of file
     *       - Keep all API calls within this class
     *       - Export all data serialization to CGitlabManager or dedicated class
     *       - Allow to pass project_id and branch_id to target any project/branch
     */

    /**
     * @param string $interval
     *
     * @return void
     * @throws Exception
     */
    public function clearOldPipeline(string $interval): void
    {
        $date_limit = new DateTime();
        $date_limit->sub(new DateInterval($interval));

        try {
            $this->recursiveDeletePipelines($date_limit);
        } catch (Exception $e) {
            if ($this->debug) {
                dump($e);
            }
        }
    }

    /**
     * @param string $date_limit
     *
     * @return bool
     * @throws Exception
     * @throws ClientException
     */
    private function recursiveDeletePipelines(string $date_limit): bool
    {
        $response = $this->request(
            Client::METHOD_GET,
            "projects/" . self::MEDIBOARD_PROJECT_ID . "/pipelines?sort=asc"
        );

        $body = json_decode($response->getBody(), true);

        if (empty($body)) {
            if ($this->debug) {
                dump('No pipeline to delete');
            }

            return true;
        }

        foreach ($body as $key => $pipeline) {
            $pipeline = (object)$pipeline;
            $date = new DateTime($pipeline->created_at);
            if ($date >= $date_limit) {
                throw new Exception('No more pipeline to delete');
            }

            $response = $this->request(
                Client::METHOD_DELETE,
                "projects/" . self::MEDIBOARD_PROJECT_ID . "/pipelines/{$pipeline->id}"
            );

            if ($this->debug) {
                dump($pipeline->id, $date, $response->getStatusCode());
            }
        }

        $this->recursiveDeletePipelines($date_limit);

        return false;
    }

    /**
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $since
     * @param DateTime $until
     *
     * @return array
     * @throws Exception
     */
    public function getInfosRepository(
        int $project_id,
        string $branch_name,
        DateTime $since,
        DateTime $until
    ): array
    {
        $infos = [
            'commits' => 0,
            'commits_average' => 0,
            'authors' => [],
        ];

        $path = 'projects/' . $project_id . '/repository/commits';
        $path .= '?' . http_build_query(
                [
                    'id' => $project_id,
                    'ref_name' => $branch_name,
                    'per_page' => 100,
                    'since' => $since->format(DateTime::ATOM),
                    'until' => $until->format(DateTime::ATOM),
                ]
            );

        $response = $this->request(Client::METHOD_GET, $path);
        $commits = $response->getBody();

        $next_page = $response->getHeader('X-Next-Page');

        // todo ref recursively (X-Total-Pages is missing on response.headers)
        if (!empty($next_page)) {
            $i = end($next_page);
            $new_response = $this->request(Client::METHOD_GET, $path . '&page=' . $i);
            $commits = array_merge($commits, $new_response->getBody());
        }

        // commits process
        $_authors = [];
        foreach ($commits as $_commit) {
            // author
            $_commit = (object)$_commit;
            $_authors[] = $_commit->author_name;
        }

        // Set infos
        $infos['commits'] = count($commits);
        $interval = date_diff($since, $until);
        $interval_days = $interval->format('%R%a');
        $infos['commits_average'] = count($commits) ? round(count($commits) / $interval_days, 0) : 0;

        // Author
        $_authors = array_count_values($_authors);
        arsort($_authors);

        $infos['authors'] = count($_authors);

        if ($this->debug) {
            d($infos, __METHOD__);
        }

        return $infos;
    }

    /**
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $since
     * @param DateTime $until
     *
     * @return array
     */
    public function getInfosMR(
        int $project_id,
        string $branch_name,
        DateTime $since,
        DateTime $until
    ): array
    {
        $infos = [
            'total' => 0,
            'opened' => 0,
            'merged' => 0,
            'closed' => 0,
            'locked' => 0,
        ];

        $path = 'projects/' . $project_id . '/merge_requests';
        $path .= '?' . http_build_query(
                [
                    'id' => $project_id,
                    'target_branch' => $branch_name,
                    'per_page' => 100,
                    'updated_after' => $since->format(DateTime::ATOM),
                    'updated_before' => $until->format(DateTime::ATOM),
                ]
            );

        $response = $this->request(Client::METHOD_GET, $path);
        $mr = $response->getBody();

        $next_page = $response->getHeader('X-Next-Page');
        $total_page = $response->getHeader('X-Total-Pages');

        // other range
        if (!empty($next_page)) {
            $i = 1;
            while ($i < $total_page[0]) {
                $i++;
                $response = $this->request('GET', $path . '&page=' . $i);
                $mr = array_merge($mr, $response->getBody());
            }
        }

        if (!is_array($mr)) {
            return $infos;
        }

        foreach ($mr as $merge_requests) {
            $infos['total']++;
            $infos[$merge_requests['state']]++;
        }

        if ($this->debug) {
            d($infos, __METHOD__);
        }

        return $infos;
    }

    /**
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $after
     * @param DateTime $before
     *
     * @return array
     * @throws Exception
     */
    public function getInfosPipeline(
        int $project_id,
        string $branch_name,
        DateTime $after,
        DateTime $before
    ): array
    {
        $infos = [
            'total' => 0,
            'successful' => 0,
            'successful_ratio' => 0,
            'failed' => 0,
            'failed_ratio' => 0,
            'duration' => 0,
        ];

        $path = 'projects/' . $project_id . '/pipelines';
        $path .= '?' . http_build_query(
                [
                    'id' => $project_id,
                    'ref' => $branch_name,
                    'per_page' => 100,
                    'updated_after' => $after->format(DateTime::ATOM),
                    'updated_before' => $before->format(DateTime::ATOM),
                    'scope' => 'finished',
                ]
            );

        $response = $this->request('GET', $path);
        $pipelines = $response->getBody();

        $next_page = $response->getHeader('X-Next-Page');
        $total_page = $response->getHeader('X-Total-Pages');

        // next range
        if (!empty($next_page)) {
            $i = 1;
            while ($i < $total_page[0]) {
                $i++;
                $response = $this->request('GET', $path . '&page=' . $i);
                $pipelines = array_merge($pipelines, $response->getBody());
            }
        }

        // pipelines process
        $duration = 0;
        foreach ($pipelines as $_pipeline) {
            $_pipeline = (object)$_pipeline;
            $_created = new DateTime($_pipeline->created_at);
            $_updated = new DateTime($_pipeline->updated_at);
            // todo remove when updated_after & updated_before are functional
            if ($_created < $after || $_updated > $before) {
                continue;
            }
            $infos['total']++;

            // todo stat other status ?
            switch ($_pipeline->status) {
                case 'success':
                    $infos['successful']++;
                    break;
                default:
                    $infos['failed']++;
            }

            $duration += $_updated->getTimestamp() - $_created->getTimestamp();
        }

        // set infos
        $infos['successful_ratio'] = 0;
        $infos['failed_ratio'] = 0;
        $infos['duration'] = 0;

        if ($infos['total'] > 0) {
            $infos['successful_ratio'] = round((100 / $infos['total']) * $infos['successful'], 0);
            $infos['failed_ratio'] = round((100 / $infos['total']) * $infos['failed'], 0);
            $duration = round($duration / $infos['total'], 0);
            $infos['duration'] = $this->formatDuration($duration);
        }

        if ($this->debug) {
            d($infos, __METHOD__);
        }

        return $infos;
    }

    /**
     * @return array
     */
    public function getInfosUrls(): array
    {
        return [
            'img' => self::ENDPOINT_RUNNER . '/report/assets',
            'commits' => self::ENDPOINT_WEB . '/openxtrem/mediboard/-/commits/master',
            'mr' => self::ENDPOINT_WEB . '/openxtrem/mediboard/-/merge_requests',
            'pipelines' => self::ENDPOINT_WEB . '/openxtrem/mediboard/pipelines',
            'jobs' => self::ENDPOINT_WEB . '/openxtrem/mediboard/-/jobs',
            'coverage' => self::ENDPOINT_RUNNER . '/report/coverage/index.html',
            'task' => self::ENDPOINT_WEB . '/openxtrem/mediboard/commits/master',
        ];
    }

    /**
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $before
     *
     * @return array
     * @throws Exception
     */
    public function getInfosTU(int $project_id, string $branch_name, DateTime $before): array
    {
        $infos = [
            'output' => null,
            'coverage' => 0,
            'coverage_lines' => 0,
            'duration' => 0,
            'job_id' => null,
            'date' => null,
        ];

        $pipeline = $this->getSchedulePipeline($project_id, $branch_name, $before);

        if ($pipeline !== null) {
            $job = $this->getJobFromName(
                $project_id,
                $pipeline,
                self::JOB_NAME_TU
            );

            $infos['job_id'] = $job->id;
            $infos['duration'] = $this->formatDuration($job->duration);
            $infos['date'] = $job->created_at;

            // Trace
            $trace = $this->getTraceFromJob($project_id, $job);
            $infos['trace'] = $trace;
            $infos['output'] = $this->regexPhpUnitOutput($trace);
            $infos['coverage'] = $job->coverage;
            // coverage
            //            $pattern = '/Lines:\s+(?<coverage>.*)% \((?<coverage_lines>\d+\/\d+)\)/m';
            //            $preg    = preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);
            //            $matches = reset($matches);
            //
            //            if ($preg !== false) {
            //                $infos['coverage']       = $matches['coverage'];
            //                $infos['coverage_lines'] = explode('/', $matches['coverage_lines']);
            //            }
        }

        if ($this->debug) {
            d($infos, __METHOD__);
        }

        return $infos;
    }

    /**
     * Get last pipeline schedule
     *
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $before
     *
     * @return mixed
     * @throws Exception
     * @deprecated
     */
    private function getSchedulePipeline(int $project_id, string $branch_name, DateTime $before)
    {
        $path = 'projects/' . $project_id . '/pipelines';
        $path .= '?' . http_build_query(
                [
                    'id' => $project_id,
                    'ref' => $branch_name,
                    'per_page' => 1,
                    'status' => 'success',
                    'updated_before' => $before->format(DateTime::ATOM),
                    'scope' => 'finished',
                    'name' => self::ADMIN_NAME,
                ]
            );

        $response = $this->request(Client::METHOD_GET, $path);
        $pipelines = $response->getBody();
        if (!isset($pipelines[0]['id'])) {
            // throw new Exception('Any scheduled pipelines found');
            return null;
        }

        return (object)$pipelines[0];
    }

    /**
     * @param int $project_id
     * @param Object $pipeline
     * @param string $job_name
     *
     * @return object
     * @throws Exception
     */
    private function getJobFromName(int $project_id, $pipeline, string $job_name)
    {
        $response = $this->request(
            Client::METHOD_GET,
            "projects/" . $project_id . "/pipelines/{$pipeline->id}/jobs"
        );
        $jobs = $response->getBody();
        $job_expected = null;
        foreach ($jobs as $_job) {
            $_job = (object)$_job;
            if (strpos($_job->name, 'unit') !== false) {
                $job_expected = $_job;
                break;
            }
        }

        if ($job_expected === null) {
            throw new Exception('Missing job name ' . $job_name);
        }

        return (object)$job_expected;
    }

    /**
     * @param int $project_id
     * @param Object $job
     *
     * @return string
     * @throws Exception
     * @deprecated use getJobTrace()
     */
    private function getTraceFromJob(int $project_id, $job): string
    {
        $response = $this->request(
            Client::METHOD_GET,
            "projects/" . $project_id . "/jobs/{$job->id}/trace"
        );
        $trace = $response->getBody();
        if (!$trace) {
            throw new Exception('No trace found for job ' . $job->id);
        }

        return $trace;
    }

    /**
     * @param string $trace
     *
     * @return mixed
     */
    private function regexPhpUnitOutput(string $trace)
    {
        $output = ['Tests' => 0, 'Assertions' => 0];
        $pattern = '/ \(.*tests,.*assertions\)/'; //2635 tests, 4293 assertions
        $result = preg_match_all($pattern, $trace, $matches, PREG_SET_ORDER, 0);
        if ($result !== false && !empty($matches)) {
            $_outputs = end($matches[0]);
            $_outputs = trim(str_replace(['(', ')'], '', $_outputs));

            foreach (explode(',', $_outputs) as $_output) {
                [$_val, $_key] = explode(' ', trim($_output));
                $output[ucfirst($_key)] = $_val;
            }
        }

        return $output;
    }

    /**
     * @param int $project_id
     * @param string $branch_name
     * @param DateTime $before
     *
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function getInfosTF(int $project_id, string $branch_name, DateTime $before): array
    {
        $infos = [
            'output' => null,
            'duration' => 0,
            'job_id' => null,
            'date' => null,
        ];

        $pipeline = $this->getSchedulePipeline($project_id, $branch_name, $before);

        if ($pipeline !== null) {
            $job = $this->getJobFromName(
                $project_id,
                $pipeline,
                self::JOB_NAME_TF
            );

            $infos['job_id'] = $job->id;
            $infos['duration'] = $this->formatDuration($job->duration);
            $infos['date'] = $job->created_at;

            // Trace
            $trace = $this->getTraceFromJob($project_id, $job);
            $infos['output'] = $this->regexPhpUnitOutput($trace);
        }

        if ($this->debug) {
            d($infos, __METHOD__);
        }

        return $infos;
    }

    /**
     * @param int $sec
     *
     * @return string
     */
    private function formatDuration(int $sec): string
    {
        return sprintf('%02d:%02d:%02d', ($sec / 3600), ($sec / 60 % 60), $sec % 60);
    }

    /**
     * @param string $method
     * @param string $path
     *
     * @return Response|null
     */
    private function request(string $method, string $path): ?Response
    {
        // Auth
        $this->client->setHeaders(
            [
                'Private-Token' => $this->token,
            ]
        );

        $this->chrono->start();

        // Request
        try {
            if ($this->debug) {
                d($path);
                d($method);
            }

            $response = $this->client->call($method, $path);
            $this->chrono->stop();
            return $response;
        } catch (ClientException $e) {
            if ($this->debug) {
                dump($e);
            }
            $this->chrono->stop();
            return null;
        }
    }

    public function getChronoReport(): array
    {
        return [
            'steps' => $this->chrono->nbSteps,
            'total' => $this->chrono->total,
            'average' => $this->chrono->avgStep,
        ];
    }
}
