<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\Import\CMbXMLObjectImport;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Forms\CExClassImport;
use Ox\Mediboard\System\Forms\CExConcept;
use Ox\Mediboard\System\Forms\CExList;

CCanDo::checkAdmin();

$uid            = preg_replace('/[^\d]/', '', CValue::get("uid"));
$ignore_similar = CView::get('ignore_similar', 'bool default|0');

CView::checkin();

$temp = CAppUI::getTmpPath("ex_class_import");
$file = "$temp/$uid";

$import = new CExClassImport($file);

$map = array(
  "CExConcept" => array(
    "behaviour" => "shared",
    "children"  => "CExCListItem-list_id",
    "fields"    => array(
      "name" => "ask",
    ),
  ),

  "CExList"          => array(
    "behaviour" => "shared",
    "children"  => "CExCListItem-list_id",
    "fields"    => array(
      "name" => "ask",
    ),
  ),
  "CExClassListItem" => array(),

  "CExClass" => array(
    "children" => array(
      "CExClassFieldGroup-ex_class_id",
    ),
    "fields"   => array(
      "group_id" => "ask",
    ),
  ),

  "CExClassFieldGroup" => array(
    "children" => array(
      "CExClassField-ex_group_id",
    ),
    "fields"   => array(
      "group_id" => "ask",
    ),
  ),

  "CExClassField"            => array(
    "children" => array(
      "CExListItem-field_id",
      "CExClassFieldTranslation-ex_class_field_id",
    ),
    "fields"   => array(
      "group_id" => "ask",
    ),
  ),
  "CExClassFieldTranslation" => array(),
);

/** @var DOMElement $ex_class */
$ex_class      = $import->getElementsByClass("CExClass")->item(0);
$ex_class_name = $import->getNamedValueFromElement($ex_class, "name");
$list_elements = $import->getElementsByClass("CExList");

$lists = array();
foreach ($list_elements as $_list_element) {
  $_id       = $_list_element->getAttribute("id");
  $_elements = $import->getElementsByFwdRef("CExListItem", "list_id", $_id);

  $_elements_values = array();
  foreach ($_elements as $_element) {
    $_elements_values[] = CMbXMLObjectImport::getValuesFromElement($_element);
  }

  /** @var CExList[] $_similar */
  $_similar = $import->getSimilarFromElement($_list_element);

  $lists[$_list_element->getAttribute("id")] = array(
    "values"   => CMbXMLObjectImport::getValuesFromElement($_list_element),
    "similar"  => $_similar,
    "elements" => $_elements_values,
  );
}

$sortfunc = function ($a, $b) {
  return strcasecmp($a["values"]["name"], $b["values"]["name"]);
};

uasort($lists, $sortfunc);

$list = new CExList();

/** @var CExList[] $all_lists */
$all_lists        = $list->loadGroupList(null, "name");
$concept_elements = $import->getElementsByClass("CExConcept");

$concepts = array();
foreach ($concept_elements as $_concept_element) {
  $_values = CMbXMLObjectImport::getValuesFromElement($_concept_element);
  $_spec   = explode(" ", $_values["prop"]);

  $concepts[$_concept_element->getAttribute("id")] = array(
    "values"    => CMbXMLObjectImport::getValuesFromElement($_concept_element),
    "similar"   => $import->getSimilarFromElement($_concept_element),
    "spec_type" => $_spec[0],
  );
}

uasort($concepts, $sortfunc);

$concept      = new CExConcept();
$all_concepts = $concept->loadGroupList(null, "name");

$smarty = new CSmartyDP();
$smarty->assign("ex_class_name", $ex_class_name);
$smarty->assign("uid", $uid);
$smarty->assign("concepts", $concepts);
$smarty->assign("all_concepts", $all_concepts);
$smarty->assign("lists", $lists);
$smarty->assign("all_lists", $all_lists);
$smarty->assign("ignore_similar", $ignore_similar);
$smarty->display("inc_import_ex_class.tpl");