<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Exception;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

/**
 * Interface with monolog
 */
class CLogger {

  const LEVEL_DEBUG = 100;
  const LEVEL_INFO = 200;
  const LEVEL_NOTICE = 250;
  const LEVEL_WARNING = 300;
  const LEVEL_ERROR = 400;
  const LEVEL_CRITICAL = 500;
  const LEVEL_ALERT = 550;
  const LEVEL_EMERGENCY = 600;

  const CHANNEL_ERROR = "error";
  const CHANNEL_MEDIBOARD = "mediboard";
  const CHANNEL_ACCESS = "access";
  const CHANNEL_LONG_REQUEST = "long_request";


  /**
   * @var String
   */
  private $channel;

  /**
   * @var \Monolog\Logger $logger
   */
  private $logger;

  /**
   * @var string $file_path
   */
  private $file_path;

  /**
   * @var mixed $formatter
   */
  private $formatter;

  private $necessary_encode = false;

  /**
   * CLogger constructor.
   *
   * @param string $channel Use const CHANNEL_NAME
   */
  public function __construct($channel) {
    $this->channel = $channel;
    $this->logger  = new Logger($channel);
  }

  /**
   * @return array
   */
  static function getLevels() {
    return array(
      self::LEVEL_DEBUG     => "debug",
      self::LEVEL_INFO      => "info",
      self::LEVEL_NOTICE    => "notice",
      self::LEVEL_WARNING   => "warning",
      self::LEVEL_ERROR     => "error",
      self::LEVEL_CRITICAL  => "critical",
      self::LEVEL_ALERT     => "alert",
      self::LEVEL_EMERGENCY => "emergency",
    );
  }

  /**
   * @return array
   */
  static function getLevelsColors() {
    return array(
      'DEBUG'     => "DimGray",
      'INFO'      => "black",
      'NOTICE'    => "DodgerBlue",
      'WARNING'   => "orange",
      'ERROR'     => "DarkOrange",
      'CRITICAL'  => "Tomato",
      'ALERT'     => "red",
      'EMERGENCY' => "DarkViolet",
    );
  }

  /**
   *
   */
  public function setJsonFormatter() {
    $this->formatter = new JsonFormatter();
  }

  /**
   * @param string      $output     The output mask format
   * @param string|null $dateFormat The format of the timestamp: one supported by DateTime::format
   */
  public function setLineFormatter($output = null, $dateFormat = null) {
    $this->necessary_encode = true;
    $this->formatter        = new LineFormatter($output, $dateFormat);
  }

  /**
   * Add extra data (user_id, server_ip, session_id)
   */
  public function setMediboardProcessor() {
    $this->logger->pushProcessor(function ($record) {
      $record['extra']['user_id']    = (CAppUI::$user) ? CAppUI::$user->user_id : null;
      $record['extra']['server_ip']  = isset($_SERVER["SERVER_ADDR"]) ? isset($_SERVER["SERVER_ADDR"]) : null;
      $record['extra']['session_id'] = CMbString::truncate(session_id(), 15);

      return $record;
    });
  }

  public function setIntrospectionProcessor() {
    $processor = new IntrospectionProcessor(self::LEVEL_EMERGENCY, array(), 2);
    $this->logger->pushProcessor($processor);
  }


  /**
   *
   */
  public function setHtmlFormatter() {
    $this->formatter = new HtmlFormatter();
  }

  /**
   * @param string $file
   *
   * @throws Exception
   */
  public function setStreamFile($file) {
    $this->file_path = $file;
    $stream          = new StreamHandler($file);

    if ($this->formatter) {
      $stream->setFormatter($this->formatter);
    }

    $this->logger->pushHandler($stream);
  }

  /**
   * @return Logger
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * @return string
   */
  public function getFilePath() {
    return $this->file_path;
  }

  /**
   * Cast object to array|string
   * Encode from Windows-1252 to UTF-8
   *
   * @param array $context
   *
   * @return array $context
   */
  protected function encodeContext($context) {
    // cast
    array_walk_recursive($context, function (&$item) {
      if (is_object($item)) {
        $class = get_class($item);
        $item  = $item instanceof CModelObject ? array($class => $item->getPlainFields()) : array($class => get_object_vars($item));
      }
      elseif (is_resource($item)) {
        $type = get_resource_type($item);
        $item = array('ressource' => $type);
      }
    });

    // encode
    array_walk_recursive($context, function (&$item) {
      $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1252');
    });

    return $context;
  }

  /**
   * Decode from UTF-8 to Windows-1252
   *
   * @param array $context
   *
   * @return array $context
   */
  public static function decodeContext($context) {
    if (!$context || !is_array($context)) {
      return $context;
    }

    array_walk_recursive($context, function (&$item) {
      $item = mb_convert_encoding($item, 'Windows-1252', 'UTF-8');
    });

    return $context;
  }

  /**
   * @param string $message
   * @param array  $context
   * @param int    $level
   *
   * @return bool
   */
  public function log($message, $context = null, $level = self::LEVEL_DEBUG) {
    $levels = self::getLevels();
    if (!array_key_exists($level, $levels)) {
      return false;
    }

    $context = is_array($context) ? $context : array();

    if ($this->necessary_encode) {
      $context = $this->encodeContext($context);
    }

    return $this->logger->log($level, $message, $context);
  }

}
