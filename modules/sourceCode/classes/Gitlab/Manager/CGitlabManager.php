<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Manager;

use DateTime;
use DateTimeZone;
use Exception;
use Ox\Core\CApp;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbString;
use Ox\Core\HttpClient\Client;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabCommit;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabJob;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabPipeline;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabJobTestsReport;
use Ox\Erp\Tasking\CTaskingTicketCommit;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceHTTP;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CGitlabManager
 *
 * @package Ox\Erp\SourceCode\Gitlab\Manager
 */
class CGitlabManager
{
    public const IO_URL               = 'https://openxtrem.gitlab.io';
    public const SOURCE_NAME          = 'gitlab_api';
    public const DEFAULT_BRANCH       = 'master';
    public const CI_MEDIBOARD_PROJECT = 'mediboard';
    public const SEARCHED_BRANCHES    = ['^master', '^release'];

    /** @var CGitLabApiClient */
    private static $api_client;

    /**
     * Sets the Gitlab Api Client up for repository usage
     *
     * @return mixed
     * @throws CMbException
     */
    private static function getApiClient()
    {
        if (!self::$api_client instanceof CGitLabApiClient) {
            /* Load the configured HTTP exchange source */
            $source = CExchangeSource::get(
                self::SOURCE_NAME,
                CSourceHTTP::TYPE
            );

            /* Check the source is valid */
            if (!$source instanceof CSourceHTTP || empty($source->_id)) {
                throw new CMbException('Invalid exchange source : ' . self::SOURCE_NAME);
            }

            /* Build and return the Gitlab API Client */

            return self::$api_client = new CGitLabApiClient(
                new Client($source),
                true
            );
        }

        return self::$api_client;
    }

    /**
     * Imports Gitlab projects
     *
     * @return array|CGitlabProject[]
     * @throws CMbException
     */
    public static function importAllProjects(): array
    {
        $imported_projects = [];

        $resources = self::getApiClient()->getProjects()->getBody();

        foreach ($resources as $resource) {
            try {
                $project = new CGitlabProject();
                $project->bind($resource);
                $project->loadMatchingObject();
                if (!$project instanceof CGitlabProject || empty($project->ox_gitlab_project_id)) {
                    if ($msg = $project->store()) {
                        throw new CMbException("CGitlabProject-error-cannot_be_created", $msg);
                    }
                    $imported_projects[] = $project;
                }
            } catch (Exception $e) {
                CApp::error($e);
            }
        }

        return $imported_projects;
    }

    /**
     * Imports all Gitlab projects branches
     *
     * @param bool $ready
     *
     * @return array|CGitlabBranch[]
     * @throws CMbException|Exception
     */
    public static function importAllProjectBranches(bool $ready = true): array
    {
        $imported_branches = [];

        foreach (self::loadProjects($ready) as $project) {
            foreach (self::SEARCHED_BRANCHES as $branch_pattern) {
                $imported_branches = array_merge(
                    $imported_branches,
                    self::importProjectBranches($project, $branch_pattern)
                );
            }
        }

        return $imported_branches;
    }

    /**
     * Imports a Gitlab project's branches
     *
     * @param CGitlabProject $project
     * @param string         $branch_pattern
     *
     * @return array|CGitlabBranch[]
     * @throws CMbException|Exception
     */
    public static function importProjectBranches(CGitlabProject $project, string $branch_pattern): array
    {
        $imported_branches = [];

        $resources = self::getApiClient()->getBranches($project->id, $branch_pattern)->getBody();

        foreach ($resources as $resource) {
            try {
                $branch                       = new CGitlabBranch();
                $branch->ox_gitlab_project_id = $project->ox_gitlab_project_id;
                $branch->bind($resource);
                $branch->loadMatchingObject();
                if (!$branch instanceof CGitlabBranch || empty($branch->ox_gitlab_branch_id)) {
                    if ($msg = $branch->store()) {
                        throw new CMbException("CGitlabBranch-error-cannot_be_created", $msg);
                    }
                    $imported_branches[] = $branch;
                }
            } catch (CMbException $e) {
                CApp::error($e);
            }
        }

        return $imported_branches;
    }

