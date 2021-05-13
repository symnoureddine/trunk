<?php
/**
 * Created by PhpStorm.
 * User: Mathias
 * Date: 08/04/2019
 * Time: 12:09
 */

namespace Ox\Tests;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbConfig;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Core\Handlers\Facades\HandlerManager;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Bcb\CBcbProduitLivretTherapeutique;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Medicament\CMedicament;
use Ox\Mediboard\Patients\CSourceIdentite;
use Ox\Mediboard\System\CConfiguration;
use Ox\Mediboard\System\CConfigurationModelManager;
use Ox\Mediboard\System\CTableStatus;
use Ox\Mediboard\System\Forms\CExObject;
use PDO;
use PDOException;
use ReflectionClass;

/**
 * Trait TestMediboard
 * horizontal composition of behavior (db, error, config, pref ...)
 */
trait TestMediboard
{
    public $base_url;
    public $dsn;
    public $db_type;
    public $db_host;
    public $db_name;
    public $db_username;
    public $db_password;

    public $errorCount;

    private $newConfigs = ['standard' => [], 'groups' => []];
    private $oldConfigs = ['standard' => [], 'groups' => []];

    private $newPrefs = ['standard' => []];
    private $oldPrefs = [];


    /**
     * Set attribut from conifg (db_name, db_user, db_pass, base_url ...)
     * Global case:  http://XXX.XX.XX.XX/mediboard/
     * Release case: http://XXX.XX.XX.XX/mediboard_MonthName/
     *
     *
     * @param string $instance_name Instance name
     *
     * @return void
     */
    public function setContext($instance_name = null)
    {
        // db
        $this->db_type     = CAppUI::conf("db std dbtype");
        $this->db_host     = CAppUI::conf("db std dbhost");
        $this->dsn         = "{$this->db_type}:host={$this->db_host}";
        $this->db_username = CAppUI::conf("db std dbuser");
        $this->db_password = CAppUI::conf("db std dbpass");
        $this->db_name     = CAppUI::conf("db std dbname");

        // Global case
        $conf_base_url  = CAppUI::conf("base_url");
        $this->base_url = $conf_base_url;

        // Release case
        $release_file = __DIR__ . "/../../release.xml";
        //    if (file_exists($release_file)) {
        //      // If the file exists, we read it
        //      $file_content = file_get_contents($release_file);
        //      preg_match("/[0-9]{4}_[0-9]{2}/", $file_content, $matches);
        //      $month_number = explode("_", $matches[0])[1];
        //
        //      // Check if month number is odd or even and set url and db name
        //      $branch         = ($month_number & 1) ? "odd" : "even";
        //      $this->base_url = dirname($conf_base_url) . "/mediboard_$branch";
        //      $this->db_name  = "mediboard_$branch";
        //    }
        //    // Special case when a test runs on a given instance
        //    else {
        //      if ($instance_name) {
        //        $this->base_url = dirname($conf_base_url) . "/$instance_name";
        //        $this->db_name  = $instance_name;
        //      }
        //    }
    }

    /**
     * @return array
     */
    public static function enableObjectHandler()
    {
        HandlerManager::resetHandlers();

        return static::setGroupsConfig('system object_handlers CPhpUnitHandler', '1');
    }

    /**
     * @return array
     */
    public static function disableObjectHandler()
    {
        HandlerManager::resetHandlers();

        return static::setGroupsConfig('system object_handlers CPhpUnitHandler', '0');
    }


    /**
     * Set a standard configuration (config.php or config_db)
     *
     * @param string $path  Configuration path
     * @param string $value Configuration value
     *
     * @return array
     */
    private static function setStandardConfig($path, $value)
    {
        if (CAppUI::conf("config_db")) {
            $ds        = CSQLDataSource::get("std");
            $query     = "SELECT `value` FROM `config_db`
                WHERE `key` = '$path';";
            $old_value = $ds->loadResult($query);

            if ($old_value != $value) {
                $query = "INSERT INTO `config_db`
                    VALUES (%1, %2)
                    ON DUPLICATE KEY UPDATE value = %2;";
                $query = $ds->prepare($query, $path, $value);
                $ds->exec($query);
            }
        } else {
            $config    = new CMbConfig();
            $old_value = $config->get($path);
            $config->set($path, $value);
            $config->update($config->values);
        }

        return [$path => $old_value];
    }

    /**
     * Try to connect to the specify DB with login/password
     *
     * @param string $db_name Optional db_name
     *
     * @return PDO PDO object
     */
    public function dbConnect($db_name = null)
    {
        try {
            return new PDO(
                $this->dsn . ";" . ($db_name ? "dbname=$db_name" : ""),
                $this->db_username,
                $this->db_password
            );
        } catch (PDOException $e) {
            die("DB ERROR: " . $e->getMessage());
        }
    }

