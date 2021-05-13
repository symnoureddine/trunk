<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Ox\Cli\CommandLinePDO;
use Ox\Cli\MediboardCommand;
use Ox\Core\CAppUI;
use Ox\Core\CMbConfig;
use Ox\Mediboard\Admin\CUser;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DeployRunner
 *
 * @package Ox\Cli\Console
 */
class InstallDatabase extends MediboardCommand {

  /** @var OutputInterface */
  protected $output;

  /** @var InputInterface */
  protected $input;

  /** @var string */
  protected $path;

  /** @var QuestionHelper */
  protected $question_helper;

  /** @var array */
  protected $params = [];

  /** @var CMbConfig */
  protected $config;

  /** @var CommandLinePDO */
  protected $pdo;


  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('install:database')
      ->setDescription('Install OX database')
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
    $this->out($this->output, '<fg=yellow;bg=black>OX database installation</fg=yellow;bg=black>');
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

    // includes configs (legacy)
    require __DIR__ . '/../../../includes/config_all.php';

    if (!$this->askQuestions()) {
      return false;
    }

    // process
    $this->checkConnexion()
      ->checkDatabase()
      ->createDatabase()
      ->createTables()
      ->updateUsers();

    $output->writeln('<info>Database successfully created !</info>');
  }


  /**
   * @return mixed
   * @throws Exception
   */
  private function askQuestions() {
    $is_from_config = false;
    if (file_exists($this->path.CMbConfig::CONFIG_FILE)) {
      $is_from_config = $this->ask(new ConfirmationQuestion('Do you want to use the database parameters saved in the OX configuration file [Y/n] ?', true));
    }

    if ($is_from_config) {
      $this->params['host']     = CAppUI::conf('db std dbhost');
      $this->params['database'] = CAppUI::conf('db std dbname');
      $this->params['user']     = CAppUI::conf('db std dbuser');
      $this->params['password'] = CAppUI::conf('db std dbpass');
    }
    else {
      // ask
      $this->params['host']     = $this->ask(new Question('Please enter the host name: ', 'localhost'));
      $this->params['database'] = $this->ask(new Question('Enter the database name: ', 'mediboard'));
      $this->params['user']     = $this->ask(new Question('Enter the user name: ', 'root'));
      $this->params['password'] = $this->ask(new Question('Enter the user password: '), true);
    }

    // password admin
    $question = new Question('Enter the admin account password: ');
    $question->setValidator(function ($answer) {
      if (!preg_match('/^S*(?=\S{6,})(?=\S*[a-zA-Z])(?=\S*[\d])\S*$/', $answer)) {
        throw new \RuntimeException(
          'The password should match min 6 characters alpha & numeric'
        );
      }

      return $answer;
    });
    $question->setMaxAttempts(3);
    $this->params['admin_password'] = $this->ask($question, true);

    // resume
    $params_resume = [];
    foreach ($this->params as $key => $value) {
      if ($key === 'password' || $key === 'admin_password') {
        $value = str_repeat('*', strlen($value));
      }
      $params_resume[] = [$key, $value];
    }

    $io = new SymfonyStyle($this->input, $this->output);
    $io->table(['config', 'value'], $params_resume);

    // confirm
    return $this->ask(new ConfirmationQuestion('Do you confirm this settings, and create the database [Y/n] ?', true));
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
   * Create pdo & check connexion
   * @return InstallDatabase
   * @throws Exception
   */
  private function checkConnexion(): InstallDatabase {
    try {
      $this->pdo = new CommandLinePDO($this->params['host'], $this->params['user'], $this->params['password']);
    }
    catch (Exception $e) {
      if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE) {
        throw $e;
      }
      throw new LogicException("Unable to connect to mysql:host={$this->params['host']}.");
    }

    return $this;
  }

  /**
   * @return InstallDatabase
   * @throws Exception
   */
  private function checkDatabase(): InstallDatabase {
    // name
    if (!preg_match('/^[A-Z0-9_-]+$/i', $this->params['database'])) {
      throw new LogicException('Invalid database name (A-Z0-9_).');
    }

    // already exists
    if ($this->pdo->isDatabaseExists($this->params['database'])) {
      throw new LogicException("Database {$this->params['database']} already exists.");
    }

    return $this;
  }

  /**
   * @return InstallDatabase
   * @throws Exception
   */
  private function createDatabase(): InstallDatabase {

    if (!$this->pdo->createDatabase($this->params['database'])) {
      throw new LogicException("Unable to create database {$this->params['database']}.");
    }

    // use database
    $this->pdo = new CommandLinePDO($this->params['host'], $this->params['user'], $this->params['password'], $this->params['database']);

    return $this;
  }

  /**
   * @return InstallDatabase
   */
  private function createTables(): InstallDatabase {
    if (!$this->pdo->createTables()) {
      throw new LogicException("Unable to create tables in database {$this->params['database']}.");
    }

    return $this;
  }


  /**
   * @return InstallDatabase
   */
  private function updateUsers(): InstallDatabase {
    $salt     = CUser::createSalt();
    $password = CUser::saltPassword($salt, $this->params['admin_password']);

    if (!$this->pdo->updateUsers($salt, $password)) {
      throw new LogicException('Unable to update user admin.');
    }

    return $this;
  }
}