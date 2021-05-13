<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;


use Exception;
use Ox\Core\FieldSpecs\CEnumSpec;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Core\Module\CModule;
use Ox\Mediboard\System\CConfigurationModelManager;
use Ox\Mediboard\System\CTranslationOverwrite;

/**
 * Translations manager class
 */
class CTranslation {
  const COMMON_LOCALES = "locales/*/common.php";

  private $module;
  private $language;
  private $reference;

  private $classes = [];
  private $languages = [];
  private $locales_dirs = [];
  private $ref_items = [];
  private $archives = [];
  private $translations_overload = [];

  // Old globs
  private $trans = [];
  private $items = [];
  private $all_items = [];
  private $completions = [];
  private $all_locales = [];
  private $all_locales_lang = [];
  private $total_count = 0;
  private $local_count = 0;

  /**
   * Compute the translations files to build a translation array
   *
   * @param string $module    Module to get translations for
   * @param string $language  Language to get translations in
   * @param string $reference Reference language to get translations in another language
   *
   * @return array|mixed
   * @throws CMbException
   */
  public function getTranslationsFor($module, $language, $reference) {
    $this->module    = $module;
    $this->language  = $language;
    $this->reference = $reference;

    // Récupération des langues disponibles
    $this->setLanguages();

    $this->setTranslationsOverload();

    // Récupération des chemins des fichiers de trads
    $this->setLocalesDirectories();

    // Chargement des locales pour chaque langue
    $this->setFileForAllLanguages();

    // Préparation de $this->trans avec en clé la chaine puis un tableau de langues et les traductions pour ces langues
    $this->setTranslations();

    $this->all_locales_lang = (isset($this->all_locales[$language])) ? $this->all_locales[$language] : [];
    $this->sanitizeLocales();

    // Ajout des locales suppémentaires à $this->all_locales
    $this->addReferencesLocales();

    // Pour chaque class ajout des locales
    $this->setClassesForModule();

    foreach ($this->classes as $_class) {
      $this->addLocalesForClass($_class);
    }

    // Pour chaque config ajout des locales
    if ($module && $module != "common") {
      // Add the configs from the default conf tree
      $model = CConfigurationModelManager::_getModel($module);
      $this->addLocalesForConfigs($model);

      // Add the configs that exists in config.php
      if ($categories = @CAppUI::conf($module)) {
        foreach ($categories as $category => $values) {
          $this->addConfigCategoryLocales($module, $category, $values);
        }
      }

      // Ajout de toutes les configurations qui ne sont pas dans d'autres modules
      if ($module == "system") {
        foreach (CAppUI::conf() as $chapter => $values) {
          if (!CModule::exists($chapter) && $chapter != "db") {
            $this->addConfigCategoryLocales(null, $chapter, $values);
          }
        }
      }

      // Pour chaque tabs ajout des locales
      $this->addTabsActionLocales();
    }

    // Ajout des locales restantes
    $this->addRemainingLocales();

    return $this->trans;
  }

  /**
   * @return int
   */
  public function getCompletion() {
    return $this->total_count ? round(100 * $this->local_count / $this->total_count) : 0;
  }

  /**
   * @return int
   */
  public function getTotalCount() {
    return $this->total_count;
  }

  /**
   * @return int
   */
  public function getLocalCount() {
    return $this->local_count;
  }

  /**
   * @return array
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * @return array
   */
  public function getArchives() {
    return $this->archives;
  }

  /**
   * @return array
   */
  public function getCompletions() {
    return $this->completions;
  }

  /**
   * @return array
   */
  public function getLanguages() {
    return $this->languages;
  }

  /**
   * @return array
   */
  public function getRefItems() {
    return $this->ref_items;
  }

  /**
   * Get the differents existing languages
   *
   * @return void
   */
  private function setLanguages() {
    $files = glob(static::COMMON_LOCALES);

    foreach ($files as $file) {
      $name                   = basename(dirname($file));
      $this->languages[$name] = $file;
    }
  }