    /**
     * @param string $class The class name
     * @param int    $nb    Nb of objects
     *
     * @return mixed|CStoredObject|CStoredObject[]
     * @throws TestsException
     */
    public function getRandomObjects($class, $nb = 1)
    {
        $nb = ($nb > 100) ? 100 : $nb;

        /** @var CStoredObject $object */
        $object  = new $class;
        $objects = $object->loadList(
            null,
            null,
            $this->getLimit($object->countList(), $nb),
            null,
            null,
            null,
            null,
            false
        );

        if (empty($objects) || count($objects) < $nb) {
            $genrators = CObjectGenerator::getGenerators();
            if (!array_key_exists(get_class($object), $genrators)) {
                throw new TestsException("No generator for {$class}, contribute to populate !");
            }

            $generator = $genrators[get_class($object)];
            try {
                $nb = $nb - count($objects);

                /** @var CObjectGenerator $gen */
                $gen = new $generator();

                // Keep generated objects in DB
                $this->disableObjectHandler();

                for ($i = 0; $i < $nb; $i++) {
                    $objects[] = $gen->setForce(true)->generate();
                }

                $this->enableObjectHandler();
            } catch (Exception $e) {
                throw new TestsException("Error while generating an object " . $e->getMessage());
            }
        }

        if ($nb === 1) {
            return reset($objects);
        }

        return $objects;
    }

    /**
     * Count error number by querying the db
     *
     * @return int error count
     */
    public function getErrorCount()
    {
        $excludedErrorType = ["notice"];
        $db                = $this->dbConnect($this->db_name);
        $sql               = "SELECT COUNT(`error_log_id`) as errorCount FROM `error_log`
            WHERE `error_type` NOT IN ('" . implode("', '", $excludedErrorType) . "');";
        $statement         = $db->prepare($sql);
        $statement->execute();
        $row       = $statement->fetch();
        $statement = null;
        $db        = null;

        return $row['errorCount'];
    }

    /**
     * Set MB configuration according to test comment
     *
     * @param array $configs Array containing config path, value and type (standard or groups)
     *
     * @return void
     */
    private function setConfig($configs)
    {
        if (!$configs) {
            return;
        }

        foreach ($configs as $_type => $_configs) {
            foreach ($_configs as $_path => $_value) {
                if ($_type == 'standard') {
                    $this->oldConfigs['standard'] += static::setStandardConfig($_path, $_value);
                } else {
                    // Ignore PhpUnitHandler
                    if ($_path === 'system object_handlers CPhpUnitHandler') {
                        continue;
                    }

                    $this->oldConfigs['groups'] += static::setGroupsConfig($_path, $_value);
                }
            }
        }

        // Reload config in DB
        if (CAppUI::conf("config_db")) {
            CMbConfig::loadValuesFromDB();
        }
    }


    /**
     * Get the current test function comments
     *
     * @return null|string
     */
    private function getFunctionComments()
    {
        global $mbpath;
        $mbpath = __DIR__ . "/../../";

        // HTTP_HOST is undefined when running with PHP CLI
        $_SERVER["HTTP_HOST"] = "";

        $reflectionClass = new ReflectionClass(get_class($this));

        $method_name = $this->getName();
        if (!$reflectionClass->hasMethod($method_name)) {
            // @dataProvider case
            $method_name = explode(' ', $this->getName())[0];
            if (!$reflectionClass->hasMethod($method_name)) {
                return;
            }
        }

        $method = $reflectionClass->getMethod($method_name);

        return $method->getDocComment();
    }

    /**
     * Parse function comment in order to retrieve config information
     *
     * @param string $type  Type of comment to parse (config or pref)
     * @param array  $array Array to append parsed values
     *
     * @return array
     */
    private function parseComment($type, &$array)
    {
        $comments = $this->getFunctionComments();

        if (preg_match_all("/.*{$type}\s(?<{$type}>.*)/", $comments, $matches)) {
            foreach ($matches[$type] as $_match) {
                $pos   = strrpos($_match, ' ');
                $path  = trim(substr($_match, 0, $pos));
                $value = trim(substr($_match, $pos + 1));

                // Needed fo groups config
                if ($pos = strpos($path, '[CConfiguration]') !== false) {
                    $path            = str_replace('[CConfiguration] ', '', $path);
                    $array['groups'] += [$path => $value];
                } else {
                    $array['standard'] += [$path => $value];
                }
            }
        }

        return $array;
    }

