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
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusersXMLImport;

CCanDo::checkAdmin();

$user_guid = CView::get('user_guid', 'str notNull');
$directory = CView::get('directory', 'str notNull');

CView::checkin();

if (!is_dir($directory)) {
  CAppUI::stepAjax("mod-dPpatients-directory-unavailable", UI_MSG_ERROR, $directory);
}

$file_path = $directory . '/' . $user_guid . '/export.xml';

if (!file_exists($file_path)) {
  CAppUI::stepAjax('CFile-not-exists', UI_MSG_ERROR, $file_path);
}

$import = new CMediusersXMLImport($file_path);

$compare = $import->compareProfileFromXML();

$user_types = CUser::$types;

$smarty = new CSmartyDP();
$smarty->assign('compare', $compare);
$smarty->assign('user_types', $user_types);
$smarty->assign('file_name', $user_guid);
$smarty->assign('directory', $directory);
$smarty->display('inc_show_profile_compare.tpl');