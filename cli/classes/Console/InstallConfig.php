<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Ox\Cli\MediboardCommand;
use Ox\Core\CMbConfig;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DeployRunner
 *
 * @package Ox\Cli\Console
 */
class InstallConfig extends MediboardCommand {

  /** @var OutputInterface */
  protected $output;

  /** @var InputInterface */
  protected $input;

  /** @var string */
  protected $path;

  /** @var QuestionHelper */
  protected $question_helper;

  /** @var array */
  private $configs = [];

  /** @var CMbConfig */
  private $mbConfig;

  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('install:config')
      ->setDescription('Install OX configuration')
      ->addOption(
        'path',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Working copy root',
        dirname(__DIR__, 3) . '/'
      );
  }

  /**
   * @see parent::showHeader()
   */
  protected function showHeader() {
    $this->out($this->output, '<fg=yellow;bg=black>OX configurations setting</fg=yellow;bg=black>');
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int|void|null
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output          = $output;
    $this->input           = $input;
    $this->question_helper = $this->getHelper('question');
    $this->path            = $input->getOption('path');
    $this->showHeader();

    if (!is_dir($this->path)) {
      throw new InvalidArgumentException("'$this->path' is not a valid directory.");
    }

    if (file_exists($this->path . CMbConfig::CONFIG_FILE)) {
      throw new LogicException("The configuration file {$this->path} already exists.");
    }
    $this->mbConfig = new CMbConfig($this->path);

    if (!$this->askQuestions()) {
      return false;
    }

    $make_config = $this->addDefaultConfigs()
      ->convertConfigs()
      ->storeConfigs();

    if (!$make_config || !$this->mbConfig->isConfigFileExists()) {
      throw new LogicException('Configuration registrations failed');
    }

    $output->writeln('<info>Configurations successfully saved !</info>');
  }

  /**
   * legacy compat with old /install/04_configur.php
   * @return InstallConfig
   */
  private function addDefaultConfigs(): InstallConfig {
    $this->configs = array_merge($this->configs, [
      'config_db'            => 0, // @todo ref fw
      'servers_ip'           => null,
      'offline'              => 0,
      'offline_non_admin'    => 0,
      'db'                   => [
        'std' => [
          'dbtype' => 'mysql'
        ]
      ],
      'mutex_drivers'        => [
        'CMbRedisMutex' => 0,
        'CMbAPCMutex'   => 0,
        'CMbFileMutex'  => 0,
      ],
      'mutex_drivers_params' => [
        'CMbRedisMutex' => null
      ],
      'migration'            => [
        'active' => 0
      ],
      'offline_time_start'   => null,
      'offline_time_end'     => null,
    ]);

    return $this;
  }

  /**
   * legacy convert key
   * @return InstallConfig
   */
  private function convertConfigs(): InstallConfig {
    // db
    $this->configs['db']['std']['dbhost'] = $this->configs['database_host'];
    unset($this->configs['database_host']);

    $this->configs['db']['std']['dbname'] = $this->configs['database_name'];
    unset($this->configs['database_name']);

    $this->configs['db']['std']['dbuser'] = $this->configs['database_user'];
    unset($this->configs['database_user']);

    $this->configs['db']['std']['dbpass'] = $this->configs['database_pass'];
    unset($this->configs['database_pass']);

    // mutex
    $this->configs['mutex_drivers']['CMbRedisMutex'] = $this->configs['mutex_driver_redis'] ? 1 : 0;
    unset($this->configs['mutex_driver_redis']);

    $this->configs['mutex_drivers']['CMbAPCMutex'] = $this->configs['mutex_driver_apc'] ? 1 : 0;
    unset($this->configs['mutex_driver_apc']);

    $this->configs['mutex_drivers']['CMbFileMutex'] = $this->configs['mutex_driver_files'] ? 1 : 0;
    unset($this->configs['mutex_driver_files']);

    $this->configs['mutex_drivers_params']['CMbRedisMutex'] = $this->configs['mutex_drivers_params'];
    unset($this->configs['mutex_drivers_params']);

    return $this;
  }

  /**
   * @return bool|null
   * @throws Exception
   */
  private function storeConfigs(): ?bool {
    return $this->mbConfig->update($this->configs, false);
  }


  /**
   * @return mixed
   */
  private function askQuestions() {
    // general
    $this->configs['product_name']  = $this->ask(new Question('Please enter the product name: ', 'Mediboard'));
    $this->configs['company_name']  = $this->ask(new Question('Enter the company name: ', 'OpenXtrem'));
    $this->configs['page_title']    = $this->ask(new Question('Enter the page title: ', 'Mediboard SIH'));
    $this->configs['root_dir']      = $this->ask(new Question('Enter the root directory: ', dirname(__DIR__, 3)));
    $this->configs['base_url']      = $this->ask(new Question('Enter the base url: ', 'http://127.0.0.1/mediboard'));
    $this->configs['external_url']  = $this->ask(new Question('Enter the external url: ', 'http://127.0.0.1/mediboard'));
    $this->configs['instance_role'] = $this->ask(new ChoiceQuestion('Select instance role: ', ['qualif', 'prod'], 0));

    // database
    $this->configs['database_host'] = $this->ask(new Question('Enter the database host: ', '127.0.0.1'));
    $this->configs['database_name'] = $this->ask(new Question('Enter the database name: ', 'mediboard'));
    $this->configs['database_user'] = $this->ask(new Question('Enter the database user: ', 'root'));
    $this->configs['database_pass'] = $this->ask(new Question('Enter the database password: ', ''), true);

    // memory
    $this->configs['shared_memory'] = $this->ask(new ChoiceQuestion('Select local shared memory: ', ['disk', 'apcu'], 1));

    $this->configs['shared_memory_distribued'] = $this->ask(new ChoiceQuestion('Select distribued shared memory: ', ['disk', 'redis'], 0));
    if ($this->configs['shared_memory_distribued'] === 'redis') {
      $this->configs['shared_memory_params'] = $this->ask(new Question('Enter the redis params (ex: 192.168.1.39:6379, 192.168.1.40:6379): ', ''));
    }
    else {
      $this->configs['shared_memory_params'] = '';
    }

    // session
    $this->configs['session_handler'] = $this->ask(new ChoiceQuestion('Select session handler: ', ['files', 'redis', 'mysql'], 0));
    if ($this->configs['session_handler'] === 'mysql') {
      $this->configs['session_handler_mutex_type'] = $this->ask(new ChoiceQuestion('Select mutex session mysql: ', ['mysql', 'files', 'system'], 0));
    }
    else {
      $this->configs['session_handler_mutex_type'] = '';
    }

    // mutex
    $this->configs['mutex_driver_files'] = $this->ask(new ConfirmationQuestion('Enable mutex driver files [Y/n] ?', true));
    $this->configs['mutex_driver_apc']   = $this->ask(new ConfirmationQuestion('Enable mutex driver apc [y/N] ?', false));
    $this->configs['mutex_driver_redis'] = $this->ask(new ConfirmationQuestion('Enable mutex driver redis [y/N] ?', false));
    if ($this->configs['mutex_driver_redis']) {
      $this->configs['mutex_drivers_params'] = $this->ask(new Question('Enter the redis params (ex: 127.0.0.1:6379): ', ''));
    }
    else {
      $this->configs['mutex_drivers_params'] = '';
    }

    // resume
    $configs_resume = [];
    foreach ($this->configs as $key => $value) {
      if ($key === 'database_pass') {
        $value = str_repeat('*', strlen($value));
      }
      $configs_resume[] = [$key, $value];
    }

    $io = new SymfonyStyle($this->input, $this->output);
    $io->table(['config', 'value'], $configs_resume);

    // confirm
    return $this->ask(new ConfirmationQuestion('Do you confirm this settings [Y/n] ?', true));
  }

  /**
   * @param Question $question
   *
   * @param bool     $hidden
   *
   * @return mixed
   */
  private function ask(Question $question, $hidden = false) {
    if ($hidden && !(defined('PHPUNIT_MEDIBOARD_TESTSUITE') && PHPUNIT_MEDIBOARD_TESTSUITE)) {
      $question->setHidden(true);
    }

    return $this->question_helper->ask($this->input, $this->output, $question);
  }

  /**
   * @return array
   */
  public function getConfigs(): array {
    return $this->configs;
  }
}