    /**
     * Loads projects that are marked as ready
     *
     * @param bool $ready
     *
     * @return CGitlabProject[]|null
     * @throws Exception
     */
    public static function loadProjects(bool $ready = false): ?array
    {
        $project = new CGitlabProject();

        $where = $ready ? ['ready' => $project->getDS()->prepare('= ?', '1')] : [];

        return $project->loadList($where);
    }

    /**
     * Loads projects branches of which commits can be integrated
     *
     * @param bool $ready
     *
     * @return CGitlabBranch[]|null
     * @throws Exception
     */
    private static function loadProjectsBranches(bool $ready = false): ?array
    {
        $projects     = self::loadProjects($ready);
        $projects_ids = CMbArray::pluck($projects, 'ox_gitlab_project_id');
        $branch       = new CGitlabBranch();

        return $branch->loadList(
            [
                'ox_gitlab_project_id' => $branch->getDS()->prepareIn($projects_ids),
            ]
        );
    }

    /**
     * Imports a Gitlab project commits
     *
     * @param CGitlabProject $project
     * @param CGitlabBranch  $branch
     * @param bool           $init
     * @param bool           $bind
     * @param int            $page
     *
     * @return CGitlabCommit[]|array
     * @throws CMbException|Exception
     */
    public static function importCommits(
        CGitlabProject $project,
        CGitlabBranch $branch,
        bool $init = false,
        bool $bind = true,
        int $page = 1
    ): array {
        $imported_commits = [];
        $page             = $init ? self::getPageFromCommits($branch) : $page;

        $response = self::getApiClient()->getCommits(
            $project->id,
            $branch->name,
            $page
        );

        switch ($response->getStatusCode()) {
            case 200:
                $resources = $response->getBody();

                foreach ($resources as $resource) {
                    $imported_commit = self::importCommit($branch, $resource, $init, $bind);
                    if ($imported_commit instanceof CGitlabCommit) {
                        $imported_commits[] = $imported_commit;
                    } elseif (false === $init) {
                        /* Stop import when an existing commit has been found (when not in init mode) */
                        return $imported_commits;
                    }
                }
                break;
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }

        return $imported_commits;
    }

    /**
     * Imports a Gitlab project pipelines
     *
     * @param CGitlabProject $project
     * @param string         $branch
     * @param bool           $init
     * @param int            $page
     *
     * @return CGitlabPipeline[]|array
     * @throws CMbException|Exception
     */
    public static function importPipelines(
        CGitlabProject $project,
        string $branch,
        bool $init = false,
        int $page = 1
    ): array {
        $imported_pipelines = [];
        $page               = $init ? self::getPageFromPipelines($project, $branch) : $page;

        $response = self::getApiClient()->getPipelines(
            $project->id,
            $branch,
            $page,
            CGitlabPipeline::API_PAGE_LIMIT,
            [
                'name'          => CGitlabPipeline::ADMIN_USER_NAME,
                'scope'         => CGitlabPipeline::SCOPE_FINISHED,
                'updated_after' => CGitlabManager::convertGitlabDate(
                    CMbDT::dateTime(CGitlabPipeline::API_IMPORT_START_DATE),
                    true
                ),
            ]
        );

        switch ($response->getStatusCode()) {
            case 200:
                $resources = $response->getBody();

                /* Loop over list to fetch single pipelines data and import one by one */
                foreach ($resources as $resource) {
                    $imported_pipeline = self::importPipeline($project, $resource);
                    if ($imported_pipeline instanceof CGitlabPipeline) {
                        $imported_pipelines[] = $imported_pipeline;
                    } elseif (false === $init) {
                        /* Stop import when an existing pipeline has been found (when not in init mode) */
                        return $imported_pipelines;
                    }
                }
                break;
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }

        return $imported_pipelines;
    }

