<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Error;
use ErrorException;
use Exception;
use Ox\Core\Kernel\CKernel;
use Ox\Mediboard\System\CErrorLog;
use Ox\Mediboard\System\CErrorLogWhiteList;
use Ox\Mediboard\System\Cron\CCronJobLog;

/**
 * Error manager
 */
class CError
{
    /** @var int */
    const LOG_SIZE_LIMIT = 5242880; // 1024*1024*5

    /** @var string */
    const PATH_TMP_BUFFER = "/tmp/errors/";

    /** @var array */
    static $output = [];

    /** @var array */
    static $_excluded = [
        E_STRICT,
        E_DEPRECATED,        // BCB
        E_RECOVERABLE_ERROR, // Thrown by bad type hinting, to be removed
    ];

    /** @var array This errors will be thrown after converted in ErrorException */
    static $_error_throw = [
        E_ERROR,
    ];

    /**
     * @var array
     */
    public static $buffered_signatures = [];


    /**
     * @var array
     */
    static $_types = [
        "exception"         => "exception",
        E_ERROR             => "error",
        E_WARNING           => "warning",
        E_PARSE             => "parse",
        E_NOTICE            => "notice",
        E_CORE_ERROR        => "core_error",
        E_CORE_WARNING      => "core_warning",
        E_COMPILE_ERROR     => "compile_error",
        E_COMPILE_WARNING   => "compile_warning",
        E_USER_ERROR        => "user_error",
        E_USER_WARNING      => "user_warning",
        E_USER_NOTICE       => "user_notice",
        E_STRICT            => "strict",
        E_RECOVERABLE_ERROR => "recoverable_error",
        E_DEPRECATED        => "deprecated",
        E_USER_DEPRECATED   => "user_deprecated",
    ];

    static $_classes = [
        "exception"         => "big-warning",
        E_ERROR             => "big-error",   // 1
        E_WARNING           => "big-warning", // 2
        E_PARSE             => "big-info",    // 4
        E_NOTICE            => "big-info",    // 8
        E_CORE_ERROR        => "big-error",   // 16
        E_CORE_WARNING      => "big-warning", // 32
        E_COMPILE_ERROR     => "big-error",   // 64
        E_COMPILE_WARNING   => "big-warning", // 128
        E_USER_ERROR        => "big-error",   // 256
        E_USER_WARNING      => "big-warning", // 512
        E_USER_NOTICE       => "big-info",    // 1024
        E_STRICT            => "big-info",    // 2048
        E_RECOVERABLE_ERROR => "big-error",   // 4096
        E_DEPRECATED        => "big-info",    // 8192
        E_USER_DEPRECATED   => "big-info",    // 16384
        // E_ALL = 32767 (PHP 5.4)
    ];

    static $_categories = [
        "exception"         => "warning",
        E_ERROR             => "error",
        E_WARNING           => "warning",
        E_PARSE             => "error",
        E_NOTICE            => "notice",
        E_CORE_ERROR        => "error",
        E_CORE_WARNING      => "warning",
        E_COMPILE_ERROR     => "error",
        E_COMPILE_WARNING   => "warning",
        E_USER_ERROR        => "error",
        E_USER_WARNING      => "warning",
        E_USER_NOTICE       => "notice",
        E_STRICT            => "notice",
        E_RECOVERABLE_ERROR => "error",
        E_DEPRECATED        => "notice",
        E_USER_DEPRECATED   => "notice",
    ];

    /**
     * @var CLogger $logger
     */
    private static $logger;

    /**
     * @var null
     */
    private static $current_file_buffer;

    /**
     * @var bool
     */
    public static $is_error_handler_throw_exception = false;

    /**
     * Error handlers and configuration
     */
    public static function init(bool $is_api = false)
    {
        global $dPconfig;
        // Do not set to E_STRICT as it hides fatal errors to our error handler

        // Developement
        //error_reporting(E_ALL | E_STRICT | E_USER_DEPRECATED | E_DEPRECATED);

        // Production
        error_reporting(E_ALL);

        ini_set("log_errors_max_len", "4M");
        ini_set("log_errors", true);
        ini_set("display_errors", $dPconfig["debug"]);

        set_error_handler([static::class, 'errorHandler']);
        set_exception_handler([static::class, 'exceptionHandler']);

        CApp::registerShutdown([static::class, "onShutdown"], CApp::ERROR_PRIORITY);

        self::$is_error_handler_throw_exception = $is_api;
    }