  /**
   * Get the locales directories and store the paths in $this->locales_dirs
   *
   * @return void
   */
  private function setLocalesDirectories() {
    if ($this->module != "common") {
      $files = glob("modules/$this->module/locales/*");

      foreach ($files as $file) {
        $name                      = basename($file, ".php");
        $this->locales_dirs[$name] = $file;
      }
    }
    else {
      $this->locales_dirs = $this->languages;
    }
  }

  /**
   * Prepare the locales for the current module for each language
   *
   * @return void
   */
  private function setFileForAllLanguages() {
    // Récupération du fichier demandé pour toutes les langues
    $translate_module             = new CMbConfig();
    $translate_module->sourcePath = null;
    foreach ($this->locales_dirs as $locale => $path) {
      $translate_module->options    = array("name" => "locales");
      $translate_module->targetPath = $path;
      try {
        $translate_module->load();

        $this->all_locales[$locale] = $translate_module->values;
      }
      catch (Exception $e) {
        CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);
      }

    }

    // Reference items
    $this->ref_items = ($this->reference && $this->reference != $this->language) ? $this->all_locales[$this->reference] : array();
  }

  /**
   * Prepare the locales for each language
   *
   * @return void
   */
  private function setTranslations() {
    foreach ($this->locales_dirs as $locale => $path) {
      foreach ($this->all_locales[$locale] as $k => $v) {
        $key = (is_int($k) ? $v : $k);
        $this->trans[$key][$locale] = $v;
      }
    }

    foreach ($this->translations_overload as $_key => $_trans) {
      if (isset($this->trans[$_key])) {
        $this->trans[$_key][$this->language] = $_trans;
      }
    }
  }

  /**
   * Sanitize the locales. Line feeds won't get properly stored if escaped
   *
   * @return void
   */
  private function sanitizeLocales() {
    foreach ($this->all_locales_lang as &$_locale) {
      $_locale = str_replace(array('\n', '\t'), array("\n", "\t"), $_locale);
    }
  }

  /**
   * Add locales remaining in ref language
   *
   * @return void
   */
  private function addReferencesLocales() {
    // Add other remaining locales from reference language if defined
    foreach (array_keys($this->ref_items) as $_item) {
      if (!array_key_exists($_item, $this->all_locales_lang)) {
        $this->all_locales_lang[$_item] = "";
      }
    }
  }

  /**
   * Set the classes for the current module
   *
   * @return void
   */
  private function setClassesForModule() {
    if ($this->module != "common") {
      $this->classes = CModule::getClassesFor($this->module);

      // Hack to have CModule in system locale file
      if ($this->module == "system") {
        $this->classes[] = "CModule";
      }
    }
    else {
      $this->classes = array();
    }
  }

  /**
   * Add the locales keys for a class
   *
   * @param string $class Class to add locales for
   *
   * @return void
   */
  private function addLocalesForClass($class) {
    /** @var CModelObject $object */
    $object = new $class;
    $classname = $object->_class;

    // Traductions au niveau classe
    $this->addLocale($classname, $classname, "$classname");

    if ($object->_spec->archive) {
      $this->archives[$class] = true;
      return;
    }

    // Add default class locales
    $this->addLocale($classname, $classname, "$classname.none");
    $this->addLocale($classname, $classname, "$classname.one");
    $this->addLocale($classname, $classname, "$classname.all");
    $this->addLocale($classname, $classname, "$classname-msg-create");
    $this->addLocale($classname, $classname, "$classname-msg-modify");
    $this->addLocale($classname, $classname, "$classname-msg-delete");
    $this->addLocale($classname, $classname, "$classname-title-create");
    $this->addLocale($classname, $classname, "$classname-title-modify");

    $this->addLocalesFromProperties($object);
  }

  /**
   * Add locales keys for each prop from the class
   *
   * @param CModelObject $object Object to add locales for
   *
   * @return void
   */
  private function addLocalesFromProperties($object) {
    $classname = $object->_class;

    // Translate key
    if ($object->_spec->key) {
      $prop = $object->_spec->key;

      $this->addLocale($classname, $prop, "$classname-$prop");
      $this->addLocale($classname, $prop, "$classname-$prop-desc");
      $this->addLocale($classname, $prop, "$classname-$prop-court");
    }

    // Traductions de chaque propriété
    foreach ($object->_specs as $prop => $spec) {
      if (!$spec->prop) {
        continue;
      }

      if (in_array($prop, array($object->_spec->key, "_view", "_shortview"))) {
        continue;
      }

      $this->addLocale($classname, $prop, "$classname-$prop");
      $this->addLocale($classname, $prop, "$classname-$prop-desc");
      $this->addLocale($classname, $prop, "$classname-$prop-court");

      if ($spec instanceof CEnumSpec) {
        $this->addEnumLocales($classname, $spec, $prop);
      }

      if ($spec instanceof CRefSpec && $prop[0] != "_") {
        $this->addRefLocales($object, $spec, $prop);
      }
    }

    // Traductions pour les uniques
    foreach (array_keys($object->_spec->uniques) as $unique) {
      $this->addLocale($classname, "Failures", "$classname-failed-$unique");
    }
  }

  /**
   * Add locales keys from enum props
   *
   * @param string    $classname Name of the class
   * @param CEnumSpec $spec      Enum spec to add locales for
   * @param string    $prop      Prop to use
   *
   * @return void
   */
  private function addEnumLocales($classname, $spec, $prop) {
    if (!$spec->notNull) {
      $this->addLocale($classname, $prop, "$classname.$prop.");
    }

    foreach (explode("|", $spec->list) as $value) {
      $this->addLocale($classname, $prop, "$classname.$prop.$value");
    }
  }

  /**
   * Add locales keys from ref specs
   *
   * @param CModelObject $object Object to get locales for
   * @param CRefSpec     $spec   Ref spec to add locales for
   * @param string       $prop   Prop to use
   *
   * @return void
   */
  private function addRefLocales($object, $spec, $prop) {
    $classname = $object->_class;

    if ($spec->meta && $object->_specs[$spec->meta] instanceof CEnumSpec) {
      $classes = $object->_specs[$spec->meta]->_list;
      foreach ($classes as $fwd_class) {
        $this->addBackLocales($fwd_class, $spec, $classname, $prop);
      }
    }
    else {
      $fwd_class = $spec->class;
      $this->addBackLocales($fwd_class, $spec, $classname, $prop);
    }
  }

  /**
   * Add back refs locales
   *
   * @param string   $fwd_class Forward class to get locales for
   * @param CRefSpec $spec      Ref spec to add locales for
   * @param string   $classname Main class name
   * @param string   $prop      Prop to use
   *
   * @return void
   */
  private function addBackLocales($fwd_class, $spec, $classname, $prop) {
    $fwd_object = new $fwd_class;

    // Find corresponding back name
    // Use preg_grep to match backprops like "CClass field cascade"
    $back_name = preg_grep("/{$spec->className} {$spec->fieldName}\s?.*/", $fwd_object->_backProps);
    if (is_array($back_name)) {
      $back_array = array_keys($back_name);
      $back_name = reset($back_array);
    }

    $this->addLocale($classname, $prop, "$spec->class-back-$back_name");
    $this->addLocale($classname, $prop, "$spec->class-back-$back_name.empty");
  }

  /**
   * Add locales from module configs
   *
   * @param array $model Configuration model to parse
   *
   * @return void
   */
  private function addLocalesForConfigs($model) {
    $features = array();
    foreach ($model as $_model) {
      foreach ($_model as $_feature => $_submodel) {
        if (strpos($_feature, $this->module) === 0) {
          $parts = explode(" ", $_feature);
          array_shift($parts); // Remove module name
          $item = array_pop($parts);   // Remove config name
          $prefix = implode("-", $parts);
          if (!isset($features[$prefix])) {
            $features[$prefix] = array();
          }

          $features[$prefix][$item] = $item;
        }
      }
    }

    foreach ($features as $_prefix => $values) {
      $this->addConfigCategoryLocales($this->module, $_prefix, null, false);
      $this->addConfigCategoryLocales($this->module, $_prefix, $values);
    }
  }

  /**
   * Add locales from module action
   *
   * @return void
   */
  private function addTabsActionLocales() {
    $files = CAppUI::readFiles("modules/$this->module", '\.php$');

    $this->addLocale("Action", "Name", "module-$this->module-court");
    $this->addLocale("Action", "Name", "module-$this->module-long");

    foreach ($files as $_file) {
      $_tab = substr($_file, 0, -4);

      if (in_array($_tab, array("setup", "index", "config", "preferences", "configuration"))) {
        continue;
      }

      $this->addLocale("Action", "Tabs", "mod-$this->module-tab-$_tab");
    }
  }

  /**
   * Add remaining locales
   *
   * @return void
   */
  private function addRemainingLocales() {
    // Remaining locales go to an 'other' with a computed category
    foreach (array_keys($this->all_locales_lang) as $_item) {
      // Explode en dashes and dots
      $parts = explode(".", str_replace("-", ".", $_item));
      $this->addLocale("Other", $parts[0], $_item);
    }
  }

  /**
   * Get the CTranslationOverload for the current language
   *
   * @return void
   */
  private function setTranslationsOverload() {
    try {
      $trans = new CTranslationOverwrite();
      $trans->language = $this->language;
      $translations = $trans->loadMatchingListEsc();
    }
    catch (Exception $e) {
      $translations = [];
    }


    /** @var CTranslationOverwrite $_trans */
    foreach ($translations as $_trans) {
      $this->translations_overload[$_trans->source] = $_trans->translation;
    }
  }

  /**
   * Add a locale item in a three levels collection
   * (Yet more of an internationalisation item)
   *
   * @param string $class Class name
   * @param string $cat   Category name
   * @param string $name  Item name
   *
   * @return void
   */
  private function addLocale($class, $cat, $name) {
    $this->items[$class][$cat][$name] = "";

    if (array_key_exists($name, $this->trans) && isset($this->trans[$name][$this->language])) {
      $this->items[$class][$cat][$name] = $this->trans[$name][$this->language];
    }

    $this->all_items[$name] = true;

    unset($this->all_locales_lang[$name]);

    // Stats
    if (!isset($this->completions[$class])) {
      $this->completions[$class] = [
        'total'   => 0,
        'count'   => 0,
        'percent' => 0,
      ];
    }

    $this->completions[$class]["total"]++;
    $this->total_count++;

    if ($this->items[$class][$cat][$name]) {
      $this->completions[$class]["count"]++;
      $this->local_count++;
    }

    $this->completions[$class]["percent"] = round(100 * $this->completions[$class]["count"] / $this->completions[$class]["total"]);
  }

  /**
   * Add locale item for config category values
   *
   * @param string     $chapter  Chapter name
   * @param string     $category Category name
   * @param null|array $values   Key-value array when necessary
   * @param bool       $add_desc Tell wether shoud add a description locale item
   *
   * @return void
   */
  function addConfigCategoryLocales($chapter, $category, $values, $add_desc = true) {
    $prefix = $chapter ? "$chapter-$category" : $category;

    if (!is_array($values)) {
      $this->addLocale("Config", "global", "config-$prefix");
      if ($add_desc) {
        $this->addLocale("Config", "global", "config-$prefix-desc");
      }
      return;
    }

    foreach ($values as $key => $value) {
      $this->addLocale("Config", $category, "config-$prefix-$key");
      if ($add_desc) {
        $this->addLocale("Config", $category, "config-$prefix-$key-desc");
      }
    }
  }
}
