<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CMbArray;
use Ox\Interop\Eai\CExchangeDataFormat;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Chronometer;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbString;

/**
 * Class CSourceFileSystem
 */
class CSourceFileSystem extends CExchangeSource
{
    // Source type
    public const TYPE = 'file_system';

    // DB Table key
    public $source_file_system_id;

    public $fileextension;
    public $fileextension_write_end;
    public $fileprefix;
    public $sort_files_by;
    public $delete_file;
    public $ack_prefix;

    // Form fields
    public $_path;
    public $_file_path;
    public $_files           = array();
    public $_dir_handles     = array();
    public $_limit;
    public $_acknowledgement = false;

    public $_chrono;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "source_file_system";
        $spec->key   = "source_file_system_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props                            = parent::getProps();
        $props["fileextension"]           = "str";
        $props["fileextension_write_end"] = "str";
        $props["fileprefix"]              = "str";
        $props["sort_files_by"]           = "enum list|date|name|size default|name";
        $props["delete_file"]             = "bool default|1";
        $props["ack_prefix"]              = "str";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function updateFormFields()
    {
        parent::updateFormFields();

        $this->_view = $this->host;
    }

    /**
     * Init source file system
     *
     * @param string $function_name Function name
     *
     * @return CExchangeFileSystem
     * @throws CMbException
     *
     */
    function init($function_name = null)
    {
        $exchange_fs = new CExchangeFileSystem();

        if (!$this->_id) {
            throw new CMbException("CSourceFileSystem-no-source", $this->name);
        }

        if (!is_dir($this->host)) {
            throw new CMbException("CSourceFileSystem-host-not-a-dir", $this->host);
        }

        if (!$this->loggable || !$function_name) {
            return $exchange_fs;
        }

        $exchange_fs->date_echange  = CMbDT::dateTime();
        $exchange_fs->emetteur      = CAppUI::conf("mb_id");
        $exchange_fs->destinataire  = $this->host;
        $exchange_fs->function_name = $function_name;
        $exchange_fs->input         = serialize($this->_data);
        $exchange_fs->source_class  = $this->_class;
        $exchange_fs->source_id     = $this->_id;

        $exchange_fs->store();

        CApp::$chrono->stop();
        $this->_chrono = new Chronometer();
        $this->_chrono->start();

        return $exchange_fs;
    }

    /**
     * Update exchange
     *
     * @param CExchangeFileSystem $exchange_fs Exchange
     * @param string              $output      Output
     *
     * @return mixed
     */
    function updateExchange(CExchangeFileSystem $exchange_fs, $output = null)
    {
        if (!$this->loggable) {
            return $output;
        }

        $exchange_fs->date_echange = CMbDT::dateTime();
        if ($output) {
            $exchange_fs->output = serialize($output);
        }
        $exchange_fs->store();

        $this->_chrono->stop();
        CApp::$chrono->start();

        // response time
        $exchange_fs->response_time     = $this->_chrono->total;
        $exchange_fs->response_datetime = CMbDT::dateTime();
        $exchange_fs->store();

        return $output;
    }

    /**
     * @inheritdoc
     */
    function receiveOne()
    {
        $exchange_fs = $this->init("readdir");

        $path = $this->getFullPath($this->_path);
        $path = rtrim($path, "/\\");

        if (isset($this->_dir_handles[$path])) {
            $handle = $this->_dir_handles[$path];
        } else {
            $this->startCallTrace();
            if (!is_dir($path)) {
                $this->stopCallTrace();
                $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-found", $path));
                throw new CMbException("CSourceFileSystem-path-not-found", $path);
            }

            if (!is_readable($path)) {
                $this->stopCallTrace();
                $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-readable", $path));
                throw new CMbException("CSourceFileSystem-path-not-readable", $path);
            }

            if (!$handle = opendir($path)) {
                $this->stopCallTrace();
                $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-readable", $path));
                throw new CMbException("CSourceFileSystem-path-not-readable", $path);
            }

            $this->stopCallTrace();

            $this->_dir_handles[$path] = $handle;
        }

        $filepath = null;
        while (true) {
            $this->startCallTrace();
            $file = readdir($handle);
            $this->stopCallTrace();

            if ($file === false) {
                $this->updateExchange($exchange_fs);

                return;
            }

            $this->startCallTrace();
            if (!is_dir($filepath = "$path/$file")) {
                $this->stopCallTrace();
                $this->updateExchange($exchange_fs, $filepath);

                return $filepath;
            }
            $this->stopCallTrace();
        }
    }

    /**
     * @inheritdoc
     */
    function receive()
    {
        $exchange_fs = $this->init("readdir");

        $path = $this->getFullPath($this->_path);

        $this->startCallTrace();
        if (!is_dir($path)) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-found", $path));
            throw new CMbException("CSourceFileSystem-path-not-found", $path);
        }