    /**
     * Sets a groups configuration (CConfiguration class)
     * The value is set for global and all groups
     *
     * @param string $path  Configuration path
     * @param string $value Configuration value
     *
     * @return array
     */
    private static function setGroupsConfig($path, $value)
    {
        $value_field = (CAppUI::conf('instance_role') == 'prod') ? 'value' : 'alt_value';

        $config          = new CConfiguration();
        $config_spec     = $config->getSpec();
        $config->feature = $path;
        $config->setObject(CGroups::loadCurrent());

        $old_conf = [];

        $configs = $config->loadMatchingList();

        // Ref module not installed, case shouldn't happen
        if ($configs === null) {
            return [];
        }

        // Config is absent from database
        if (empty($configs)) {
            $old_conf += [$path => null];

            $config->{$value_field} = $value;
            $config->rawStore();
        }

        foreach ($configs as $_config) {
            $old_conf += [$path => $_config->{$value_field}];

            // Store only if different values
            if ($_config->{$value_field} != $value) {
                $_config->feature        = $path;
                $_config->{$value_field} = $value;
                $_config->rawStore();
            }

            // Remove configuration if value is null
            if ($value == null) {
                $ds = $config->getDS();
                $ds->deleteObject($config_spec->table, $config_spec->key, $_config->_id);
            }
        }

        // Clear cache
        $module = explode(' ', $path)[0];
        CTableStatus::change($config_spec->table);
        CConfiguration::updateTableStatus($module, CMbDT::dateTime());
        CConfigurationModelManager::clearCache($module);
        Cache::flushInner();
        //$instance_path = realpath(__DIR__ . "/../../");
        //exec("php $instance_path/cli/console.php cache:clear $instance_path");

        return $old_conf;
    }


    /**
     * Get the last object identifier created
     *
     * @param string $table Table name where object is stored
     * @param string $key   Optional primary key of the table
     *
     * @return null|string
     */
    public function getObjectId($table, $key = null)
    {
        $ds = CSQLDataSource::get("std");

        if (!$key) {
            $key = $table . "_id";
        }

        $r = new CRequest();
        $r->addSelect($key);
        $r->addTable($table);
        $r->addOrder("$key DESC");

        return $ds->loadResult($r->makeSelect());
    }

    /**
     * @return string getTmpFileStoredObject
     */
    public static function getTmpFileStoredObject()
    {
        return dirname(__DIR__, 2) . '/tmp/phpunit_stored_objects.tmp';
    }

    /**
     * @param $object
     *
     * @return void
     */
    public static function addStoredObject($object)
    {
        $file_tmp = static::getTmpFileStoredObject();

        $objects = file_exists($file_tmp) ? file($file_tmp, FILE_IGNORE_NEW_LINES) : [];
        $_guid   = $object->_guid;

        if (in_array($_guid, $objects, true)) {
            return;
        }

        $objects[] = $_guid;
        if ($object instanceof CUser) {
            $objects[] = "CMediusers-$object->_id";
        }

        file_put_contents($file_tmp, implode(PHP_EOL, $objects));
    }

    /**
     * Static function because tearDownAfterClass is static
     *
     * @return string|void
     * @throws Exception
     */
    public static function removeObject()
    {
        $file_tmp = static::getTmpFileStoredObject();
        $objects  = file_exists($file_tmp) ? file($file_tmp, FILE_IGNORE_NEW_LINES) : [];

        if (!$objects) {
            return '{}';
        }

        echo __METHOD__ . ' ' . count($objects) . PHP_EOL;

        CApp::disableCacheAndHandlers();

        while ($_guid = array_pop($objects)) {
            [$class, $id] = explode('-', $_guid);

            if (strpos($class, 'CExObject') === 0) {
                [$ex_name, $ex_id] = explode('_', $class);
                /** @var CExObject $object */
                $object = new $ex_name($ex_id);
            } else {
                /** @var CStoredObject $object */
                $object = new $class();
            }

            $ds = $object->getDS();
            if ($ds->hasTable($object->_spec->table)) {
                // todo remove nullify backprops
                //                foreach ($object->_backSpecs as $backSpec) {
                //
                //                    $backObject = new $backSpec->class;
                //                    $spec  = $backObject->getSpec();
                //                    $backField  = $backSpec->field;
                //
                //                    $query = "UPDATE `$spec->table` SET `$backField` = NULL WHERE `$backField` = '$object->_id'";
                //                    var_dump($query);
                //                    $ds->exec($query);
                //                }

                $ds->deleteObject($object->_spec->table, $object->_spec->key, $id);
            }

            if ($class === "CProduitLivretTherapeutique" && CMedicament::getBase() === "bcb") {
                $ds = CBcbProduitLivretTherapeutique::getDataSource();
                $ds->exec("DELETE FROM `LIVRETTHERAPEUTIQUE`;");
            }
        }

        unlink($file_tmp);
    }

    /**
     * @param int $table_count
     * @param int $row_count
     *
     * @return string
     */
    private function getLimit(int $table_count, int $row_count)
    {
        $max_rand = ($table_count - $row_count);
        $start    = ($max_rand < 0) ? 0 : rand(0, $max_rand);

        return "{$start},{$row_count}";
    }

}
