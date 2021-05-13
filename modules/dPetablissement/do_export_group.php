<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\Import\CMbObjectExport;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;

CCanDo::checkAdmin();

$group_id = CView::get("group_id", "ref class|CGroups");

CView::checkin();

CStoredObject::$useObjectCache = false;

$backrefs_tree = array(
  "CGroups" => array(
    "functions",
    "blocs",
    "services",
    "secteurs",
    "unites_fonctionnelles",
  ),
  "CFunctions" => array(
    "users",
  ),
  "CBlocOperatoire" => array(
    "salles",
  ),
  "CService" => array(
    "chambres",
  ),
  "CChambre" => array(
    "lits",
  ),
);

$fwdrefs_tree = array(
  "CMediusers" => array(
    "user_id",
  ),
);

$group = CGroups::get($group_id);

try {
  $export = new CMbObjectExport($group, $backrefs_tree);
  $export->empty_values = false;
  $export->setForwardRefsTree($fwdrefs_tree);
  $export->streamXML();
}
catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}