    /**
     * Get error types by level : error, warning and notice
     *
     * @return array
     */
    static function getErrorTypesByCategory()
    {
        $categories = [
            "error"   => [],
            "warning" => [],
            "notice"  => [],
        ];

        foreach (self::$_categories as $_type => $_category) {
            $categories[$_category][] = self::$_types[$_type];
        }

        return $categories;
    }

    /**
     * Create a link to open the file in an IDE
     *
     * @param string $file File to open in the IDE
     * @param int    $line Line number
     *
     * @return string
     */
    static function openInIDE($file, $line = null)
    {
        global $dPconfig;

        $url = null;

        $ide_url = (!empty($dPconfig["dPdeveloppement"]["ide_url"]) ? $dPconfig["dPdeveloppement"]["ide_url"] : false);
        if ($ide_url) {
            $url = str_replace("%file%", urlencode($file), $ide_url) . ":$line";
        } else {
            $ide_path = (!empty($dPconfig["dPdeveloppement"]["ide_path"]) ? $dPconfig["dPdeveloppement"]["ide_path"] : false);
            if ($ide_path) {
                $url = "ide:" . urlencode($file) . ":$line";
            }
        }

        if ($url) {
            $file = $line ? $file . ':' . $line : $file;
            $file = str_replace(CAppUI::conf("root_dir"), "", $file);
            $file = $file[0] == DIRECTORY_SEPARATOR ? substr($file, 1) : $file;

            return "<a target=\"ide-launch-iframe\" title=\"Open script in IDE\" href=\"$url\">$file</a>";
        }

        return $file;
    }

    /**
     * @return CLogger $logger
     * @throws Exception
     */
    static function getLogger()
    {
        if (is_null(static::$logger)) {
            $root_dir = CAppUI::conf('root_dir');
            $dir      = $root_dir . static::PATH_TMP_BUFFER;

            CMbPath::forceDir($dir);

            $file = $dir . CApp::getRequestUID() . ".log";

            $logger = new CLogger(CLogger::CHANNEL_ERROR);
            $logger->setJsonFormatter();
            $logger->setStreamFile($file);

            static::$current_file_buffer = $file;
            static::$logger              = $logger;
        }

        return static::$logger;
    }

    /**
     * @return array|false
     */
    static function globWaitingBuffer()
    {
        $root_dir = CAppUI::conf('root_dir');

        return glob($root_dir . static::PATH_TMP_BUFFER . "*.log", GLOB_BRACE);
    }

    static function clearErrorBuffer(){
       foreach (self::globWaitingBuffer() as $buffer){
           unlink($buffer);
       }
       self::$current_file_buffer = null;
    }

    static function countWaitingBuffer(){
        return count(static::globWaitingBuffer());
    }

    /**
     * @return array|false
     * @deprecated
     */
    static function getFirstFileInWaitingBuffer()
    {
        $root_dir  = CAppUI::conf('root_dir');
        $dir       = $root_dir . static::PATH_TMP_BUFFER;
        $file      = is_dir($dir) ? CMbPath::getFirstFile($dir) : false;
        $file_path = $dir . $file;

        return is_file($file_path) ? $file_path : false;
    }

    /**
     * Because fata error (memory exhausted, max execution time ..) triggers script termination :
     * Shutdown function log uncatched error
     *
     * @throws Exception
     */
    static function logLastError()
    {
        $error = error_get_last();
        if (!is_null($error) && class_exists(CApp::class, false)) {
            $type = self::$_types[$error['type']];
            CApp::log("Uncatched {$type}", $error, CLogger::LEVEL_CRITICAL);
        }
    }


