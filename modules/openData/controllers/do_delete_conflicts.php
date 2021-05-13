<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\OpenData\CImportConflict;

CCanDo::checkAdmin();

$audit = CView::post('audit', 'bool default|1');

CView::checkin();

$import_conflict = new CImportConflict();
$ds = $import_conflict->getDS();

$query = "DELETE FROM `import_conflict` WHERE `audit` = '$audit' AND `object_class` = 'CMedecin' AND `import_tag` = 'import_rpps'";
$ds->exec($query);

$msg = ($audit) ? 'CImportConflict-delete-audit|pl' : 'CImportConflict-delete|pl';

CAppUI::js("ImportMedecins.resetDisplay();");

CAppUI::stepAjax($msg, UI_MSG_WARNING);