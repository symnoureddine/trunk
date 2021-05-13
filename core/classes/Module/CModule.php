<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Module;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CClassMap;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CSetup;
use Ox\Core\CStoredObject;
use Ox\Core\Module\Requirements\CRequirementsException;
use Ox\Core\Module\Requirements\CRequirementsManager;
use Ox\Mediboard\Admin\CPermModule;
use Ox\Mediboard\System\Controllers\CSystemLegacyController;
use Ox\Mediboard\System\Controllers\Legacy\CMainController;

if (!defined("TAB_READ")) {
    /**
     * Read permissions on the view
     */
    define("TAB_READ", 0);

    /**
     * Edit permissions on the view
     */
    define("TAB_EDIT", 1);

    /**
     * Admin permissions on the view
     */
    define("TAB_ADMIN", 2);

    /**
     * No permissions on the view
     * @deprecated
     */
    define("TAB_NONE", 3);
}

if (!defined("PERM_DENY")) {
    /**
     * No permission on the object
     */
    define("PERM_DENY", 0);

    /**
     * Read permission on the object
     */
    define("PERM_READ", 1);

    /**
     * Edit permission on the object
     */
    define("PERM_EDIT", 2);
}

/**
 * Module class
 */
class CModule extends CStoredObject
{
    public const TTL = 120; // Cache TTL in second

    public const RESOURCE_NAME = 'module';

    /** @var string */
    public const TAB_STANDARD = "standard";

    /** @var string */
    public const TAB_SETTINGS = "settings";

    /** @var string */
    public const TAB_CONFIGURE = "configure";

    /** @var array */
    public const TABS = [
        self::TAB_SETTINGS,
        self::TAB_STANDARD,
        self::TAB_CONFIGURE,
    ];

    /** @var array */
    public static $installed = [];

    /** @var array */
    public static $active = [];

    /** @var array */
    public static $visible = [];

    /** @var array */
    public static $absent = [];

    /** @var string[] */
    public static $category_color = [
        "autre"             => "757575",
        "administratif"     => "8e24aa",
        "systeme"           => "f44336",
        "circuit_patient"   => "2196f3",
        "dossier_patient"   => "7986cb",
        "erp"               => "90a4ae",
        "import"            => "00796b",
        "reporting"         => "303f9f",
        "interoperabilite"  => "009688",
        "parametrage"       => "fbc02d",
        "plateau_technique" => "4caf50",
        "referentiel"       => "f57c00",
        "obsolete"          => "546e7a",
    ];
    /** @var int Primary key */
    public $mod_id;

    // DB Fields
    /** @var string */
    public $mod_name;

    /** @var string */
    public $mod_type; // Core or User

    /** @var string */
    public $mod_version; // Current Installed version MM.mmm

    /** @var bool */
    public $mod_active; // active module

    /** @var bool */
    public $mod_ui_active; // visible module

    /** @var int */
    public $mod_ui_order; // UI Position

    /** @var string */
    public $mod_category; // Category name

    /** @var string */
    public $mod_package; // Package name

    /** @var string */
    public $mod_custom_color; // Custom color code

    // Form Fields
    /** @var string */
    public $_latest;

    /** @var bool */
    public $_too_new;

    /** @var bool */
    public $_upgradable;

    /** @var bool */
    public $_need_php_update = false;

    /** @var bool */
    public $_configable;

    /** @var bool */
    public $_files_missing;

    /** @var array */
    public $_dependencies;

    /** @var array */
    public $_dependencies_not_verified;

    /** @var string */
    public $_mod_requires_php;

    /** @var array */
    public $_update_messages;

    /** @var string */
    public $_color;

    /** @var int */
    public $_requirements = 0;

    /** @var int */
    public $_requirements_failed = 0;

    /** @var array */
    public $_dsns = [];

    /** @var string */
    public $_namespace;

    /** @var array */
    public $_tabs = []; // List of tabs (grouped)

    /** @var string */
    public static $modules_hash; // Hash list modules from classmap