    /**
     * Store error buffer in db
     *
     * @param null $file_buffer
     * @param bool $close_ressource
     *
     * @return bool
     * @throws Exception
     *
     */
    static function storeBuffer($file_buffer = null, $close_ressource = true)
    {
        // Check file
        if (is_null($file_buffer) || !is_file($file_buffer)) {
            return false;
        }

        // Close ressource
        if ($close_ressource && self::$logger) {
            self::$logger->getLogger()->close();
        }

        // Readonly
        if (CApp::isReadonly()) {
            return false;
        }

        // Load whitelist signatures
        $whiteList = new CErrorLogWhiteList();
        if ($whiteList->isInstalled()) {
            $whitelist_hash = $whiteList->loadColumn('hash');
        } else {
            $whitelist_hash = [];
        }

        // First loop : prepare data to store
        $lines         = @file($file_buffer);
        $lines         = is_array($lines) ? $lines : [];
        $data_to_store = [];
        foreach ($lines as $_num => $_line) {
            $_line          = json_decode($_line, true);
            $_context       = CLogger::decodeContext($_line['context']);
            $signature_hash = $_context['signature_hash'];

            // When unbuffered on shutdown => final count is known
            $_final_count = static::$buffered_signatures[$signature_hash] ?? null;

            // First signature find in buffer
            if (!array_key_exists($signature_hash, $data_to_store)) {
                $data_to_store[$signature_hash] = [
                    'user_id'         => $_context['user_id'],
                    'server_ip'       => $_context['server_ip'],
                    'time'            => $_context['time'],
                    'request_uid'     => $_context['request_uid'],
                    'type'            => $_context['type'],
                    'text'            => $_context['text'],
                    'file'            => $_context['file'],
                    'line'            => $_context['line'],
                    'data'            => $_context['data'],
                    'count_in_buffer' => 1,
                    'count'           => $_final_count ?? 1,
                ];
                continue;
            }

            // Next signatures found in buffer
            if ($_final_count === null) {
                // When unbuffered after crash => chose lower range approximate counter
                $_aproximate_count                       = 2 ** $data_to_store[$signature_hash]['count_in_buffer'];
                $data_to_store[$signature_hash]['count'] = $_aproximate_count;

                // Increase after calculate (we log the first error in buffer)
                $data_to_store[$signature_hash]['count_in_buffer']++;
            }
        }

        // Second loop : store data
        foreach ($data_to_store as $signature_hash => $data) {
            // Cron logging
            if (CApp::isCron()) {
                $error_data = "{$data['text']} : {$data['file']} l. {$data['line']}";

                switch ($data['type']) {
                    case 'notice':
                        CCronJobLog::logInfo($error_data);
                        break;
                    case 'exception':
                        CCronJobLog::logWarning($error_data);
                        break;
                    case 'user_error':
                        CCronJobLog::logError($error_data);
                        break;
                    default:
                        // Do nothing
                }
            }

            // Whitelisted ?
            if (in_array($signature_hash, $whitelist_hash, true)) {
                $wl       = new CErrorLogWhiteList();
                $wl->hash = $signature_hash;
                $wl->loadMatchingObject();
                $wl->count += $data['count'];
                $wl->store();

                continue;
            }

            // Store
            try {
                CErrorLog::insert(
                    $data['user_id'],
                    $data['server_ip'],
                    $data['time'],
                    $data['request_uid'],
                    $data['type'],
                    $data['text'],
                    $data['file'],
                    $data['line'],
                    $signature_hash,
                    $data['count'],
                    $data['data']
                );
            } catch (Exception $e) {
                CApp::log($e->getMessage(), null, CLogger::CHANNEL_ERROR);
            }
        }

        // unlink buffer stored
        return @unlink($file_buffer);
    }


    /**
     * @throws Exception
     */
    static function onShutdown()
    {
        static::logLastError();
        static::storeBuffer(static::$current_file_buffer);
    }


