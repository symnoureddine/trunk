<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console\CI;

use Cz\Git\GitException;
use Cz\Git\GitRepository;
use Exception;
use Ox\Cli\CommandLinePDO;
use Ox\Cli\DeployOperation;
use Ox\Core\CMbConfig;
use Ox\Core\CMbPath;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildRunner extends DeployOperation
{

    const ALLOWED_BRANCH_NAME = [
        'master',
        'feature',
        'release',
        'bugfix',
        'test',
    ];

    const FEATURE_BRANCH_NAME = [
        'feature',
        'bugfix',
        'test',
    ];

    const MASTER_DATABASE = 'branch_master';

    protected $ci_project_dir;
    protected $ip_runner;
    protected $db_host;
    protected $db_user;
    protected $db_pass;
    protected $branch_name;
    protected $is_gc;
    protected $pipeline_id;
    private   $database_name;

    /**
     * @see parent::configure()
     */
    protected function configure(): void
    {
        $this
            ->setName('ci:build-runner')
            ->setDescription('Build Runner')
            ->addOption(
                'ci_project_dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The full path where the repository is cloned'
            )->addOption(
                'db_host',
                null,
                InputOption::VALUE_REQUIRED,
                'The db host name'
            )->addOption(
                'db_user',
                null,
                InputOption::VALUE_REQUIRED,
                'The db username'
            )->addOption(
                'db_pass',
                null,
                InputOption::VALUE_REQUIRED,
                'The db password'
            )->addOption(
                'branch_name',
                null,
                InputOption::VALUE_REQUIRED,
                'The branch name'
            )->addOption(
                'ip_runner',
                null,
                InputOption::VALUE_OPTIONAL,
                'Runner ip adress'
            )->addOption(
                'is_gc',
                null,
                InputOption::VALUE_OPTIONAL,
                'Is mode garbage collector'
            )->addOption(
                'pipeline_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Pipeline id'
            );
    }

    /**
     * @param string $release_code
     *
     * @return bool|mixed
     */
    protected function testBranch($release_code)
    {
        return ($this->master_branch == $release_code);
    }

    /**
     * @see parent::showHeader()
     */
    protected function showHeader()
    {
        $this->out($this->output, '<fg=red;bg=black>Deploy GitLab-runner</fg=red;bg=black>');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output         = $output;
        $this->input          = $input;
        $this->path           = dirname(__DIR__, 4) . "/";
        $this->ci_project_dir = $input->getOption('ci_project_dir');
        $this->ip_runner      = $input->getOption('ip_runner');
        $this->db_host        = $input->getOption('db_host');
        $this->db_user        = $input->getOption('db_user');
        $this->db_pass        = $input->getOption('db_pass');
        $this->branch_name    = $input->getOption('branch_name');
        $this->pipeline_id    = $input->getOption('pipeline_id');
        $this->is_gc          = (bool)$input->getOption('is_gc');
        $this->showHeader();

        // Check branch name
        $is_allowed_branch = false;
        foreach (static::ALLOWED_BRANCH_NAME as $name) {
            if (strpos($this->branch_name, $name) === 0) {
                $is_allowed_branch = true;
            }
        }

        if (!$is_allowed_branch) {
            $allowed_branch = implode('|', static::ALLOWED_BRANCH_NAME);
            throw new LogicException("Invalid branch name '{$this->branch_name}' allowed ({$allowed_branch}).");
        }

        // Check path
        if (!is_dir($this->path)) {
            throw new InvalidArgumentException("'{$this->path}' is not a valid directory.");
        }

        $this->out($this->output, $this->ci_project_dir);

        // bootstrap
        include_once "{$this->path}/cli/bootstrap.php";

        // generate includes/config.php
        $this->generateConfig();

        // generate database
        $this->generateDatabase();

        // generate tmp
        $this->generateTmp();

        // garbage collector
        if ($this->is_gc) {
            $this->dbGarbageCollector();
        }
    }

    /**
     * @return void
     * @throws GitException
     */
    private function generateDatabase()
    {
        $database        = $this->getDatabaseName();
        $master_database = static::MASTER_DATABASE;
        $this->out($this->output, "Generate database '{$database}'.");

        $pdo = new CommandLinePDO($this->db_host, $this->db_user, $this->db_pass);


        if ($pdo->isDatabaseExists($database)) {
            $this->out($this->output, "Database '{$database}' already exists.");

            return;
        }

        if (!$pdo->isDatabaseExists($master_database)) {
            throw new LogicException("Database '{$master_database}' is not exists.");
        }

        if ($pdo->createDatabase($database, true)) {
            $this->out($this->output, "Database '{$database} created.'");
        } else {
            throw new LogicException("Unable to create new database '{$database}'.");
        }

        // dump from master database
        $cmd = "mysqldump -h {$this->db_host} -u {$this->db_user} --password={$this->db_pass} {$master_database} |
     mysql -h {$this->db_host} -u {$this->db_user} --password={$this->db_pass} {$database}";
        exec($cmd, $output);

        if (!empty($output)) {
            echo $cmd;
            throw new LogicException("Unable to copy database '{$database}' from {$master_database}.");
        }

        $this->out($this->output, "Database '{$database} dumped from {$master_database}.'");
    }

    private function dbGarbageCollector()
    {
        // Garbage collector old databases
        $pdo       = new CommandLinePDO($this->db_host, $this->db_user, $this->db_pass);
        $databases = $pdo->getAllDatabases('branch_');
        foreach ($databases as $database) {
            if (!$this->isFeatureDatabase($database)) {
                continue;
            }
            if ($pdo->dropDatabase($database)) {
                $this->out($this->output, "Database '{$database}' removed.");
            } else {
                $this->out($this->output, "Unable to drop database '{$database}'.");
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function generateConfig()
    {
        $this->out($this->output, "Generate config.php");

        $file = $this->ci_project_dir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "config.php";

        touch($file);

        $configs = $this->getConfigs();

        foreach ($configs as $key => $value) {
            $config = new CMbConfig();
            $config->set($key, $value);
            $config->update($config->values);
        }
    }

    /**
     * @return void
     */
    private function generateTmp()
    {
        $this->out($this->output, "Generate tmp folder");
        CMbPath::forceDir("tmp");
        chmod("tmp", 0777);
    }


    /**
     *
     * @return string
     */
    private function getDatabaseName()
    {
        if ($this->database_name === null) {
            $database_name = 'branch_' . preg_replace('/\W/', '_', $this->branch_name);
            if ($this->isFeatureDatabase($database_name)) {
                $database_name .= '_' . $this->pipeline_id;
            }
            $this->database_name = $database_name;
        }

        return $this->database_name;
    }

    /**
     * @param string $database_name
     *
     * @return bool
     */
    private function isFeatureDatabase($database_name)
    {
        $result = false;
        foreach (static::FEATURE_BRANCH_NAME as $_name) {
            if (strpos($database_name, 'branch_' . $_name) === 0) {
                $result = true;
            }
        }

        return $result;
    }


    /**
     * @return array
     */
    private function getConfigs()
    {
        $root_dir  = $this->path;
        $ip_runner = $this->ip_runner ?: 'localhost';
        $base_url  = str_replace('/var/www/html', "http://{$ip_runner}/", $this->path);
        $this->out($this->output, "Pipeline url {$base_url}");

        $dPconfig                 = [];
        $dPconfig['root_dir']     = $root_dir;
        $dPconfig['product_name'] = 'Mediboard';
        $dPconfig['company_name'] = 'mediboard.org';
        $dPconfig['page_title']   = 'Mediboard SIH';
        $dPconfig['base_url']     = $base_url;
        //    $dPconfig['external_url']                                = $base_url;
        $dPconfig['instance_role']                               = 'qualif';
        $dPconfig['db_security_flag']                            = '1';
        $dPconfig['config_db']                                   = '1';
        $dPconfig['shared_memory']                               = 'apcu';
        $dPconfig['shared_memory_distributed']                   = '';
        $dPconfig['shared_memory_params']                        = '';
        $dPconfig['session_handler']                             = 'files';
        $dPconfig['session_handler_mutex_type']                  = 'files';
        $dPconfig['mutex_drivers CMbRedisMutex']                 = '0';
        $dPconfig['mutex_drivers CMbAPCMutex']                   = '0';
        $dPconfig['mutex_drivers CMbFileMutex']                  = '1';
        $dPconfig['mutex_drivers_params CMbRedisMutex']          = '';
        $dPconfig['mutex_drivers_params CMbFileMutex']           = '';
        $dPconfig['app_master_key_filepath']                     = '';
        $dPconfig['app_public_key_filepath']                     = '';
        $dPconfig['app_private_key_filepath']                    = '';
        $dPconfig['offline']                                     = '0';
        $dPconfig['offline_non_admin']                           = '0';
        $dPconfig['offline_time_start']                          = '';
        $dPconfig['offline_time_end']                            = '';
        $dPconfig['sourceCode selenium_browsers windows_chrome'] = '1';
        $dPconfig['db']['std']['dbtype']                         = 'mysql';
        $dPconfig['db']['std']['dbhost']                         = $this->db_host;
        $dPconfig['db']['std']['dbname']                         = $this->getDatabaseName();
        $dPconfig['db']['std']['dbuser']                         = $this->db_user;
        $dPconfig['db']['std']['dbpass']                         = $this->db_pass;

        $bdd = [
            'ameli',
            'ccam',
            'cim10',
            'cdarr',
            'compendium',
            'bcb1',
            'bcb2',
            'hl7v2',
            'drc',
            'cisp',
            'ccamV2',
            'ASIP',
            'csarr',
            'INSEE',
            'presta_ssr',
            'sae',
            'atih',
            'rpps_import',
            'sesam-vitale',
            'loinc',
            'lpp',
            'hospi_diag',
            'snomed'
        ];

        foreach ($bdd as $_bdd_name) {
            $dPconfig['db'][$_bdd_name]['dbtype'] = 'mysql';
            $dPconfig['db'][$_bdd_name]['dbhost'] = $this->db_host;
            $dPconfig['db'][$_bdd_name]['dbname'] = strtolower($_bdd_name);
            $dPconfig['db'][$_bdd_name]['dbuser'] = $this->db_user;
            $dPconfig['db'][$_bdd_name]['dbpass'] = $this->db_pass;
        }

        // Add BCB1 datasource
        $dPconfig['bcb']['CBcbObject']['dsn'] = 'bcb1';

        // hack sesam vitale
        $dPconfig['db']['sesam-vitale']['dbname'] = 'sesam_vitale';

        return $dPconfig;
    }

}