    /**
     * @var bool
     * @deprecated
     */
    public $_canView;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Hack to simulate the activeness of the class which has no real module
        $this->_ref_module = $this;
    }

    /**
     * Get all classes for a given module
     *
     * @param string $module Module name
     *
     * @return array[string] Class names
     **/
    public static function getClassesFor(string $module): array
    {
        // Liste des Class
        $listClass = CApp::getInstalledClasses();

        $tabClass = [];
        foreach ($listClass as $class) {
            $object = new $class();

            if (!is_object($object->_ref_module)) {
                continue;
            }

            if ($object->_ref_module->mod_name == $module) {
                $tabClass[] = $object->_class;
            }
        }

        return $tabClass;
    }

    /**
     * Specs
     *
     * @return CMbObjectSpec
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec                  = parent::getSpec();
        $spec->table           = 'modules';
        $spec->key             = 'mod_id';
        $spec->uniques["name"] = ["mod_name"];

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    public function getProps(): array
    {
        $props                  = parent::getProps();
        $props["mod_name"]      = "str notNull maxLength|20 fieldset|default";
        $props["mod_type"]      = "enum notNull list|core|user fieldset|default";
        $props["mod_version"]   = "str notNull maxLength|10 fieldset|default";
        $props["mod_active"]    = "bool fieldset|default";
        $props["mod_ui_active"] = "bool fieldset|default";
        $props["mod_ui_order"]  = "num fieldset|default";

        $props["mod_category"] = "enum notNull list|autre|referentiel|plateau_technique|parametrage|obsolete"
            . "|interoperabilite|reporting|import|erp|dossier_patient|circuit_patient|systeme|administratif"
            . " default|autre fieldset|default";

        $props["mod_package"] = "enum notNull list|autre|metier|administration|ox|echange|referentiel default|autre"
            . " fieldset|default";

        $props["mod_custom_color"] = "color fieldset|default";
        $props["_latest"]          = "str notNull maxLength|10";
        $props["_too_new"]         = "bool";
        $props["_upgradable"]      = "bool";
        $props["_configable"]      = "bool";
        $props["_dependencies"]    = "str";
        $props["_color"]           = "str";

        $props["_dsns"] = "";

        return $props;
    }

    /**
     * Load and compare a module to a given setup
     *
     * @param CSetup $setup The CSetup object to compare to
     *
     * @return void
     */
    public function compareToSetup(CSetup $setup): void
    {
        $this->mod_name = $setup->mod_name;
        $this->loadMatchingObject();

        $this->mod_type          = $setup->mod_type;
        $this->_latest           = $setup->mod_version;
        $this->_mod_requires_php = $setup->mod_requires_php;
        $this->_upgradable       = $this->mod_version < $this->_latest;
        $this->_too_new          = $this->mod_version > $this->_latest;
        $this->_configable       = is_file("modules/$this->mod_name/configure.php")
            || $this->isMatchLegacyController('configure');

        if ($this->_id) {
            $this->_dsns = $setup->getDatasources();
        }

        $this->_dependencies = $setup->dependencies;

        if (version_compare(PHP_VERSION, $setup->mod_requires_php, '<=')) {
            $this->_need_php_update = true;
            $this->_upgradable      = false;
        }

        if (!$this->_id) {
            $this->mod_ui_order = 1000;
        }
    }

    /**
     * Vérification de l'existence du module
     *
     * @return bool
     */
    public function checkModuleFiles(): bool
    {
        $this->_files_missing = !self::exists($this->mod_name);

        return !$this->_files_missing;
    }

    /**
     * @see parent::updateFormFields()
     */
    public function updateFormFields(): void
    {
        parent::updateFormFields();
        $this->loadColor();
        $this->_view = CAppUI::tr("module-$this->mod_name-court");
    }

    /**
     * @inheritdoc
     */
    public function store(): ?string
    {
        self::removeModulesCache();

        return parent::store();
    }

    /**
     * Load a module by name
     */
    public function loadByName(string $name): ?int
    {
        $this->mod_name = $name;

        return $this->loadMatchingObject();
    }

    /**
     * @inheritDoc
     */
    public function getPerm($permType): bool
    {
        return CPermModule::getPermModule($this->mod_id, $permType);
    }

    /**
     * Récupération de droit de vue d'un module
     *
     * @param int $permType type de permission
     *
     * @return bool
     */
    public function getView(int $permType): bool
    {
        return CPermModule::getViewModule($this->mod_id, $permType);
    }

    /**
     * Get the update message following mod_version
     *
     * @param CSetup $setup          setup object to check
     * @param bool   $onlyNextUpdate only the next update message ?
     *
     * @return array messages list [version => message]
     */
    public function getUpdateMessages(CSetup $setup, bool $onlyNextUpdate = false): array
    {
        $this->_update_messages = $setup->messages;
        if ($onlyNextUpdate) {
            foreach ($this->_update_messages as $version => $message) {
                if ($version < $this->mod_version) {
                    unset($this->_update_messages[$version]);
                }
            }
        }

        return $this->_update_messages;
    }

    /**
     * Checks the View permission on the module
     *
     * @return bool
     */
    public function canView(): bool
    {
        return $this->_canView = $this->getView(PERM_READ);
    }

    /**
     * Checks the Admin permission on the module
     *
     * @return bool
     */
    public function canAdmin(): bool
    {
        return $this->_canEdit = $this->getView(PERM_EDIT);
    }

    /**
     * @see parent::canDo()
     */
    public function canDo(): CCanDo
    {
        if ($this->_can) {
            return $this->_can;
        }

        parent::canDo();

        $this->_can->view  = $this->canView();
        $this->_can->admin = $this->canAdmin();

        // Module view can be shown information
        $this->_can->context = "module $this->_view";

        return $this->_can;
    }

    /**
     * Load the list of visible modules
     *
     * @return void
     */
    public static function loadModules(): void
    {
        $modules = self::loadModulesFromCache();

        foreach ($modules as &$module) {
            $module->checkModuleFiles();
            self::$installed[$module->mod_name] =& $module;

            if ($module->mod_active == 1) {
                self::$active[$module->mod_name] =& $module;
            }

            if ($module->mod_ui_active == 1) {
                self::$visible[$module->mod_name] =& $module;
            }

            if ($module->_files_missing) {
                self::$absent[$module->mod_name] =& $module;
            }
        }
    }

    /**
     * Loads modules from cache if available
     *
     * @return self[]
     * @throws Exception
     */
    private static function loadModulesFromCache(): array
    {
        $cache = new Cache(__CLASS__, '::all', Cache::INNER_OUTER, self::TTL);

        if ($cache->exists()) {
            return $cache->get();
        }

        $module  = new self();
        $modules = $module->loadList(null, "mod_ui_order");

        return $cache->put($modules);
    }


    public function registerTabs(): void
    {
        if (!empty($this->_tabs)) {
            return; // One shot
        }

        $cache = new Cache(__METHOD__, $this->mod_name, Cache::INNER_OUTER);
        if ($cache->exists()) {
            $registers = $cache->get();
        } else {
            $registers = CClassMap::getInstance()->getClassChildren(
                AbstractTabsRegister::class,
                false,
                true,
                $this->mod_name
            );
            $cache->put($registers);
        }

        /** @var AbstractTabsRegister $_register */
        foreach ($registers as $_register) {
            (new $_register($this))->registerAll();
        }
    }

    public function addTab(string $key, string $url, string $group): void
    {
        if (!array_key_exists($group, $this->_tabs)) {
            $this->_tabs[$group] = [];
        }

        $this->_tabs[$group][$key] = $url;
    }


    /**
     * Returns the $tab if it is valid, the first one from $this->_tabs if not
     *
     * @param string $tab The tab to validate
     *
     * @return mixed
     */
    public function getValidTab(?string $tab)
    {
        if (!$this->mod_active) {
            return null;
        }

        // Try to access wanted tab
        $tabPath = ($tab !== null) ? "./modules/$this->mod_name/$tab.php" : null;
        if (!$tabPath || (!is_file($tabPath) && !$this->isMatchLegacyController($tab))) {
            return $this->getFirstRegisteredTab();
        }

        return $tab;
    }

    /**
     * @return CRequirementsManager|string|null
     * @throws CRequirementsException
     */
    public function getRequirements()
    {
        $cache = new Cache(__METHOD__, $this->mod_name, Cache::INNER_OUTER);
        if ($data = $cache->get()) {
            return $data;
        }

        $classes = CClassMap::getInstance()->getClassChildren(CRequirementsManager::class, true, true, $this->mod_name);
        if (empty($classes)) {
            return null;
        }

        if (count($classes) > 1) {
            throw new CRequirementsException(CRequirementsException::TOO_MUCH_REQUIREMENTS_CLASS, $this->mod_name);
        }

        $class = reset($classes);

        return $cache->put($class, true);
    }

    public static function clearCacheRequirements(): void
    {
        /** @var CModule $module */
        foreach (CModule::$installed as $module) {
            (new Cache(self::class . '::getRequirements', $module->mod_name, Cache::INNER_OUTER))->rem();
        }
    }

    /**
     * Shows the list of available tabs
     *
     * @return void
     */
    public function showTabs(): void
    {
        if (!$this->checkActive()) {
            return;
        }

        global $uistyle, $tab, $a, $action, $actionType;

        // Try to access wanted tab
        $tabPath = "./modules/$this->mod_name/$tab.php";
        if (!is_file($tabPath) && !$this->isMatchLegacyController($tab)) {
            CAppUI::accessDenied();
        }

        // Tab becomes an action if unique
        if ($this->countTabs() == 1) {
            $a = $tab;
            $this->showAction();

            return;
        }

        $action     = $tab;
        $actionType = "tab";

        // Show tabbox
        $main = new CMainController();
        $main->tabboxOpen($this->_tabs, $tab);

        if (is_file($tabPath)) {
            include_once $tabPath;
        } elseif ($controller = $this->matchLegacyController($action)) {
            $controller->$action();
        }

        $main->tabboxClose();
    }

    /**
     * Shows the "action" page
     *
     * @return void
     */
    public function showAction(): void
    {
        if (!$this->checkActive()) {
            return;
        }

        global $a, $action, $actionType;

        if ($a === 'index') {
            return;
        }

        $action     = $a;
        $actionType = "a";
        $actionPath = "./modules/$this->mod_name/$a.php";

        if (is_file($actionPath)) {
            include_once $actionPath;
        } elseif ($controller = $this->matchLegacyController($action)) {
            $controller->$action();
        }
    }

    /**
     * @param $action
     *
     * @return bool
     */
    public function isMatchLegacyController($action): bool
    {
        return (bool)$this->matchLegacyController($action, false);
    }


    public function matchLegacyController($action, $instance = true)
    {
        if (!$this->mod_name) {
            return;
        }

        $cache = new Cache(__METHOD__, $this->mod_name, Cache::INNER_OUTER);

        if (!$cache->exists()) {
            $cache->put(CClassMap::getInstance()->getLegacyActions($this->mod_name));
        }

        $legacy_actions = $cache->get();

        if (array_key_exists($action, $legacy_actions)) {
            $controller = $legacy_actions[$action];
            if ($instance) {
                return new $controller();
            }

            return $controller;
        }
    }

    /**
     * Checks if the module is active
     *
     * @return bool
     */
    public function checkActive(): bool
    {
        if (!$this->mod_active) {
            (new CMainController())->moduleInactive();

            return false;
        }

        return true;
    }

    /**
     * Checks the modules related cache item tags
     *
     * @return bool True if modules did not change, false otherwise
     * @throws Exception
     */
    public static function checkModulesCacheTags()
    {
        if (self::$modules_hash === null) {
            $modules_from_classmap = CClassMap::getInstance()->getModules();
            self::$modules_hash    = hash('sha256', implode('|', $modules_from_classmap));
        }

        $cache = new Cache(__CLASS__, 'modules_hash', Cache::INNER_OUTER);

        if ($cache->get() !== self::$modules_hash) {
            $cache->put(self::$modules_hash);

            return false;
        }

        return true;
    }

    /**
     * Check if a module exist
     *
     * @param string $moduleName Module name
     *
     * @return bool true if the module exists
     */
    public static function exists(string $moduleName): bool
    {
        $modules_cache = new Cache(__METHOD__, '::all', Cache::INNER_OUTER);

        // Todo: Note that the tag is invalided by fw AFTER we check modules existency,
        // so it will be renewed on the second call only...
        if (!self::checkModulesCacheTags()) {
            $modules_cache->rem();
        }

        $exists = $modules_cache->get();

        $exists = (is_array($exists)) ? $exists : [];

        if (array_key_exists($moduleName, $exists)) {
            return $exists[$moduleName];
        }

        if (is_dir("./modules/$moduleName")) {
            $exists[$moduleName] = true;
            $modules_cache->put($exists);

            return true;
        }

        $moduleName = lcfirst($moduleName);

        $exists[$moduleName] = is_dir("./modules/$moduleName");
        $modules_cache->put($exists);

        return $exists[$moduleName];
    }

    /**
     * Returns all or a named installed module
     *
     * @param string $moduleName Module name
     *
     * @return CModule|CModule[]
     */
    public static function getInstalled(?string $moduleName = null)
    {
        if ($moduleName) {
            if (isset(self::$installed[$moduleName])) {
                return self::$installed[$moduleName];
            }

            $moduleName = lcfirst($moduleName);

            return isset(self::$installed[$moduleName]) ? self::$installed[$moduleName] : null;
        }

        return self::$installed;
    }

    /**
     * Returns all or a named active module
     *
     * @param string $moduleName Module name
     *
     * @return CModule|CModule[]
     */
    public static function getActive(?string $moduleName = null)
    {
        if ($moduleName) {
            if (isset(self::$active[$moduleName])) {
                return self::$active[$moduleName];
            }

            $moduleName = lcfirst($moduleName);

            return isset(self::$active[$moduleName]) ? self::$active[$moduleName] : null;
        }

        return self::$active;
    }

    /**
     * Checks if the current module is obsolete
     * Used by CPermission::check
     *
     * @param string $module_name
     *
     * @return bool
     */
    public static function getObsolete(string $module_name, $a = null): bool
    {
        $obsolete_module = false;
        $user            = CAppUI::$user;

        // We check only when not in the "system" module, and not in an "action" (ajax, etc)
        // And when user is undefined or admin
        if ($module_name && $module_name != "system" && (!$a || $a == "index")
            && (!$user || !$user->_id || $user->isAdmin())
        ) {
            $setupclass = CSetup::getCSetupClass($module_name);
            $setup      = new $setupclass();

            $module = new CModule();
            $module->compareToSetup($setup);

            $obsolete_module = $module->_upgradable;
        }

        return $obsolete_module;
    }

    /**
     * Returns all or a named visible module
     *
     * @param string $moduleName Module name
     *
     * @return CModule|CModule[]
     */
    public static function getVisible(?string $moduleName = null)
    {
        if ($moduleName) {
            if (isset(self::$visible[$moduleName])) {
                return self::$visible[$moduleName];
            }

            $moduleName = lcfirst($moduleName);

            return isset(self::$visible[$moduleName]) ? self::$visible[$moduleName] : null;
        }

        return self::$visible;
    }

    /**
     * Get CanDo object for given installed module,
     *
     * @param string $moduleName Module name
     *
     * @return CCanDo with no permission if module not installed
     */
    public static function getCanDo(string $moduleName): CCanDo
    {
        $module = self::getInstalled($moduleName);

        return $module ? $module->canDo() : new CCanDo();
    }

    /**
     * Reorder modules' ranks
     *
     * @return void
     */
    public function reorder(): void
    {
        /** @var self[] $all_modules */
        $all_modules = $this->loadList(null, "mod_ui_order");

        $i = 1;
        foreach ($all_modules as $_module) {
            $_module->mod_ui_order = $i++;
            $_module->store();
        }
    }

    /**
     * Install a module and reorders the list
     *
     * @return bool
     */
    public function install(): bool
    {
        if ($msg = $this->store()) {
            return false;
        }

        $this->reorder();

        return true;
    }

    /**
     * Modification de le l'ordre d'affichage du module
     *
     * @param string $dirn type de modification de l'ordre des modules
     *
     * @return void
     */
    public function move(string $dirn): void
    {
        $temp = $this->mod_ui_order;
        if ($dirn == "moveup") {
            $temp--;
            $query = "UPDATE modules SET mod_ui_order = (mod_ui_order+1) WHERE mod_ui_order = $temp";
            $this->_spec->ds->exec($query);
        } elseif ($dirn == "movedn") {
            $temp++;
            $query = "UPDATE modules SET mod_ui_order = (mod_ui_order-1) WHERE mod_ui_order = $temp";
            $this->_spec->ds->exec($query);
        }

        $query = "UPDATE modules SET mod_ui_order = $temp WHERE mod_id = $this->mod_id";
        $this->_spec->ds->exec($query);

        $this->mod_id = $temp;

        $this->reorder();
    }

    /**
     * Upgrade all modules
     *
     * @return void
     */
    public static function upgradeAll(): void
    {
        /** @var self[] $installed */
        $installed = self::loadModulesFromCache();

        $upgradeables = [];

        foreach ($installed as $_module) {
            $setupClass = CSetup::getCSetupClass($_module->mod_name);
            if (!$setupClass) {
                continue;
            }

            /** @var CSetup $setup */
            $setup = new $setupClass();
            $_module->compareToSetup($setup);

            if ($_module->_upgradable) {
                $upgradeables[$_module->mod_name] = [
                    "module" => $_module,
                    "setup"  => $setup,
                ];
            }
        }

        foreach ($upgradeables as $_upgrade) {
            /** @var CModule $_module */
            $_module = $_upgrade["module"];

            /** @var CSetup $_setup */
            $_setup = $_upgrade["setup"];

            if ($_setup->upgrade($_module)) {
                if ($_setup->mod_version == $_module->mod_version) {
                    CAppUI::setMsg(
                        "Installation de '%s' à la version %s",
                        UI_MSG_OK,
                        $_module->mod_name,
                        $_setup->mod_version
                    );
                } else {
                    CAppUI::setMsg(
                        "Installation de '%s' à la version %s sur %s",
                        UI_MSG_WARNING,
                        $_module->mod_name,
                        $_module->mod_version,
                        $_setup->mod_version
                    );
                }
            } else {
                CAppUI::setMsg("Module '%s' non mis à jour", UI_MSG_WARNING, $_module->mod_name);
            }
        }
    }

    /**
     * Prefix the module name with 'dP' if needed
     *
     * @param string $module Module name
     *
     * @return string
     */
    public static function prefixModuleName(string $module): string
    {
        static $_cache = [];

        if (isset($_cache[$module])) {
            return $_cache[$module];
        }

        // dP ugly prefix hack
        if (!is_dir(__DIR__ . "/../../../modules/$module") && strpos($module, "dP") !== 0) {
            $module = "dP$module";
        }

        return $_cache[$module] = $module;
    }

    /**
     * Remove modules' cache
     *
     * @return void
     */
    public static function removeModulesCache(): void
    {
        $cache = new Cache(__CLASS__, '::all', Cache::INNER_OUTER);
        $cache->rem();
    }

    /**
     * @return CSetup|bool
     */
    public function getCSetup()
    {
        $class_name = $this->_namespace . "\\Setup";

        return class_exists($class_name) ? new $class_name() : false;
    }

    /**
     * Set the current Module Color
     *
     * @return string
     */
    public function loadColor(): string
    {
        if ($this->mod_custom_color) {
            return $this->_color = "#$this->mod_custom_color";
        }
        if (array_key_exists($this->mod_category, self::$category_color)) {
            return $this->_color = "#" . self::$category_color[$this->mod_category];
        }

        return $this->_color = "#" . self::$category_color["obsolete"];
    }

    private function getFirstRegisteredTab(): ?string
    {
        foreach ($this->_tabs as $_group => $_tabs) {
            foreach ($_tabs as $_tab => $_url) {
                return $_tab;
            }
        }

        return null;
    }

    private function countTabs(): int
    {
        $count = 0;
        foreach ($this->_tabs as $_group => $_tabs) {
            $count += count($_tabs);
        }

        return $count;
    }

    private function countTabGroups(): int
    {
        return count($this->_tabs);
    }
}