    /**
     * Imports a Gitlab project single pipeline
     *
     * @param CGitlabProject $project
     * @param array          $resource
     *
     * @return CGitlabPipeline|null
     * @throws CMbException|Exception
     */
    public static function importPipeline(
        CGitlabProject $project,
        array $resource
    ): ?CGitlabPipeline {

        $response = self::getApiClient()->getPipeline(
            $project->id,
            $resource['id'],
        );

        switch ($response->getStatusCode()) {
            case 200:
                $resource = $response->getBody();

                $pipeline     = new CGitlabPipeline();
                $pipeline->id = $resource['id'];
                $pipeline->loadMatchingObject();

                if ($pipeline instanceof CGitlabPipeline && !empty($pipeline->ox_gitlab_pipeline_id)) {
                    return null;
                } else {
                    /* Bind data to pipeline */
                    $pipeline->bind(
                        CGitlabPipeline::formatResource($resource),
                        true
                    );

                    /* Bind to pipeline */
                    $pipeline->ox_gitlab_project_id = $project->ox_gitlab_project_id;

                    /* Persist data */
                    if ($msg = $pipeline->store()) {
                        throw new CMbException("CGitlabPipeline-error-cannot_be_created", $msg);
                    }

                    /* Only import jobs from successful/failed pipelines */
                    if (
                        in_array(
                            $pipeline->status,
                            [
                                CGitlabPipeline::STATUS_SUCCESS,
                                CGitlabPipeline::STATUS_FAILED
                            ]
                        )
                    ) {
                        self::importJobs($project, $pipeline);
                    }

                    return $pipeline;
                }
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }

        return null;
    }

    /**
     * Imports a Gitlab pipeline jobs
     *
     * @param CGitlabProject  $project
     * @param CGitlabPipeline $pipeline
     *
     * @return CGitlabJob[]|null
     * @throws CMbException|Exception
     */
    public static function importJobs(
        CGitlabProject $project,
        CGitlabPipeline $pipeline
    ): ?array {
        $imported_jobs = [];

        $response = self::getApiClient()->getJobs(
            $project->id,
            $pipeline->id,
        );

        switch ($response->getStatusCode()) {
            case 200:
                $resources = $response->getBody();
                foreach ($resources as $resource) {
                    $imported_job = self::importJob($pipeline, $resource);
                    if ($imported_job instanceof CGitlabJob) {
                        $imported_jobs[] = $imported_job;
                    }
                }
                break;
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }

        return !empty($imported_jobs) ? $imported_jobs : null;
    }

    /**
     * Imports a Gitlab pipeline job
     *
     * @param CGitlabPipeline $pipeline
     * @param array           $resource
     *
     * @return CGitlabJob|null
     * @throws Exception
     */
    public static function importJob(CGitlabPipeline $pipeline, array $resource): ?CGitlabJob
    {
        try {
            $job     = new CGitlabJob();
            $job->id = $resource['id'];
            $job->loadMatchingObject();

            if ($job instanceof CGitlabJob && !empty($job->ox_gitlab_job_id)) {
                return null;
            } else {
                /* As long as Gitlab API does not allow to filter jobs with the job name, filtering is applyied here */
                if (
                    !array_key_exists('name', $resource)
                    || !in_array($resource['name'], CGitlabJob::IMPORT_JOB_NAMES)
                ) {
                    return null;
                }

                /* Bind data to commit */
                $job->bind(
                    CGitlabJob::formatResource($resource),
                    true
                );

                /* Bind to pipeline */
                $job->ox_gitlab_pipeline_id = $pipeline->ox_gitlab_pipeline_id;

                /* Persist data */
                if ($msg = $job->store()) {
                    throw new CMbException("CGitlabJob-error-cannot_be_created", $msg);
                }

                self::importJobArtifacts(
                    $pipeline->loadRefGitlabProject(),
                    $job
                );

                return $job;
            }
        } catch (CMbException $e) {
            CApp::error($e);
        }

        return null;
    }

    /**
     * Downloads a Gitlab pipeline job artifact file
     *
     * @param CGitlabProject $project
     * @param CGitlabJob     $job
     *
     * @return void
     * @throws Exception
     */
    public static function importJobArtifacts(CGitlabProject $project, CGitlabJob $job): void
    {
        $trace = self::importJobTraceData($project, $job);
        if (is_string($trace) && !empty($trace)) {
            CGitlabJobTestsReport::generateTestsReport($project, $job, $trace);
        }
    }

