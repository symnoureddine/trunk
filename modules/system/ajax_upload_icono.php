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
use Ox\Core\CMbPath;
use Ox\Core\Module\CModule;
use Ox\Core\CView;

CCanDo::checkAdmin();

$image     = CView::get("image", "str");
$module_id = CView::get("module_id", "ref class|CModule");

CView::checkin();

if (CAppUI::conf('instance_role') !== 'qualif') {
  CApp::json(0);
}

$image_data = str_replace(' ', '+', $image);
$image_data = base64_decode(substr($image_data, strpos($image_data, ",") + 1));

$module = new CModule();
$module->load($module_id);

if (!$module->_id) {
  CApp::json(0);
}


$root       = rtrim(CAppUI::conf('root_dir'), '/');
$module_rep = "{$root}/modules/{$module->mod_name}";
foreach (array('de', 'en', 'fr', 'fr-be', 'nl_be') as $lang) {
  CMbPath::forceDir("{$module_rep}/images");
  CMbPath::forceDir("{$module_rep}/images/iconographie");
  CMbPath::forceDir("{$module_rep}/images/iconographie/{$lang}");

  $file_handle = fopen("$module_rep/images/iconographie/$lang/icon.png", "w+");
  fwrite($file_handle, $image_data);
  fclose($file_handle);
}

CApp::json("./modules/$module->mod_name/images/iconographie/$lang/icon.png");
