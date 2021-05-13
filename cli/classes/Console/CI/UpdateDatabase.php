<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console\CI;

use ErrorException;
use Exception;
use Ox\Cli\DeployOperation;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CSetup;
use Ox\Core\Module\CModule;
use Ox\Core\SHM;
use Ox\Tests\TestBootstrap;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class UpdateDatabase extends DeployOperation
{

    protected $root_dir;
    protected $modules_installed   = [];
    protected $modules_upgradable  = [];
    protected $modules_installable = [];
    protected $is_upgrade_core     = false;
    public static $current_setup;


    /**
     * @see parent::configure()
     */
    protected function configure(): void
    {
        $this
            ->setName('ci:update-database')
            ->setDescription('Update runner database')
            ->addOption(
                'ci_project_dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The full path where the repository is cloned'
            );
    }


    /**
     * @see parent::showHeader()
     */
    protected function showHeader()
    {
        $this->out($this->output, '<fg=red;bg=black>Update Database</fg=red;bg=black>');
    }


    /**
     * @param string $release_code
     *
     * @return bool|mixed
     */
    protected function testBranch($release_code)
    {
        return ($this->master_branch == $release_code);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output   = $output;
        $this->input    = $input;
        $this->root_dir = dirname(__DIR__, 4);

        $this->showHeader();
        $this->bootstrap();
        $this->checkSetup();

        $count_installed   = count($this->modules_installed);
        $count_installable = count($this->modules_installable);
        $count_upgradable  = count($this->modules_upgradable);

        $this->output("Installed: {$count_installed}, Insallable: {$count_installable}, Upgradable: $count_upgradable");

        if ($this->is_upgrade_core) {
            $this->output("Upgrade core modules");
            $this->upgradeCoreModules();
        }

        if ($count_installable) {
            $this->output($count_installable . " modules installable");
            $this->installModules();
        }

        if ($count_upgradable) {
            $this->output($count_upgradable . " modules upgradable");
            $this->upgradeModules();
        }
    }

    private function bootstrap()
    {
        $loader    = require $this->root_dir . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
        $bootstrap = new TestBootstrap($loader);
        $bootstrap->start(false);

        error_reporting(E_ERROR);
        set_error_handler([static::class, 'errorHandler']);
        set_exception_handler([static::class, 'exceptionHandler']);
    }


    public static function errorHandler($code, $text, $file, $line)
    {
        $error_reporting = error_reporting();
        if ($error_reporting || strpos($text, "FATAL ERROR") === 0) {
            $e = new ErrorException($text, $code, 1, $file, $line);
            static::exceptionHandler($e);
        }
    }

    public static function exceptionHandler(Throwable $e)
    {
        echo "\033[33m " . get_class($e) . " - " . static::$current_setup . " - " . $e->getMessage() . "\033[0m" . PHP_EOL;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function checkSetup()
    {
        $all_setup = CApp::getChildClasses(CSetup::class);
        $this->output("Check " . count($all_setup) . " " . CSetup::class);

        foreach ($all_setup as $setup) {
            static::$current_setup = $setup;
            $setup = new $setup();

            $module = new CModule();
            $module->compareToSetup($setup);
            $module->checkModuleFiles();
            $module->getUpdateMessages($setup, true);
            $module->updateFormFields();

            if ($module->mod_type === "core" && $module->_upgradable) {
                $this->is_upgrade_core = true;
                continue;
            }

            if ($module->mod_ui_order === 1000) {
                $this->modules_installable[$module->mod_name] = $module;
            } else {
                $this->modules_installed[$module->mod_name] = $module;
                if ($module->_upgradable) {
                    $this->modules_upgradable[$module->mod_name] = $module;
                }
            }
        }
    }


    private function upgradeCoreModules()
    {
        $module           = new CModule();
        $module->mod_type = "core";

        /** @var CModule[] $list_modules */
        $list_modules = $module->loadMatchingList();

        foreach ($list_modules as $module) {
            $this->upgradeModule($module);
        }

        CAppUI::buildPrefs();
        SHM::rem("modules");
    }


    private function installModules()
    {
        foreach ($this->modules_installable as $module_name => $module) {
            $module->mod_version = "0.0";
            $module->mod_name    = $module_name;
            $module->mod_active  = 1;

            $this->upgradeModule($module, true);
        }

        CAppUI::buildPrefs();
        SHM::rem("modules");
    }


    private function upgradeModules()
    {
        foreach ($this->modules_upgradable as $module_name => $module) {
            $this->upgradeModule($module);
        }

        CAppUI::buildPrefs();
        SHM::rem("modules");
    }

    private function upgradeModule(CModule $module, $reorder = false)
    {
        $setupClass          = CSetup::getCSetupClass($module->mod_name);

        /** @var CSetup $setup */
        $setup = new $setupClass;
        if ($result = $setup->upgrade($module)) {
            if ($reorder) {
                $module->reorder();
            }
            if ($setup->mod_version == $module->mod_version) {
                $this->output("Success upgraded {$module->mod_name} version {$setup->mod_version}");
            } else {
                throw new Exception(
                    "Failed upgraded {$module->mod_name} version {$module->mod_version} to {$setup->mod_version}"
                );
            }
        } else {
            $this->output("Module {$module->mod_name} uptodate");
        }
    }


    private function output($msg)
    {
        $this->out($this->output, $msg);
    }
}