    /**
     * Downloads a Gitlab pipeline job coverage txt file
     *
     * @param CGitlabProject $project
     * @param CGitlabJob     $job
     *
     * @return string|null
     * @throws Exception
     */
    public static function importJobCoverageXml(CGitlabProject $project, CGitlabJob $job): ?string
    {
        $response = self::getApiClient()->getJobArtifact(
            $project->id,
            $job->id,
            CGitlabJob::ARTIFACT_DATA_COVERAGE_XML
        );

        switch ($response->getStatusCode()) {
            case 200:
                return $response->getBody();
            case CGitLabApiClient::NOT_FOUND_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-resource-not-found"));
                break;
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }
        return null;
    }

    /**
     * Downloads a Gitlab pipeline job trace log file
     *
     * @param CGitlabProject $project
     * @param CGitlabJob     $job
     *
     * @return string|null
     * @throws Exception
     */
    public static function importJobTraceData(CGitlabProject $project, CGitlabJob $job): ?string
    {
        $response = self::getApiClient()->getJobTrace(
            $project->id,
            $job->id
        );

        switch ($response->getStatusCode()) {
            case 200:
                return $response->getBody();
            case CGitLabApiClient::NOT_FOUND_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-resource-not-found"));
                break;
            case CGitLabApiClient::RATE_LIMIT_REACHED_HTTP_CODE:
                CApp::error(new CMbException("CGitLabApiClient-error-api-rate-limit-reached"));
                break;
            default:
                CApp::error(new CMbException($response->getStatusCode() . " : " . json_encode($response->getBody())));
                break;
        }
        return null;
    }

    /**
     * Imports all Gitlab projects pipelines
     *
     * @param bool $init
     *
     * @return CGitlabPipeline[]|array
     * @throws Exception
     */
    public static function importAllProjectsPipelines(bool $init = false): array
    {
        $imported_pipelines = [];

        /* Load all projects marked as ready */
        /* @todo: config array of project names/ids with available pipelines */
        $projects = CGitlabManager::loadProjects(true);

        foreach ($projects as $project) {
            /* @todo: config array of project names/ids with available pipelines */
            if ($project->name !== self::CI_MEDIBOARD_PROJECT) {
                continue;
            }

            /* @todo: config array of refs with available pipelines */
            $branches = [self::DEFAULT_BRANCH];
            foreach ($branches as $branch) {
                $imported_pipelines = array_merge(
                    $imported_pipelines,
                    self::importPipelines($project, $branch, $init)
                );
            }
        }

        return $imported_pipelines;
    }


    /**
     * Imports all Gitlab projects commits
     *
     * @param bool $init
     * @param bool $bind
     *
     * @return CGitlabCommit[]|array
     * @throws Exception
     */
    public static function importAllProjectsCommits(bool $init = false, bool $bind = true): array
    {
        $imported_commits = [];

        $branches = self::loadProjectsBranches(true);
        foreach ($branches as $branch) {
            $project = $branch->loadRefGitlabProject();
            if (empty($project->ox_gitlab_project_id)) {
                throw new CMbException("CGitlabBranch-error-cannot_find_linked_project_id");
            }

            $imported_commits = array_merge(
                $imported_commits,
                self::importCommits($project, $branch, $init, $bind)
            );
        }

        return $imported_commits;
    }

    /**
     * Imports a Gitlab commit
     *
     * @param CGitlabBranch $branch
     * @param array         $resource
     * @param bool          $init
     * @param bool          $bind
     *
     * @return CGitlabCommit|null
     * @throws Exception
     */
    public static function importCommit(
        CGitlabBranch $branch,
        array $resource,
        bool $init = false,
        bool $bind = true
    ): ?CGitlabCommit {
        try {
            $commit     = new CGitlabCommit();
            $commit->id = $resource['id'];
            $commit->loadMatchingObject();

            if ($commit instanceof CGitlabCommit && !empty($commit->ox_gitlab_commit_id)) {
                return null;
            } else {
                /* Bind data to commit */
                $commit->bind(
                    CGitlabCommit::formatResource($resource),
                    true
                );

                /* Locate commit user */
                $user = self::findCommitUser($resource);
                if ($user instanceof CMediusers && !empty($user->user_id)) {
                    $commit->ox_user_id = $user->user_id;
                }

                /* Locate commit type */
                $commit->determineType();

                /* Bind to branch */
                $commit->ox_gitlab_branch_id = $branch->ox_gitlab_branch_id;

                /* Persist data */
                if ($msg = $commit->store()) {
                    throw new CMbException("CGitlabCommit-error-cannot_be_created", $msg);
                }

                if ($bind === true) {
                    self::bindToTasks($commit, $init);
                }

                return $commit;
            }
        } catch (CMbException $e) {
            CApp::error($e);
        }

        return null;
    }

