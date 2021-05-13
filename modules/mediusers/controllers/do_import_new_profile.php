<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusersXMLImport;

CCanDo::checkAdmin();

$directory         = CView::post('directory', 'str notNull');
$file_name         = CView::post('file_name', 'str notNull');
$perms             = CView::post('perms', 'bool default|0');
$prefs             = CView::post('prefs', 'bool default|0');
$perms_functionnal = CView::post('perms_functionnal', 'bool default|0');
$new_name          = CView::post('new_name', 'str');

CView::checkin();

$directory = str_replace('\\\\', '\\', $directory);

if (!is_dir($directory)) {
  CAppUI::stepAjax('mod-dPpatients-directory-unavailable', UI_MSG_ERROR, $directory);
}

$file_path = rtrim($directory, '/\\') . '/' . $file_name . '/export.xml';

if (!file_exists($file_path)) {
  CAppUI::stepAjax('CFile-not-exists', UI_MSG_ERROR, $file_path);
}

CMediusersXMLImport::$_ignored_classes[] = 'CMediusers';

if (!$perms) {
  CMediusersXMLImport::$_ignored_classes[] = 'CPermObject';
  CMediusersXMLImport::$_ignored_classes[] = 'CPermModule';
}

$options = array(
  'perms'                    => $perms,
  'update_perms'             => 1,
  'prefs'                    => $prefs,
  'update_prefs'             => 1,
  'perms_functionnal'        => $perms_functionnal,
  'update_perms_functionnal' => 1,
  'default_prefs'            => 0,
);

if ($new_name) {
  $options['new_name'] = $new_name;
  $options['ignore_find'] = 1;
}

$group = CGroups::loadCurrent();

$import = new CMediusersXMLImport($file_path);
$import->setGroupId($group->_id);
$import->setDirectory(dirname($file_path));
$import->import(array(), $options);
