<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Cron;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLogger;
use Ox\Core\CMbDT;
use Ox\Core\Mutex\CMbMutex;

/**
 * Cron job manager
 */
class CCronJobManager implements IShortNameAutoloadable
{
    const MUTEX_TIMEOUT      = 600;
    const TOLERANCE          = 30;
    const CONNECTION_TIMEOUT = 10;

    /** @var CCronJob[] */
    private $jobs = [];
    /** @var CCronJob[] */
    private $jobs_to_run = [];
    /** @var CCronJobExecution[] */
    private $executions = [];

    private $start_datetime;
    private $session_cookie;
    private $multi_curl;

    /**
     * CCronJobManager constructor.
     */
    public function __construct()
    {
        $this->start_datetime = CMbDT::dateTime();
        $this->session_cookie = session_name() . "=" . session_id();
        $this->multi_curl     = curl_multi_init();
    }

    /**
     * Register the jobs to launch
     *
     * @param CCronJob $job Job to register
     *
     * @return void
     */
    public function registerJob(CCronJob $job)
    {
        $this->jobs[] = $job;
    }

    /**
     * Run the registered jobs
     *
     * @return void
     * @throws Exception
     */
    public function runJobs()
    {
        $this->electJobsToRun();
        $this->prepareExecution();
        $this->executeCronjobs();
        $this->closeHandles();
    }

    /**
     * Elect the registered jobs to run at the current minute
     *
     * @return void
     */
    private function electJobsToRun()
    {
        foreach ($this->jobs as $_cron) {
            // Récupération de la prochaine date d'exécution
            $next      = $_cron->getNextDate(1);
            $next      = reset($next);
            $tolerance = CMbDT::dateTime("+ " . static::TOLERANCE . " second", $next);

            // On vérifie si le script doit être exécuté
            if ($next <= $this->start_datetime && $this->start_datetime <= $tolerance) {
                try {
                    if (!$this->setMutex($_cron->_guid)) {
                        continue;
                    }
                } catch (Exception $e) {
                    CApp::log($e->getMessage(), CLogger::LEVEL_WARNING);
                    continue;
                }


                $this->jobs_to_run[] = $_cron;
            }
        }
    }

    /**
     * Prepare the execution of the jobs to run
     * Create the log and prepare the curl handle
     *
     * @return void
     * @throws Exception
     */
    private function prepareExecution()
    {
        foreach ($this->jobs_to_run as $_job) {
            // Préparation du log
            $_log = $this->createJobLog($_job);

            $_handle = $this->initJob($_job);

            curl_multi_add_handle($this->multi_curl, $_handle);

            $_execution = new CCronJobExecution($_job, $_log->_id, $_handle);
            $this->registerJobExecution($_execution);
        }
    }

    /**
     * Registrer the currents job executions
     *
     * @param CCronJobExecution $execution Executions to register
     *
     * @return void
     */
    private function registerJobExecution(CCronJobExecution $execution)
    {
        $this->executions[] = $execution;
    }

    /**
     * @param CCronJob $job Job to create the log for
     *
     * @return CCronJobLog
     * @throws Exception
     */
    private function createJobLog(CCronJob $job)
    {
        $log = new CCronJobLog();

        $log->start_datetime = $this->start_datetime;
        $log->cronjob_id     = $job->_id;
        $log->status         = 'started';
        $log->server_address = get_server_var('SERVER_ADDR');
        $log->severity       = 0;

        $log->store();

        // Tell if the script is executed via cron or not
        $job->_params['execute_cron_log_id'] = $log->_id;

        return $log;
    }

    /**
     * @param CCronJob $job Job to initialise
     *
     * @return false|resource
     * @throws Exception
     */
    private function initJob(CCronJob $job)
    {
        // TODO pour chaque cronjob élection du serveur cible
        $base = rtrim(CAppUI::conf("base_url"), "/");

        $handle = curl_init($job->makeUrl($base));

        $curl_opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => static::CONNECTION_TIMEOUT,
        ];

        // If not a tokenized job use session to login
        if (!$job->token_id) {
            $curl_opts[CURLOPT_COOKIE] = $this->session_cookie;
        }

        curl_setopt_array($handle, $curl_opts);


        return $handle;
    }

    /**
     * Execute the curl calls
     *
     * @return void
     */
    private function executeCronjobs()
    {
        // Exec
        do {
            // Run the handles
            curl_multi_exec($this->multi_curl, $still_running);
            // Wait for a handle to change state
            curl_multi_select($this->multi_curl);

            $remove = [];
            foreach ($this->executions as $_id => $_exec) {
                $http_infos = $_exec->getHttpInfo();
                if ($http_infos['http_code']) {
                    $this->endJob($_exec);
                    $remove[] = $_id;
                }
            }

            foreach ($remove as $_rem) {
                unset($this->executions[$_rem]);
            }
        } while ($still_running > 0);

        // End remaining jobs
        foreach ($this->executions as $_exec) {
            $this->endJob($_exec);
        }
    }

    /**
     * Log the results of wurl calls
     *
     * @return void
     * @throws Exception
     */
    private function closeHandles()
    {
        curl_multi_close($this->multi_curl);
    }

    /**
     * @param object $execution Curl execution
     *
     * @return void
     * @throws Exception
     */
    protected function endJob($execution): void
    {
        $_job    = $execution->getJob();
        $_handle = $execution->getHandle();
        $_log_id = $execution->getLog();

        $_info = $execution->getHttpInfo();
        curl_multi_remove_handle($this->multi_curl, $_handle);

        // Avoid releasing the mutex too soon
        if (intval($_info['total_time']) < 2) {
            sleep(2);
        }

        $this->releaseMutex($_job->_guid);

        try {
            $this->endJobLog($_log_id, $_info);
        } catch (Exception $e) {
            CApp::log('Error writing cron result', $e->getMessage(), CLogger::LEVEL_WARNING);
        }
    }


    /**
     * End a job log
     *
     * @param int   $log_id Log to complete
     * @param array $info   Http result from curl
     *
     * @return void
     * @throws Exception
     */
    private function endJobLog($log_id, $info)
    {
        $duration = intval($info['total_time']);

        // Reload log to get the right severity
        $log = new CCronJobLog();
        $log->load($log_id);

        $log->status       = $info['http_code'];
        $log->end_datetime = CMbDT::dateTime("+ $duration SECOND", $log->start_datetime);
        $log->duration     = intval($info['total_time'] * 1000);

        if ($log->status === 0) {
            $log->log = 'Unable to connect to host : ' . $info['url'];
        }

        $log->store();
    }

    /**
     * @param string $cron_guid Mutex key to use
     *
     * @return bool
     */
    private function setMutex($cron_guid)
    {
        $_mutex = CMbMutex::getDistributedMutex($cron_guid);

        if (!$_mutex->lock(static::MUTEX_TIMEOUT)) {
            throw new Exception('Unable to put mutex for cronjob');
        }

        $_mutex->forget();

        return true;
    }

    /**
     * Release the mutex
     *
     * @param string $cron_guid CCronJob guid
     *
     * @return void
     */
    private function releaseMutex($cron_guid)
    {
        try {
            $_mutex = new CMbMutex($cron_guid);
        } catch (Exception $e) {
            return;
        }

        $_mutex->release();
    }
}