    /**
     * Return the CUser matching the author email, false is not found
     *
     * @param array $resource
     * @param bool  $guess
     *
     * @return CMediusers|false
     */
    public static function findCommitUser(array $resource, bool $guess = true)
    {
        if (
            !array_key_exists('author_email', $resource)
            || !filter_var($resource['author_email'], FILTER_VALIDATE_EMAIL)
        ) {
            return false;
        }

        try {
            $user     = new CUser();
            $searches = [$resource['author_email']];

            if (true === $guess) {
                /* If email contains dots, remove them
                if not add a dot as 2nd char (to match old email addresses format) */
                if (strpos($resource['author_email'], '.') === 1) {
                    $searches[] = substr_replace($resource['author_email'], '', 1, 1);
                } else {
                    $searches[] = substr_replace(
                        $resource['author_email'],
                        '.',
                        1,
                        0
                    );
                }

                /* Try using the author name as email identifier */
                if (array_key_exists('author_name', $resource)) {
                    $searches[] = CMbString::removeAccents(
                        str_replace(' ', '.', strtolower(trim($resource['author_name'])))
                    ) . '@openxtrem.com';
                }
            }

            $i = 0;
            while ($i < count($searches) && !$user->user_id) {
                $user->user_email = $searches[$i];
                $user->loadMatchingObject();
                $i++;
            }

            if (!empty($user->user_id)) {
                return $user->loadRefMediuser();
            }
        } catch (Exception $e) {
            CApp::error($e);
        }

        return false;
    }

    /**
     * @param CGitlabBranch $branch
     *
     * @return CGitlabBranch|array
     */
    public static function getBackwardBranches(CGitlabBranch $branch): array
    {
        $backward_branches = [];

        try {
            $project = $branch->loadRefGitlabProject();
            if ($project->ox_gitlab_project_id) {
                $project_branches = $project->loadRefBranches();
                if ($branch->name === self::DEFAULT_BRANCH) {
                    return $project_branches;
                }

                /** @var CGitlabBranch $project_branch */
                foreach ($project_branches as $project_branch) {
                    if ($project_branch->name === self::DEFAULT_BRANCH) {
                        continue;
                    }
                    /* If the branch in loop */
                    if (strcmp($branch->name, $project_branch->name) >= 0) {
                        $backward_branches[] = $project_branch;
                    }
                }
            }
        } catch (Exception $e) {
            CApp::error($e);
        }

        return $backward_branches;
    }

    /**
     * @param CGitlabBranch $branch
     *
     * @return int
     * @throws Exception
     */
    public static function countCommits(CGitlabBranch $branch): int
    {
        $commit = new CGitlabCommit();
        return $commit->countList(
            [
                'ox_gitlab_branch_id' => $commit->getDS()->prepareLike($branch->ox_gitlab_branch_id),
            ]
        );
    }

    /**
     * Returns the page number to search from depending on the branch commit count
     *
     * @param CGitlabBranch $branch
     *
     * @return int
     * @throws Exception
     */
    public static function getPageFromCommits(CGitlabBranch $branch): int
    {
        $count = self::countCommits($branch);
        if ($count !== 0) {
            return intval(1 + ceil($count / CGitlabCommit::API_PAGE_LIMIT));
        }
        return 1;
    }

