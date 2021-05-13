<?php
/**
 * @package Mediboard\Developpement\ClassMap
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;

CCanDo::checkAdmin();

$modules = new CModule();
$modules = $modules->loadList();

$spec = (new CModule())->getSpecs();

$smarty = new CSmartyDP();
$smarty->assign("category_list", $spec["mod_category"]->_list);
$smarty->assign("package_list", $spec["mod_package"]->_list);
$smarty->assign("modules", $modules);
$smarty->assign("all_modules_id", implode(",", CMbArray::pluck($modules, "_id")));
$smarty->display('vw_icono.tpl');
