<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Tests;

use Composer\Autoload\ClassLoader;
use Exception;
use Ox\Core\Autoload\CAutoloadAlias;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\Chronometer;
use Ox\Core\CMbConfig;
use Ox\Core\Module\CModule;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Core\SHM;
use Ox\Mediboard\Admin\CPermModule;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CConfiguration;

class TestBootstrap
{

    const USER_USERNAME = 'PHPUnit';

    /** @var $loader ClassLoader */
    private $loader;

    /**
     * CTestBootstrap constructor.
     *
     * @param ClassLoader $loader
     */
    function __construct(ClassLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Remove CPhpUnitHandler object handlers
     */
    function __destruct()
    {
    }

    /**
     * @return void
     * @deprecated
     * Add dynamique psr4 prefix
     */
    public function addPrefixPsr4()
    {
        $prefixes          = $this->loader->getPrefixesPsr4();
        $namespace_allowed = ["Mediboard", "AppFine", "Tamm", "Erp", "Interop", "Import"];

        foreach ($prefixes as $_name => $datas) {
            $_names = explode("\\", $_name);

            if ($_names[0] !== "Ox" || !in_array($_names[1], $namespace_allowed)) {
                continue;
            }

            $_prefix = $_name . 'Tests\\';
            $_dir    = str_replace("classes", "tests", $datas[0]);

            $this->loader->addPsr4($_prefix, $_dir);
        }
    }

    /**
     * Includes files and run all necessary functions to run phpunit Tests
     *
     * @return void
     * @throws TestsException
     */
    function start($is_delete_object = true)
    {
        // Autoload
        CClassMap::init();
        CAutoloadAlias::register();

        // Configs
        include_once __DIR__ . "/../../includes/config_all.php";

        // Necessary for CI job concurrency (test)
        $config_dir  = $dPconfig['root_dir'];
        $current_dir = dirname(__FILE__, 3);
        if ($config_dir !== $current_dir) {
            $dPconfig['root_dir'] = $current_dir;
        }

        // Timezone
        date_default_timezone_set($dPconfig["timezone"]);

        // Session handlers
        CSessionHandler::setHandler(CAppUI::conf('session_handler'));

        // SHM
        SHM::init();

        // Modules
        CModule::loadModules();

        // User (selenium)
        $user                = new CUser();
        $user->user_username = self::USER_USERNAME;
        $user_id             = $user->loadMatchingObject();
        if (!$user_id) {
            throw new TestsException('Missing user_username ' . self::USER_USERNAME);
        }

        // CAppUI
        CAppUI::init();
        CAppUI::initInstance();
        CAppUI::$user                = $user;
        CAppUI::$instance->_ref_user = $user->loadRefMediuser();
        CAppUI::$instance->user_id   = $user->_id;
        CAppUI::turnOffEchoStep();

        // Avoid setting group config on a bad group
        // If $g is not initialized the first group (alphabetical order) will be returned and will change later
        global $g;
        $g = $user->loadRefMediuser()->loadRefFunction()->group_id;

        // Permissions
        CPermModule::loadUserPerms();
        CPermObject::loadUserPerms();

        // Register configuration
        CConfiguration::registerAllConfiguration();

        // Force object handlers
        if ($is_delete_object) {
            TestMediboard::enableObjectHandler();
        }

        // Load config db
        if (CAppUI::conf('config_db')) {
            CMbConfig::loadValuesFromDB();
        }

        // SHM Distributed
        SHM::initDistributed();

        // chrono
        CApp::$chrono = new Chronometer();
        CApp::$chrono->start();

        // register shutdown
        if ($is_delete_object) {
            register_shutdown_function([$this, 'shutdown']);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function shutdown()
    {
        TestMediboard::removeObject();
        TestMediboard::disableObjectHandler();
    }
}