    /**
     * @param CGitlabProject $project
     * @param string         $branch
     *
     * @return int
     * @throws Exception
     */
    public static function countPipelines(CGitlabProject $project, string $branch): int
    {
        $pipeline = new CGitlabPipeline();

        return $pipeline->countList(
            [
                'ox_gitlab_project_id' => $pipeline->getDS()->prepareLike($project->ox_gitlab_project_id),
                'ref'                  => $pipeline->getDS()->prepareLike($branch),
            ]
        );
    }

    /**
     * Returns the page number to search from depending on the branch commit count
     *
     * @param CGitlabProject $project
     * @param string         $branch
     *
     * @return int
     * @throws Exception
     */
    public static function getPageFromPipelines(CGitlabProject $project, string $branch): int
    {
        $count = self::countPipelines($project, $branch);
        if ($count !== 0) {
            return intval(1 + ceil($count / CGitlabPipeline::API_PAGE_LIMIT));
        }
        return 1;
    }

    /**
     * Bind a commit to existing tasks
     *
     * @param CGitlabCommit $commit
     * @param bool          $init
     *
     * @return void
     * @throws Exception
     */
    public static function bindToTasks(CGitlabCommit $commit, bool $init = false): void
    {
        CTaskingTicketCommit::parseMessageTaskingTicket($commit, !$init, !$init);
    }

    /**
     * Converts DATE_RFC3339_EXTENDED formatted string to MB datetime string if $convert_to is false
     *
     * @param string $datetime
     * @param bool   $convert_to
     *
     * @return string
     * @throws Exception
     */
    public static function convertGitlabDate(string $datetime, bool $convert_to = false): string
    {
        if ($convert_to === true) {
            $datetime = new DateTime(
                $datetime,
                new DateTimeZone(date_default_timezone_get())
            );

            return $datetime->format(DATE_RFC3339_EXTENDED);
        }

        return strftime(
            CMbDT::ISO_DATETIME,
            DateTime::createFromFormat(
                DATE_RFC3339_EXTENDED,
                $datetime
            )->getTimestamp()
        );
    }

    /**
     * @param string $ref
     *
     * @return array|null
     * @throws CMbException
     * @throws Exception
     */
    public function checkLatestSuccessfulPipeline(string $ref): ?array
    {
        $response = self::getApiClient()->getPipelines(
            CGitLabApiClient::MEDIBOARD_PROJECT_ID,
            $ref,
            1,
            1,
            [
                'scope'  => CGitlabPipeline::SCOPE_FINISHED,
                'status' => CGitlabPipeline::STATUS_SUCCESS,
            ]
        );
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }
        $resource = $response->getBody();
        $pipeline = reset($resource);

        return $pipeline['status'] === CGitlabPipeline::STATUS_SUCCESS ? $pipeline : null;
    }

    /**
     * Returns the sha target of the latest successful deployment job
     * Null is returned if a the deployment job is currently running
     *
     * @param string $ref
     *
     * @return array
     * @throws Exception
     */
    public function checkDeploymentJob(string $ref): ?array
    {
        /* Load the latest pipeline for ref */
        $response = self::getApiClient()->getPipelines(
            CGitLabApiClient::MEDIBOARD_PROJECT_ID,
            $ref,
            1,
            1
        );
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }
        $resource = $response->getBody();
        $pipeline = reset($resource);

        /* If latest pipeline is successful, return its api resource */
        if ($pipeline['status'] === CGitlabPipeline::STATUS_SUCCESS) {
            return $pipeline;
        }

        /* If latest pipeline is not successful, look for its jobs */
        $response = self::getApiClient()->getJobs(
            CGitLabApiClient::MEDIBOARD_PROJECT_ID,
            $pipeline['id'],
            false
        );

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }

        $resource = $response->getBody();
        if (!empty($resource)) {
            foreach ($resource as $job) {
                /* Focus only only on the CI deployment job */
                if ($job['name'] !== CGitLabApiClient::JOB_NAME_DEPLOY) {
                    continue;
                }
                /* Return the running pipeline if deploy is running (this shall mean try later) */
                if ($job['status'] === CGitlabJob::STATUS_RUNNING) {
                    return $pipeline;
                } else {
                    /* Load the latest successful pipeline for ref */
                    return $this->checkLatestSuccessfulPipeline($ref);
                }
            }
        }

        return null;
    }
}
