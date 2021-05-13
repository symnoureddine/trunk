<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSetup;
use Ox\Core\CSmartyDP;

CCanDo::checkAdmin();

$datasource_configs = CAppUI::conf("db");

$setupClasses = CApp::getChildClasses(CSetup::class);
$mbmodules = array(
  "notInstalled" => array(),
  "installed" => array(),
);

$datasources = array();

foreach ($setupClasses as $setupClass) {
  if (!class_exists($setupClass)) {
    continue;
  }

  /** @var CSetup $setup */
  $setup = new $setupClass;
  $mbmodule = new CModule();
  $mbmodule->compareToSetup($setup);
  $mbmodule->updateFormFields();
  
  if (count($setup->datasources)) {
    if (!isset($datasources[$setup->mod_name])) {
      $datasources[$setup->mod_name] = array();
    }

    foreach ($setup->datasources as $_datasource => $_query) {
      $datasources[$setup->mod_name][] = $_datasource;
      unset($datasource_configs[$_datasource]);
    }
  }
}

$datasources["_other_"] = array_keys($datasource_configs);

$smarty = new CSmartyDP();
$smarty->assign("datasources", $datasources);
$smarty->display("vw_datasources.tpl");
