<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CMbXMLDocument;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;

CCanDo::checkRead();

$owner_guid = CView::post("owner_guid", "str");

CView::checkin();

$owner = $owner_guid === "Instance" ? CCompteRendu::getInstanceObject() : CMbObject::loadFromGuid($owner_guid);

if (!$owner || !$owner->_id) {
  CAppUI::stepMessage(UI_MSG_WARNING, "Le propriétaire souhaité n'existe pas.");
}

$user_id     = "";
$function_id = "";
$group_id    = "";

switch ($owner->_class) {
  case "CMediusers":
    $user_id = $owner->_id;
    break;
  case "CFunctions";
    $function_id = $owner->_id;
    break;
  case "CGroups":
    $group_id = $owner->_id;
    break;
  default:
    // No owner
    break;
}

$file = $_FILES["datafile"];

if (strtolower(pathinfo($file["name"] , PATHINFO_EXTENSION) !== "xml")) {
  CAppUI::stepAjax("Fichier non reconnu", UI_MSG_ERROR);
  CApp::rip();
}

$doc = file_get_contents($file["tmp_name"]);

$xml = new CMbXMLDocument(null);
$xml->loadXML($doc);

$root = $xml->firstChild;

if ($root->nodeName == "modeles") {
  $root = $root->childNodes;
}
else {
  $root = array($xml->firstChild);
}

$modeles_ids = array();

CCompteRendu::$import = true;

foreach ($root as $_modele) {
  $modele = CCompteRendu::importModele($_modele, $user_id, $function_id, $group_id, $modeles_ids);

  CAppUI::stepAjax($modele->nom . " - " . CAppUI::tr("CCompteRendu-msg-create"), UI_MSG_OK);
}

CCompteRendu::$import = false;

CAppUI::js("window.opener.getForm('filterModeles').onsubmit()");
