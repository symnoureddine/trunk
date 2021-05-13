<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusersXMLImport;

CCanDo::checkAdmin();

$directory                = CView::post('directory', 'str notNull');
$user_id                  = CView::post('user_id', 'num');
$profile                  = CView::post('profile', 'bool default|0');
$perms                    = CView::post('perms', 'bool default|0');
$update_perms             = CView::post('update_perms', 'bool default|0');
$prefs                    = CView::post('prefs', 'bool default|0');
$update_prefs             = CView::post('update_prefs', 'bool default|0');
$perms_functionnal        = CView::post('perms_functionnal', 'bool default|0');
$update_perms_functionnal = CView::post('update_perms_functionnal', 'bool default|0');
$default_prefs            = CView::post('default_prefs', 'bool default|0');
$update_default_prefs     = CView::post('update_default_prefs', 'bool default|0');
$planning                 = CView::post('planning', 'bool default|0');
$tarification             = CView::post('tarification', 'bool default|0');
$update_tarification      = CView::post('update_tarification', 'bool default|0');
$functions                = CView::post('functions', 'bool default|0');
$ufs                      = CView::post('ufs', 'bool default|0');

CView::checkin();

if (!is_dir($directory)) {
  CAppUI::stepAjax("mod-dPpatients-directory-unavailable", UI_MSG_WARNING, $directory);

  return;
}

$options = array(
  'perms'                    => $perms,
  'update_perms'             => $update_perms,
  'prefs'                    => $prefs,
  'update_prefs'             => $update_prefs,
  'perms_functionnal'        => $perms_functionnal,
  'update_perms_functionnal' => $update_perms_functionnal,
  'default_prefs'            => $default_prefs,
  'update_default_prefs'     => $update_default_prefs,
  'tarification'             => $tarification,
  'update_tarification'      => $update_tarification,
  'create_functions'         => $functions,
  'create_ufs'               => $ufs,
);

$directory = str_replace("\\\\", "\\", $directory);
$group     = CGroups::loadCurrent();

$class_name = ($profile) ? 'CUser' : 'CMediusers';

CStoredObject::$useObjectCache = false;

if (!$profile) {
  CMediusersXMLImport::$_ignored_classes[] = 'CUser';
}

if (!$perms) {
  CMediusersXMLImport::$_ignored_classes[] = 'CPermObject';
  CMediusersXMLImport::$_ignored_classes[] = 'CPermModule';
}

if (!$planning) {
  CMediusersXMLImport::$_ignored_classes[] = 'CPlageconsult';
}

if ($user_id) {
  $user_id = (int)$user_id;
  $xmlfile = rtrim($directory, "/\\") . "/$class_name-$user_id/export.xml";
  if (!file_exists($xmlfile)) {
    CAppUI::js("$('wait-import-mediusers').innerText = ''");
    CAppUI::stepAjax('CFile-not-exists', UI_MSG_ERROR, $xmlfile);
  }

  $xmlfile  = realpath($xmlfile);
  $importer = new CMediusersXMLImport($xmlfile);
  $importer->setGroupId($group->_id);
  $importer->setDirectory(dirname($xmlfile));
  $importer->import(array(), $options);
}
else {
  $iterator = new DirectoryIterator($directory);

  $i = 0;

  foreach ($iterator as $_fileinfo) {
    if ($_fileinfo->isDot()) {
      continue;
    }

    if ($_fileinfo->isDir() && strpos($_fileinfo->getFilename(), "$class_name-") === 0) {
      $i++;

      $xmlfile = $_fileinfo->getRealPath() . "/export.xml";
      if (file_exists($xmlfile)) {
        $importer = new CMediusersXMLImport($xmlfile);
        $importer->setGroupId($group->_id);
        $importer->setDirectory($_fileinfo->getRealPath());

        $importer->import(array(), $options);
      }
    }
  }

  if ($default_prefs) {
    $xmlfile = rtrim($directory, '/\\') . "/CPreferences/export.xml";
    if (file_exists($xmlfile)) {
      $importer = new CMediusersXMLImport($xmlfile);
      $importer->setGroupId($group->_id);
      $importer->setDirectory(dirname($xmlfile));

      $importer->import(array(), $options);
    }
  }

  foreach (CMediusersXMLImport::$already_imported as $_class => $_nb) {
    if ($_nb > 0) {
      CAppUI::stepAjax("common-import-object-found", UI_MSG_OK, $_nb, CAppUI::tr($_class));
    }
  }
}

CAppUI::js("$('wait-import-mediusers').innerText = ''");