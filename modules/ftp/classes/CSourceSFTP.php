<?php
/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ftp;

use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CSFTP;
use Ox\Mediboard\System\CExchangeSource;

/**
 * Source SFTP
 */
class CSourceSFTP extends CExchangeSource
{
    // Source type
    public const TYPE = 'sftp';

    /** @var integer Primary key */
    public $source_sftp_id;
    public $port;
    public $timeout;
    public $fileprefix;
    public $fileextension_write_end;
    public $fileextension;
    public $delete_file;

    /** @var CSFTP */
    public $_sftp;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "source_sftp";
        $spec->key   = "source_sftp_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props = parent::getProps();

        $props["port"]                    = "num default|22";
        $props["timeout"]                 = "num default|10";
        $props["fileprefix"]              = "str";
        $props["fileextension_write_end"] = "str";
        $props["fileextension"]           = "str";
        $props["delete_file"]             = "bool default|1";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function send($destination_basename = null)
    {
        $this->init($this);

        if (!$destination_basename) {
            $destination_basename = sprintf("%s%04d", $this->fileprefix, rand(0, 1000 * 1000) % pow(10, 4));
        }

        $file_path = $destination_basename;

        try {
            $this->startCallTrace();
            $this->_sftp->connect();

            if (
                $this->fileextension
                && (CMbArray::get(pathinfo($destination_basename), "extension") != $this->fileextension)
            ) {
                $destination_basename = "$file_path.$this->fileextension";
            }

            $this->sendContent($destination_basename, $this->_data);
            if ($this->fileextension_write_end) {
                $this->sendContent("$file_path.$this->fileextension_write_end", $this->_data);
            }

            $this->_sftp->close();
            $this->stopCallTrace();

            return true;
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            throw $e;
        }
    }

    /**
     * Init
     *
     * @return CSFTP
     */
    function init()
    {
        if ($this->_sftp) {
            return $this->_sftp;
        }

        $sftp = new CSFTP();
        $sftp->init($this);
        $this->_sftp = $sftp;

        return $sftp;
    }

