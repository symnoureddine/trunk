<?php
/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ftp;


use Ox\Core\CFTP;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Mediboard\System\CExchangeSource;


class CSourceFTP extends CExchangeSource
{
    // Source type
    public const TYPE = 'ftp';

    // DB Table key
    public $source_ftp_id;

    // DB Fields
    public $port;
    public $default_socket_timeout;
    public $timeout;
    public $pasv;
    public $mode;
    public $fileprefix;
    public $fileextension;
    public $filenbroll;
    public $fileextension_write_end;
    public $counter;
    public $ssl;
    public $delete_file;
    public $ack_prefix;
    public $timestamp_file;

    // Form fields
    public $_source_file;
    public $_destination_file;
    public $_path;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'source_ftp';
        $spec->key   = 'source_ftp_id';

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {

        $props                            = parent::getProps();
        $props["ssl"]                     = "bool default|0";
        $props["port"]                    = "num default|21";
        $props["default_socket_timeout"]  = "num default|1";
        $props["timeout"]                 = "num default|5";
        $props["pasv"]                    = "bool default|0";
        $props["mode"]                    = "enum list|FTP_ASCII|FTP_BINARY default|FTP_BINARY";
        $props["counter"]                 = "str protected loggable|0";
        $props["fileprefix"]              = "str";
        $props["fileextension"]           = "str";
        $props["filenbroll"]              = "enum list|1|2|3|4";
        $props["fileextension_write_end"] = "str";
        $props["delete_file"]             = "bool default|1";
        $props["ack_prefix"]              = "str";
        $props["timestamp_file"]          = "bool default|0";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function init()
    {
        $ftp = new CFTP();
        $this->startCallTrace();
        $ftp->init($this);
        $this->stopCallTrace();

        return $ftp;
    }

    /**
     * Generate file name
     *
     * @return string Filename
     */
    static function generateFileName()
    {
        return "_" . str_replace(array(" ", ":", "-"), array("_", "", ""), CMbDT::dateTime());
    }

    /**
     * @inheritdoc
     */
    function send($destination_basename = null)
    {
        $ftp = $this->init($this);

        $this->counter++;

        if (!$destination_basename) {
            $destination_basename =
                sprintf(
                    "%s%0" . $this->filenbroll . "d",
                    $this->fileprefix,
                    $this->counter % pow(10, $this->filenbroll)
                );
        }

        if ($this->timestamp_file) {
            $destination_basename .= self::generateFileName();
        }

        $file_path = $destination_basename;

        $this->startCallTrace();
        if ($ftp->connect()) {
            $this->stopCallTrace();
            if ($this->fileextension && (CMbArray::get(pathinfo($destination_basename), "extension") != $this->fileextension)) {
                $destination_basename = "$file_path.$this->fileextension";
            }

            $this->startCallTrace();
            $ftp->sendContent($this->_data, $destination_basename);
            if ($this->fileextension_write_end) {
                $ftp->sendContent($this->_data, "$file_path.$this->fileextension_write_end");
            }
            $ftp->close();
            $this->stopCallTrace();

            $this->store();

            return true;
        }

        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function getACQ()
    {
    }

    /**
     * @inheritdoc
     */
    function receive()
    {
        $ftp = $this->init();

        $path  = $ftp->fileprefix ? "$ftp->fileprefix/$this->_path" : $this->_path;

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            $files = array();

            $this->startCallTrace();
            $files = $ftp->getListFiles($path);
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        if (empty($files)) {
            throw new CMbException("Le répertoire '$path' ne contient aucun fichier");
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $files;
    }

    /**
     * @inheritdoc
     */
    function getData($path)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            if ($ftp->fileprefix) {
                $path = "$ftp->fileprefix/$path";
            }

            $file = null;
            $temp = tempnam(sys_get_temp_dir(), "mb_");

            $this->startCallTrace();
            $file = $ftp->getFile($path, $temp);
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }
        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        $file_get_content = file_get_contents($file);

        unlink($temp);

        return $file_get_content;
    }

    /**
     * @inheritdoc
     */
    function getSize($file_name, $full_path = false)
    {
        $ftp = $this->init($this);

        $size = null;
        try {
            $this->startCallTrace();
            $ftp->connect();
            $size = $ftp->getSize($file_name);
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $size;
    }

    /**
     * @inheritdoc
     */
    function delFile($path, $current_directory = null)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();
            if ($current_directory) {
                $ftp->changeDirectory($current_directory);
            }

            if (!$current_directory && $ftp->fileprefix) {
                $path = "$ftp->fileprefix/$path";
            }

            $this->startCallTrace();
            $ftp->delFile($path);
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function renameFile($oldname, $newname, $current_directory = null, $utf8_encode = false)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            if ($current_directory) {
                $ftp->changeDirectory($current_directory);
            }

            if (!$current_directory && $ftp->fileprefix) {
                $oldname = "$ftp->fileprefix/$oldname";

                $newname = "$ftp->fileprefix/$newname";
            }

            $ftp->renameFile($oldname, $newname);
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function changeDirectory($directory_name)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            $ftp->changeDirectory($directory_name);
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function createDirectory($directory_name)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $ftp->createDirectory($directory_name);
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();
    }

    /**
     * @inheritdoc
     */
    function getCurrentDirectory($directory = null)
    {
        $ftp = $this->init($this);
        if (!$directory) {
            $directory = $this->fileprefix;
        }
        $curent_directory = "";
        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();
            if ($directory) {
                $ftp->changeDirectory($directory);
            }
            $curent_directory = $ftp->getCurrentDirectory();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $curent_directory;
    }

    /**
     * @inheritdoc
     */
    function getListFiles($current_directory)
    {
        $ftp = $this->init();

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            $files = array();
            $this->startCallTrace();
            foreach ($ftp->getListFiles($current_directory) as $_file) {
                if ($_file == "." || $_file == "..") {
                    continue;
                }

                $files[] = $_file;
            }
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        if (empty($files)) {
            throw new CMbException("Le répertoire '$current_directory' ne contient aucun fichier");
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $files;
    }

    /**
     * @inheritdoc
     */
    function getListFilesDetails($current_directory)
    {
        $ftp   = $this->init($this);
        $files = "";
        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            $files = $ftp->getListFilesDetails($current_directory);
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $files;
    }

    /**
     * @inheritdoc
     */
    function addFile($file, $file_name, $current_directory)
    {
        $ftp = $this->init($this);

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();
            $ftp->changeDirectory($current_directory);

            $ftp->addFile($file, $file_name);
        } catch (CMbException $e) {
            $this->stopCallTrace();
            throw $e;
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return true;
    }

    /**
     * @inheritdoc
     */
    function getListDirectory($current_directory = null)
    {
        $ftp = $this->init($this);
        if (!$current_directory) {
            $current_directory = $this->fileprefix;
        }
        $directories = "";
        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();

            $directories = $ftp->getListDirectory($current_directory);
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return $directories;
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
                $path = "/";
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
    function isReachableSource()
    {
        $ftp = new CFTP();
        $this->startCallTrace();
        $ftp->init($this);
        $this->stopCallTrace();

        try {
            $this->startCallTrace();
            $ftp->testSocket();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->_reachable = 0;
            $this->_message   = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function isAuthentificate()
    {
        $ftp = new CFTP();
        $this->startCallTrace();
        $ftp->init($this);
        $this->stopCallTrace();

        try {
            $this->startCallTrace();
            $ftp->connect();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $this->_reachable = 0;
            $this->_message   = $e->getMessage();

            return false;
        }

        $this->startCallTrace();
        $ftp->close();
        $this->stopCallTrace();

        return true;
    }

    /**
     * @inheritdoc
     */
    function getResponseTime()
    {
        $this->_response_time = url_response_time($this->host, $this->port);
    }
}
