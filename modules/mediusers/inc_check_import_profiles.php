<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusersXMLImport;

CCanDo::checkAdmin();

$directory = CView::get('directory', 'str notNull');

CView::checkin();

if (!is_dir($directory)) {
  CAppUI::stepAjax('mod-dPpatients-directory-unavailable', UI_MSG_ERROR, $directory);
}

$directory = str_replace("\\\\", "\\", $directory);
$group     = CGroups::loadCurrent();

$iterator = new DirectoryIterator($directory);

$import_users = array();

foreach ($iterator as $_file_infos) {
  if ($_file_infos->isDot() || !$_file_infos->isDir()) {
    continue;
  }

  if (strpos($_file_infos->getFilename(), 'CUser-') !== 0) {
    continue;
  }

  $dir_path = rtrim($_file_infos->getRealPath(), '/\\');
  if (!is_file($dir_path . '/export.xml')) {
    CAppUI::stepAjax('CUsers-import-no-file', UI_MSG_WARNING, $dir_path . '/export.xml');
    continue;
  }

  $import = new CMediusersXMLImport($dir_path . '/export.xml');
  $user   = $import->getProfileFromXML();

  $new_hash_perm_obj = $import->getHashFromXML('CPermObject');
  $new_hash_perm_mod = $import->getHashFromXML('CPermModule');
  $new_hash_prefs    = $import->getHashFromXML('CPreferences');

  if (!$user) {
    CAppUI::stepAjax('CUser-import-no-user', UI_MSG_WARNING, $dir_path . '/export.xml');
    continue;
  }

  $nb_perms_mod = $import->getCount('CPermModule');
  $nb_perms_obj = $import->getCount('CPermObject');
  $nb_prefs     = $import->getCount('CPreferences');

  $new           = true;
  $hash_perm_obj = '';
  $hash_perm_mod = '';
  $hash_prefs    = '';
  if ($user->_id) {
    $hash_perm_obj = $user->getPermObjectHash();
    $hash_perm_mod = $user->getPermModulesHash();
    $hash_prefs    = $user->getPrefsHash();
    $new           = false;
  }

  $import_users[$_file_infos->getFilename()] = array(
    'new'               => $new,
    'user'              => $user,
    'hash_perm_obj'     => $hash_perm_obj,
    'hash_perm_mod'     => $hash_perm_mod,
    'hash_prefs'        => $hash_prefs,
    'new_hash_perm_mod' => $new_hash_perm_mod,
    'new_hash_perm_obj' => $new_hash_perm_obj,
    'new_hash_prefs'    => $new_hash_prefs,
    'nb_perms_obj'      => $nb_perms_obj,
    'nb_perms_mod'      => $nb_perms_mod,
    'nb_prefs'          => $nb_prefs,
  );
}

$smarty = new CSmartyDP();
$smarty->assign('import_users', $import_users);
$smarty->assign('directory', str_replace('\\', '\\\\', $directory));
$smarty->display('inc_check_import_profiles.tpl');