    /**
     * @inheritdoc
     */
    function sendContent($remote_file, $content)
    {
        try {
            $this->_sftp->addFile($remote_file, $content, true);
        } catch (CMbException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function getACQ()
    {
    }

    /** @inheritdoc */
    function getError()
    {
        if (!$this->_sftp) {
            return null;
        }

        return CMbArray::get($this->_sftp->connexion->sftp_errors, 0);
    }

    /**
     * @inheritdoc
     */
    function receive()
    {
        $this->init();

        $this->startCallTrace();
        $this->_sftp->connect();
        $path  = $this->_sftp->getCurrentDirectory();
        $path  = $this->fileprefix ? "$path/$this->fileprefix" : $path;
        $files = $this->_sftp->getListFiles($path);
        $this->_sftp->close();
        $this->stopCallTrace();

        if (empty($files)) {
            throw new CMbException("Le répertoire ne contient aucun fichier");
        }

        return $files;
    }

    /**
     * @inheritdoc
     */
    function getListFiles($current_directory)
    {
        $this->init();

        try {
            $this->startCallTrace();
            $this->_sftp->connect();

            $files = [];
            foreach ($this->_sftp->getListFiles($current_directory) as $_file) {
                if ($_file == "." || $_file == "..") {
                    continue;
                }

                $files[] = $_file;
            }
            $this->_sftp->close();

            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            $e->stepAjax();
        }


        if (empty($files)) {
            throw new CMbException("Le répertoire '$current_directory' ne contient aucun fichier");
        }

        return $files;
    }

    /**
     * @inheritdoc
     */
    function addFile($remote_file, $data_file, $current_directory)
    {
        $this->init();

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->_sftp->changeDirectory($current_directory);

            $this->_sftp->addFile($remote_file, $data_file, false);
            $this->_sftp->close();

            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            throw $e;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function getFile($remote_file, $data_file, $current_directory = null)
    {
        $this->init();

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->_sftp->changeDirectory($current_directory);

            $this->_sftp->getFile($remote_file, $data_file, false);
            $this->_sftp->close();
            $this->stopcallTrace();
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            throw $e;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function changeDirectory($directory)
    {
        $this->init();

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->_sftp->changeDirectory($directory);
            $this->_sftp->close();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            throw $e;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function getCurrentDirectory($directory = null)
    {
        $this->init();
        if (!$directory) {
            $directory = $this->fileprefix;
        }

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            if ($directory) {
                $this->_sftp->changeDirectory($directory);
            }
            $curent_directory = $this->_sftp->getCurrentDirectory();
            $this->_sftp->close();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->_sftp->close();
            $this->stopCallTrace();
            $e->stepAjax();
        }

        return "$curent_directory/";
    }

    /**
     * @inheritdoc
     */
    function delFile($path, $current_directory = null, $use_fileprefix = false)
    {
        $this->init();
        $this->startCallTrace();
        $this->_sftp->connect();

        if ($current_directory) {
            $this->_sftp->changeDirectory($current_directory);
        }

        if (!$current_directory && $this->fileprefix && $use_fileprefix) {
            $path = "$this->fileprefix/$path";
        }

        $delete = $this->_sftp->delFile($path);
        $this->_sftp->close();
        $this->stopCallTrace();

        return $delete;
    }

    /**
     * @inheritdoc
     */
    function renameFile($oldname, $newname, $current_directory = null, $utf8_encode = false)
    {
        $this->init();
        $this->startCallTrace();
        $this->_sftp->connect();
        $this->stopCallTrace();

        if ($current_directory) {
            $this->startCallTrace();
            $this->_sftp->changeDirectory($current_directory);
            $this->stopcallTrace();
        }

        if (!$current_directory && $this->fileprefix) {
            $oldname = "$this->fileprefix/$oldname";
            $newname = "$this->fileprefix/$newname";
        }

        $this->startCallTrace();
        $rename = $this->_sftp->renameFile($oldname, $newname);
        $this->_sftp->close();
        $this->stopCallTrace();

        return $rename;
    }

    /**
     * @inheritdoc
     */
    function getData($path, $use_fileprefix = false)
    {
        $this->init();

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->stopCallTrace();

            if ($this->fileprefix && $use_fileprefix) {
                $path = rtrim($this->fileprefix, "\\/") . "/$path";
            }

            $file = null;
            $temp = tempnam(sys_get_temp_dir(), "mb_");

            $this->startCallTrace();
            $this->_sftp->getFile($path, $temp);
            $this->_sftp->close();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }

        $file_get_content = file_get_contents($temp);
        unlink($temp);

        return $file_get_content;
    }

    /**
     * @inheritdoc
     */
    function createDirectory($directory_name)
    {
        $this->init($this);

        try {
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->_sftp->createDirectory($directory_name);
            $this->_sftp->close();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }
    }

    /**
     * @inheritdoc
     */
    function getListDirectory($directory = ".")
    {
        $this->init();
        $this->startCallTrace();
        $this->_sftp->connect();
        $list = $this->_sftp->getListDirectory($directory);
        $this->_sftp->close();
        $this->stopCallTrace();

        return $list;
    }

    /**
     * @inheritdoc
     */
    function getListFilesDetails($current_directory)
    {
        $this->init();
        $this->startCallTrace();
        $this->_sftp->connect();
        $list = $this->_sftp->getListFilesDetails($current_directory);
        $this->_sftp->close();
        $this->stopCallTrace();

        return $list;
    }

    /**
     * @inheritdoc
     */
    function getRootDirectory($current_directory)
    {
        $tabRoot = explode("/", $current_directory);
        array_pop($tabRoot);
        $tabRoot[0] = "/";
        $root       = [];
        $i          = 0;
        foreach ($tabRoot as $_tabRoot) {
            if ($i === 0) {
                $path = "/";
            } else {
                $path = $root[count($root) - 1]["path"] . "$_tabRoot/";
            }
            $root[] = [
                "name" => $_tabRoot,
                "path" => $path,
            ];
            $i++;
        }

        return $root;
    }

    /**
     * @inheritdoc
     */
    function isReachableSource()
    {
        try {
            $this->init();
            $this->startCallTrace();
            $this->_sftp->testSocket();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $this->_reachable = 0;
            $this->_message   = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function getSize($file_name, $full_path = false)
    {
        try {
            $this->init($this);
            $this->startCallTrace();
            $this->_sftp->connect();
            $size = $this->_sftp->getSize($file_name);
            $this->_sftp->close();
            $this->stopCallTrace();

            return $size;
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $e->stepAjax();
        }
    }

    /**
     * @inheritdoc
     */
    function isAuthentificate()
    {
        try {
            $this->init();
            $this->startCallTrace();
            $this->_sftp->connect();
            $this->_sftp->close();
            $this->stopCallTrace();
        } catch (CMbException $e) {
            $this->stopCallTrace();
            $this->_reachable = 0;
            $this->_message   = $e->getMessage();

            return false;
        }

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
