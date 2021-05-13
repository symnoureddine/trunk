<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Synchronisation API test tool
 */
class SyncTest extends Command {
  /** @var OutputInterface */
  protected $output;

  /** @var InputInterface */
  protected $input;

  /** @var QuestionHelper */
  protected $question_helper;

  /** @var string Root path */
  protected $path;

  /** @var string Remote URL to test */
  protected $url;

  /** @var string API version to use */
  protected $version;

  /** @var string Username */
  protected $username;

  /** @var string Password */
  protected $password;

  /** @var string Remote URL with API call */
  protected $url_api;

  static $methods = array(
    'QUIT',
    'API_GET_USERS',
    'API_GET_FUNCTIONS',
    'API_GET_LOG',
    'API_GET_OBJECT',
    'API_UPDATE_DATA',
  );

  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('sync:test')
      ->setDescription('Synchronisation API test tool')
      ->setHelp('Synchronisation API test tool')
      ->addOption(
        'path',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Root path',
        realpath(__DIR__ . "/../../../")
      );
  }

  /**
   * @see parent::initialize()
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $style = new OutputFormatterStyle('blue', null, array('bold'));
    $output->getFormatter()->setStyle('b', $style);

    $style = new OutputFormatterStyle(null, 'red', array('bold'));
    $output->getFormatter()->setStyle('error', $style);
  }

  /**
   * Output timed text
   *
   * @param string $text Text to print
   *
   * @return void
   */
  protected function out($text) {
    $this->output->writeln(strftime("[%Y-%m-%d %H:%M:%S]") . " - $text");
  }

  /**
   * Display header information
   *
   * @return mixed
   */
  protected function showHeader() {
    $this->out('<fg=red;bg=black>' . $this->getDescription() . '</fg=red;bg=black>');
  }

  /**
   * Gets and sets arguments and options
   *
   * @throws Exception
   * @return void
   */
  protected function getParams() {
    $this->path = $this->input->getOption('path');

    if (!is_dir($this->path)) {
      throw new InvalidArgumentException("{$this->path} is not a valid directory.");
    }
  }

  /**
   * @param InputInterface  $input  Input Interface
   * @param OutputInterface $output Output Interface
   *
   * @return int|void|null
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->input           = $input;
    $this->output          = $output;
    $this->question_helper = $this->getHelper('question');

    $this->getParams();
    $this->showHeader();
    $this->setParams();

    $this->promptMethods();
  }

  /**
   * Sets parameters
   *
   * @return void
   */
  protected function setParams() {


    $url = $this->question_helper->ask(
      $this->input,
      $this->output,
      new Question(
        '<fg=red;bg=black>Please, enter URL to test [http://localhost/mediboard/]: </fg=red;bg=black>',
        'http://localhost/mediboard/'
      )
    );

    $this->version = $this->question_helper->ask(
      $this->input,
      $this->output,
      new Question(
        '<fg=red;bg=black>Please, enter API version to use [1]: </fg=red;bg=black>',
        '1'
      )
    );

    $this->username = $this->question_helper->ask(
      $this->input,
      $this->output,
      new Question(
        'Please, enter username: '
      )
    );

    $question = new Question('Please, enter password: ');
    $question->setHidden(true);

    $this->password = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    $this->url = rtrim($url, '/') . "/index.php?login={$this->username}:{$this->password}&m=sync";
  }

  /**
   * Prompts API methods to test
   *
   * @return void
   */
  protected function promptMethods() {

    $question = new ChoiceQuestion(
      '<fg=blue;bg=black>Select API method:</fg=blue;bg=black>',
      self::$methods,
      0
    );
    $question->setErrorMessage('Value "%s" is not valid');

    $selected = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    if (array_search($selected, self::$methods) === 0) {
      $this->out('Exiting...');
      exit(0);
    }

    $method = strtolower($selected);
    $this->request($method);
  }

  /**
   * Sends API request
   *
   * @param string $method API method to request
   *
   * @return void
   */
  protected function request($method) {
    $this->url_api = "{$this->url}&api_version={$this->version}&raw={$method}";
    $params        = array(
      'GET'  => array(),
      'POST' => array(),
    );

    switch ($method) {
      case 'api_get_log':
        $params['GET']['owner_id'] = $this->question_helper->ask(
          $this->input,
          $this->output,
          new Question('OWNER ID: ')
        );

        $object_types = array(
          '[ALL]',
          'plageConsult',
          'rdvConsult',
          'plageConges',
        );

        $object_type = $this->question_helper->ask(
          $this->input,
          $this->output,
          new ChoiceQuestion(
            'OBJECT TYPE [ALL]: ',
            $object_types,
            0
          )
        );

        if (array_search($object_type, $object_types) != 0) {
          $params['GET']['obj_type'] = $object_type;
        }

        $change_types = array(
          '[ALL]',
          'create',
          'update',
          'delete',
        );

        $change_type = $this->question_helper->ask(
          $this->input,
          $this->output,
          new ChoiceQuestion(
            'CHANGE TYPE [ALL]: ',
            $change_types,
            0
          )
        );

        if (array_search($change_type, $change_types) != 0) {
          $params['GET']['change_type'] = $change_type;
        }

        $params['GET']['start_id'] = $this->question_helper->ask(
          $this->input,
          $this->output,
          new Question(
            'START ID [0]: ',
            0
          )
        );

        $params['GET']['all'] = $this->question_helper->ask(
          $this->input,
          $this->output,
          new Question(
            'SEE ALL [0]: ',
            0
          )
        );

        $http_client = $this->initCURL($this->url_api, $params);
        break;

      case 'api_get_object':
        $object_types = array(
          'plageConsult',
          'rdvConsult',
          'plageConges',
        );

        $object_type = $this->question_helper->ask(
          $this->input,
          $this->output,
          new ChoiceQuestion(
            'OBJECT TYPE [rdvConsult]: ',
            $object_types,
            1
          )
        );

        $params['GET']['obj_type'] = $object_type;

        $params['GET']['obj_id'] = $this->question_helper->ask(
          $this->input,
          $this->output,
          new Question('OBJECT ID: ')
        );

        $http_client = $this->initCURL($this->url_api, $params);
        break;

      case 'api_update_data':
        $params['POST']['owner_id'] = $this->question_helper->ask(
          $this->input,
          $this->output,
          new Question('OWNER ID: ')
        );

        $object_types = array(
          'rdvConsult',
          'plageConsult',
          'plageConges',
          'patient',
        );

        $object_type = $this->question_helper->ask(
          $this->input,
          $this->output,
          new ChoiceQuestion(
            'OBJECT TYPE [rdvConsult]: ',
            $object_types,
            0
          )
        );

        $params['POST']['obj_type'] = $object_type;

        $change_types = array(
          'create',
          'update',
          'delete',
        );

        $change_type = $this->question_helper->ask(
          $this->input,
          $this->output,
          new ChoiceQuestion(
            'CHANGE TYPE [create]: ',
            $change_types,
            0
          )
        );

        $params['POST']['change_type'] = $change_type;

        $params['POST']['data'] = $this->getUpdateParams($params['POST']['obj_type']);

        $http_client = $this->initCURL($this->url_api, $params);
        break;

      default:
        $http_client = $this->initCURL($this->url_api);
    }

    $result = json_encode(json_decode(curl_exec($http_client)), JSON_PRETTY_PRINT);

    $this->out($result);
    $this->promptMethods();
  }

  /**
   * @param string $obj_type
   *
   * @return array
   */
  function getUpdateParams($obj_type = 'rdvConsult') {
    $params = array();

    switch ($obj_type) {
      case 'rdvConsult':
        break;
    }

    return $params;
  }

  /**
   * Initialises CURL client
   *
   * @param string $url    URL to request
   * @param array  $params Parameters to provides
   *
   * @return resource
   */
  function initCURL($url, $params = array()) {

    if ($params && isset($params['GET'])) {
      $url .= '&' . http_build_query($params['GET'], '', '&');
    }

    $http_client = curl_init($url);
    curl_setopt($http_client, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($http_client, CURLOPT_TIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($http_client, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($http_client, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($http_client, CURLOPT_FRESH_CONNECT, true);

    if (isset($params['POST']) && !empty($params['POST'])) {
      $data = json_encode($params['POST']);

      curl_setopt($http_client, CURLOPT_POSTFIELDS, $data);
      curl_setopt(
        $http_client,
        CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data))
      );
    }

    return $http_client;
  }
}
