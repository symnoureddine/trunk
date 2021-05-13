<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbString;
use Ox\Core\Module\CModule;
use Ox\Core\SHM;
use Ox\Mediboard\System\CConfigurationModelManager;
use Ox\Mediboard\System\CTranslationOverwrite;

global $version;

CCanDo::checkEdit();

/////////// Locales
foreach (glob("locales/*", GLOB_ONLYDIR) as $localeDir) {
  $localeName = basename($localeDir);
  $locales = array();
  $localeFiles = CAppUI::getLocaleFilesPaths($localeName);
  
  foreach ($localeFiles as $localeFile) {
    if (basename($localeFile) != "meta.php") {
      include $localeFile;
    }
  }
  
  $locales = CMbString::filterEmpty($locales);
  foreach ($locales as &$_locale) {
    $_locale = CMbString::unslash($_locale);
  }
  
  $path = "./tmp/locales-$localeName.js";
  if (!is_file($path)) {
    CAppUI::stepAjax("Locales-javascript-cache-none", UI_MSG_OK, $localeName);
    continue;
  }
  
  $fp = fopen($path, 'r');
  preg_match('#^//(\d+)#', fgets($fp), $v);
  if ($v[1] < $version['build']) {
    CAppUI::stepAjax("Locales-javascript-cache-ko", UI_MSG_WARNING, $localeName);
    fclose($fp);
    continue;
  }

  if (null == SHM::get("locales-$localeName-".CAppUI::LOCALES_PREFIX)) {
    CAppUI::stepAjax("Locales-shm-none", UI_MSG_OK, $localeName);
    continue;
  }

  // Load overwritten locales if the table exists
  $overwrite = new CTranslationOverwrite();
  if ($overwrite->isInstalled()) {
    $locales = $overwrite->transformLocales($locales);
  }

  $cached_locales = CAppUI::flattenCachedLocales($localeName);

  if ($cached_locales != $locales) {
    CAppUI::stepAjax("Locales-shm-ko", UI_MSG_WARNING, $localeName);
    continue;
  }
  
  CAppUI::stepAjax("Locales-shm-ok", UI_MSG_OK, $localeName);
}

// Not used yet (because of PHP 5.1)
//if (null == SHM::get("modules")) {
//  CAppUI::stepAjax("Modules-shm-none", UI_MSG_OK);
//}

////////// Configuration model
$error = null;
foreach (CModule::getInstalled() as $_mod) {
  $cache_status = CConfigurationModelManager::getModelCacheStatus($_mod->mod_name);
  switch ($cache_status) {
    case "empty":
      $error = 'empty';
      break 2;
    case "dirty":
      $error = 'dirty';
      break 2;
    case "ok":
      $error = 'ok';
      break;
  }
}

switch ($error) {
  case 'empty':
    CAppUI::stepAjax("ConfigModel-shm-none", UI_MSG_OK);
    break;
  case 'dirty':
    CAppUI::stepAjax("ConfigModel-shm-ko", UI_MSG_WARNING);
    break;
  case 'ok':
    CAppUI::stepAjax("ConfigModel-shm-ok", UI_MSG_OK);
    break;
}


////////// Configuration values
$error = null;
foreach (CModule::getInstalled() as $_mod) {
  $cache_status = CConfigurationModelManager::getValuesCacheStatus($_mod->mod_name);
  switch ($cache_status) {
    case "empty":
      $error = 'empty';
      break 2;
    case "dirty":
      $error = 'dirty';
      break 2;
    case "ok":
      $error = 'ok';
      break;
  }
}

switch ($error) {
  case "empty":
    CAppUI::stepAjax("ConfigValues-shm-none", UI_MSG_OK);
    break;
  case "dirty":
    CAppUI::stepAjax("ConfigValues-shm-ko", UI_MSG_WARNING);
    break;
  case "ok":
    CAppUI::stepAjax("ConfigValues-shm-ok", UI_MSG_OK);
    break;
}


// Smarty templates
$templates = glob("tmp/templates_c/*/*");
CAppUI::stepAjax("template-cache-ok", UI_MSG_OK, count($templates));

// Module specific checkings
foreach (glob("modules/*/check_shared_memory.php") as $script) {
  include $script;
}