        if (!is_readable($path)) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-readable", $path));
            throw new CMbException("CSourceFileSystem-path-not-readable", $path);
        }

        if (!$handle = opendir($path)) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-readable", $path));
            throw new CMbException("CSourceFileSystem-path-not-readable", $path);
        }

        $this->stopCallTrace();

        /* Loop over the directory
         * $this->_files = CMbPath::getFiles($path); => pas optimisé pour un listing volumineux
         * */
        $i     = 1;
        $files = array();

        $limit = 5000;
        $this->startCallTrace();
        while (false !== ($entry = readdir($handle))) {
            $entry = "$path/$entry";
            if ($i == $limit) {
                break;
            }

            /* We ignore folders */
            if (is_dir($entry)) {
                continue;
            }

            $files[] = $entry;

            $i++;
        }

        closedir($handle);
        $this->stopCallTrace();

        switch ($this->sort_files_by) {
            default:
            case "name":
                sort($files);
                break;
            case "date":
                usort($files, array($this, "sortByDate"));
                break;
            case "size":
                usort($files, array($this, "sortBySize"));
                break;
        }

        if (isset($this->_limit)) {
            $files = array_slice($files, 0, $this->_limit);
        }

        return $this->_files = $this->updateExchange($exchange_fs, $files);
    }

    /**
     * @inheritdoc
     */
    function send($destination_basename = null)
    {
        $exchange_fs = $this->init("file_put_contents");

        $file_path = !$destination_basename ? self::generateFileName() : $destination_basename;

        // Ajout du prefix si existant
        $file_path = $this->fileprefix . $file_path;

        if ($this->_exchange_data_format && $this->_exchange_data_format->_id) {
            $file_path = "$file_path-{$this->_exchange_data_format->_id}";
        }
        $this->_file_path = $file_path;

        if ($this->fileextension && (CMbArray::get(pathinfo($file_path), "extension") != $this->fileextension)) {
            $this->_file_path .= ".$this->fileextension";
        }

        $path      = rtrim($this->getFullPath($this->_path), "\\/");
        $file_path = "$path/$this->_file_path";

        $this->startCallTrace();
        if (!is_writable($path)) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-path-not-writable", $path));
            throw new CMbException("CSourceFileSystem-path-not-writable", $path);
        }

        $this->stopCallTrace();

        if ($this->fileextension_write_end) {
            file_put_contents($file_path, $this->_data);

            $pos       = strrpos($file_path, ".");
            $file_path = substr($file_path, 0, $pos);

            $return = file_put_contents("$file_path.$this->fileextension_write_end", "");
        } else {
            $return = file_put_contents($file_path, $this->_data);
        }

        return $this->updateExchange($exchange_fs, $return);
    }

    /**
     * @inheritdoc
     */
    function getData($path)
    {
        $exchange_fs = $this->init("file_get_contents");

        $this->startCallTrace();
        if (!is_readable($path)) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-file-not-readable", $path));
            throw new CMbException("CSourceFileSystem-file-not-readable", $path);
        }

        $this->stopCallTrace();

        return $this->updateExchange($exchange_fs, file_get_contents($path));
    }

    /**
     * Generate file name
     *
     * @return string Filename
     */
    static function generateFileName()
    {
        return str_replace(array(" ", ":", "-"), array("_", "", ""), CMbDT::dateTime());
    }

    /**
     * @inheritdoc
     */
    public function getFullPath($path = "")
    {
        $host = rtrim($this->host, "/\\");
        $path = ltrim($path, "/\\");
        $path = $host . ($path ? "/$path" : "");

        return str_replace("\\", "/", $path);
    }

    /**
     * @inheritdoc
     */
    function delFile($path, $current_directory = null)
    {
        $exchange_fs = $this->init("unlink");

        if ($current_directory) {
            $path = $current_directory . $path;
        }

        $this->startCallTrace();
        if (file_exists($path) && unlink($path) === false) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-file-not-deleted", $path));
            throw new CMbException("CSourceFileSystem-file-not-deleted", $path);
        }

        $this->stopCallTrace();

        return $this->updateExchange($exchange_fs, true);
    }

    /**
     * @inheritdoc
     */
    function renameFile($oldname, $newname, $current_directory = null, $utf8_encode = false)
    {
        $exchange_fs = $this->init("rename");

        $path = $utf8_encode ? utf8_encode($current_directory . $oldname) : $current_directory . $oldname;

        $this->startCallTrace();
        if (rename($path, $current_directory . $newname) === false) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-error-renaming", $oldname));
            throw new CMbException("CSourceFileSystem-error-renaming", $oldname);
        }

        $this->stopCallTrace();

        return $this->updateExchange($exchange_fs, true);
    }

    /**
     * @inheritdoc
     */
    function changeDirectory($directory_name)
    {
    }

    /**
     * @inheritdoc
     */
    function createDirectory($directory_name)
    {
        $path = $this->getFullPath($this->_path) . "/" . $directory_name;
        $this->startCallTrace();
        if (!is_dir($path) && mkdir($path) === false) {
            $this->stopCallTrace();
            throw new CMbException("CSourceFileSystem-error-createDirectory", $directory_name);
        }

        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function getCurrentDirectory($directory = null)
    {
        if (!$directory) {
            $directory = $this->host;
            if (substr($directory, -1, 1) !== "/" && substr($directory, -1, 1) !== "\\") {
                $directory = "$directory/";
            }
        }

        return str_replace("\\", "/", $directory);
    }

    /**
     * @inheritdoc
     */
    function getListDirectory($current_directory)
    {
        $this->startCallTrace();
        $contain = scandir($current_directory);
        $this->stopCallTrace();

        $dir = array();
        foreach ($contain as $_contain) {
            $full_path = $current_directory . $_contain;
            $this->startCallTrace();
            if (is_dir($full_path) && "$_contain/" !== "./" && "$_contain/" !== "../") {
                $dir[] = "$_contain/";
            }
            $this->stopCallTrace();
        }

        return $dir;
    }

    /**
     * @inheritdoc
     */
    function getRootDirectory($current_directory)
    {
        $tabRoot = explode("/", $current_directory);
        array_pop($tabRoot);
        $tabRoot[0] = "/";
        $root       = array();
        $i          = 0;
        foreach ($tabRoot as $_tabRoot) {

            if ($i === 0) {
                $path = $_tabRoot[0];
                if (!$path) {
                    $path = "/";
                }
            } else {
                $path = $root[count($root) - 1]["path"] . "$_tabRoot/";
            }
            $root[] = array(
                "name" => $_tabRoot,
                "path" => $path
            );
            $i++;
        }

        return $root;
    }

    /**
     * @inheritdoc
     */
    function getListFiles($current_directory)
    {
        $directory = $this->getFullPath($this->_path) . "/" . $current_directory;
        if (!file_exists($directory)) {
            CAppUI::stepAjax("CSourceFileSystem-msg-Folder does not exist", UI_MSG_ERROR);
        }

        $files = array();
        $this->startCallTrace();
        foreach (scandir($directory) as $_file) {
            if ($_file == "." || $_file == "..") {
                continue;
            }

            $files[] = $_file;
        }
        $this->stopCallTrace();

        return $files;
    }

    /**
     * @inheritdoc
     */
    function getListFilesDetails($current_directory)
    {
        if (!file_exists($current_directory)) {
            CAppUI::stepAjax("CSourceFileSystem-msg-Folder does not exist", UI_MSG_ERROR);
        }

        $this->startCallTrace();
        $contain = scandir($current_directory);
        $this->stopCallTrace();
        $fileInfo = array();
        foreach ($contain as $_contain) {
            $full_path = $current_directory . $_contain;
            if (!is_dir($full_path) && @filetype($full_path) && !is_link($full_path)) {
                $fileInfo[] = array(
                    "type"         => "f",
                    "user"         => fileowner($full_path),
                    "size"         => CMbString::toDecaBinary($this->getSize($full_path, true)),
                    "date"         => strftime(CMbDT::ISO_DATETIME, filemtime($full_path)),
                    "name"         => $_contain,
                    "relativeDate" => CMbDT::daysRelative(fileatime($full_path), CMbDT::date())
                );
            }
        }

        return $fileInfo;
    }

    /**
     * @inheritdoc
     */
    function addFile($file, $file_name, $current_directory)
    {
        $exchange_fs = $this->init("copy");

        $this->startCallTrace();
        if (copy($file, $current_directory . $file_name) === false) {
            $this->stopCallTrace();
            $this->updateExchange($exchange_fs, CAppUI::tr("CSourceFileSystem-error-add", $file));
            throw new CMbException("CSourceFileSystem-error-add", $file);
        }

        $this->stopCallTrace();

        return $this->updateExchange($exchange_fs, true);
    }

    /**
     * @inheritdoc
     */
    function getSize($file_name, $full_path = false)
    {
        if (!$full_path) {
            $path      = rtrim($this->getFullPath($this->_path), "\\/");
            $file_name = "$path/$file_name";
        }

        $this->startCallTrace();
        $size = filesize($file_name);
        $this->stopCallTrace();

        return $size;
    }

    /**
     * @inheritdoc
     */
    function isReachableSource()
    {
        $this->startCallTrace();
        if (is_dir($this->host)) {
            $this->stopCallTrace();

            return true;
        } else {
            $this->stopCallTrace();
            $this->_reachable = 0;
            $this->_message   = CAppUI::tr("CSourceFileSystem-path-not-found", $this->host);

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    function isAuthentificate()
    {
        $this->startCallTrace();
        if (is_writable($this->host)) {
            $this->stopCallTrace();

            return true;
        } else {
            $this->stopCallTrace();
            $this->_reachable = 1;
            $this->_message   = CAppUI::tr("CSourceFileSystem-path-not-writable", $this->host);

            return false;
        }
    }

    /**
     * Sort by date
     *
     * @param int $a Variable a
     * @param int $b Variable b
     *
     * @return int
     */
    function sortByDate($a, $b)
    {
        return filemtime($a) - filemtime($b);
    }

    /**
     * Sort by size
     *
     * @param int $a Variable a
     * @param int $b Variable b
     *
     * @return int
     */
    function sortBySize($a, $b)
    {
        return filesize($a) - filesize($b);
    }
}