    /**
     * Custom error handler
     *
     * @param string $code Error code
     * @param string $text Error text
     * @param string $file Error file path
     * @param string $line Error line number
     *
     * @throws Exception
     *
     */
    public static function errorHandler($code, $text, $file, $line)
    {
        // Handles the @ case and ignored error
        $error_reporting = error_reporting();
        if (!$error_reporting || in_array($code, CError::$_excluded)) {
            // Log ?
            if ((CDevtools::isActive() && CDevtools::getLevel() >= CDevtools::LEVEL_VERY_VERBOSE)
                && !str_contains($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
            ) {
                $type   = CError::$_types[$code];
                $detail = !$error_reporting ? "error_reporting disabled" : "error code excluded";
                CApp::log(
                    "Ignored {$type} ({$detail})",
                    [
                        "code" => $code,
                        "text" => $text,
                        "file" => $file,
                        "line" => $line,
                    ],
                    CLogger::LEVEL_ERROR
                );
            }

            return;
        }

        $exception = new ErrorException($text, 0, $code, $file, $line);

        // Api mode (compat with CExceptionListener)
        if (self::$is_error_handler_throw_exception === true) {
            throw $exception;
        }

        static::logException($exception);
    }

    /**
     * @return null
     */
    public static function getCurrentFileBuffer()
    {
        return static::$current_file_buffer;
    }


    /**
     * Custom throwable handler
     * CExceptionListener do not catch Internal PHP Errors
     *
     * @param Exception $exception
     *
     * @throws Exception
     */
    public static function exceptionHandler($exception)
    {
        if (static::$is_error_handler_throw_exception && $exception instanceof Error) {
            CKernel::getInstance()->terminateWithException(new Exception($exception->getMessage()));

            return;
        }

        static::logException($exception);
    }

    /**
     * @param Exception $exception
     *
     * @param bool      $display_errors
     *
     * @throws Exception
     */
    public static function logException($exception, $display_errors = true)
    {
        $time = date("Y-m-d H:i:s");

        // User information
        $user_id   = null;
        $user_view = "";
        if (class_exists(CAppUI::class, false) && CAppUI::$user) {
            $user = CAppUI::$user;
            if ($user->_id) {
                $user_id   = $user->_id;
                $user_view = $user->_view;
            }
        }

        // Devtools
        if (CDevtools::isActive()) {
            // when application does not in peace we receive this ErrorException
            if ($exception instanceof ErrorException && $exception->getMessage() === 'Application died unexpectedly') {
                CDevtools::makeTmpFile();
            } else {
                CApp::error($exception);
            }
        }

        // Server IP
        $server_ip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : null;

        $file = CMbPath::getRelativePath($exception->getFile());
        $line = $exception->getLine();

        // Stacktrace
        $contexts = $exception->getTrace();
        foreach ($contexts as &$ctx) {
            unset($ctx['args'], $ctx['object']);
        }

        $code = "exception";
        // ErrorException is send by errorHandler, we change the type for ui
        if ($exception instanceof ErrorException) {
            $code = $exception->getSeverity();
            array_shift($contexts);
        }
        $type = isset(self::$_types[$code]) ? self::$_types[$code] : null;
        $text = $exception->getMessage();

        // Might noy be ready at the time error is thrown
        $session = isset($_SESSION) ? $_SESSION : [];
        unset($session['AppUI']);
        unset($session['dPcompteRendu']['templateManager']);

        $_all_params = [
            "GET"     => $_GET,
            "POST"    => $_POST,
            "SESSION" => $session,
        ];

        filterInput($_all_params);

        // CApp might not be ready yet as of early error handling
        $request_uid = null;
        if (class_exists(CApp::class, false)) {
            $request_uid = CApp::getRequestUID();
            CApp::$performance[self::$_categories[$code]]++;
        }

        // Signature hash
        $release_info   = CApp::getReleaseInfo();
        $revision       = $release_info['revision'] ?? null;
        $signature      = [
            'type'     => $type,
            'text'     => utf8_encode($text),
            'file'     => $file,
            'line'     => $line,
            'revision' => $revision,
        ];
        $signature_hash = md5(serialize($signature));

        $data_log = [
            "microtime"      => microtime(),
            "user_id"        => $user_id,
            "server_ip"      => $server_ip,
            "time"           => $time,
            "request_uid"    => $request_uid,
            "type"           => $type,
            "text"           => utf8_encode($text),
            "file"           => $file,
            "line"           => $line,
            "signature_hash" => $signature_hash,
            "data"           => [
                "stacktrace"   => $contexts,
                "param_GET"    => $_all_params["GET"],
                "param_POST"   => $_all_params["POST"],
                "session_data" => $_all_params["SESSION"],
            ],
        ];

        // Increase counter (signatures handled)
        if (!array_key_exists($signature_hash, self::$buffered_signatures)) {
            self::$buffered_signatures[$signature_hash] = 1;
        } else {
            self::$buffered_signatures[$signature_hash]++;
        }

        // Error logs buffuring (log only exponential)
        $current_count = self::$buffered_signatures[$signature_hash];
        if ($current_count === 1 || CMbMath::isValidExponential(2, $current_count)) {
            $logger = self::getLogger();
            $logger->log(get_class($exception), $data_log, CLogger::LEVEL_ERROR);
        }

        // Output
        if (ini_get("display_errors") && $display_errors && PHP_SAPI !== 'cli') {
            $html_class = isset(CError::$_classes[$code]) ? CError::$_classes[$code] : null;
            $html_class = str_replace('big-', 'small-', $html_class);
            $log        = "\n\n<div class='$html_class'>";

            if ($user_id) {
                $log .= "\n<strong>User: </strong>$user_view ($user_id)";
            }

            $log .= "<strong>Time: </strong>{$time} <strong>Type: </strong>{$type} <strong>Text: </strong>{$text} ";
            $log .= "<strong>File: </strong>{$file}:{$line}";
            $log .= "</div>";

            echo $log;
        }
    }
}
