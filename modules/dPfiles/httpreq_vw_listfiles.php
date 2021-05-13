<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\AppFine\Client\CAppFineClient;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::check();

$object_class   = CView::get("selClass", "str", true);
$object_id      = CView::get("selKey", "ref class|$object_class", true);
$typeVue        = CView::get("typeVue" , "bool default|0", true);
$accordDossier  = CView::get("accordDossier", "bool default|0");
$category_id    = CView::get("category_id", "ref class|CFilesCategory");
$order_docitems = CView::get("order_docitems", "enum list|nom|date default|nom", true);

$only_list     = $category_id === 0 || $category_id > 0;

CView::checkin();

if (!$object_class && !$object_id) {
  CAppUI::stepMessage(UI_MSG_ERROR, "Problème de récupération de la liste des fichiers");
  CApp::rip();
}

// Liste des classes
$listCategory = CFilesCategory::listCatClass($object_class);

// Chargement de l'utilisateur courant
$mediuser = CMediusers::get();

$object  = null;
$canFile = false;
$canDoc  = false;
$praticienId = null;
$affichageFile = array();
$nbItems = 0;

// Chargement de l'objet
/** @var CMbObject $object */
$object = new $object_class;
$object->load($object_id);

if ($object instanceof CSejour || $object instanceof COperation || $object instanceof CConsultation) {
  CAccessMedicalData::logAccess($object);
}

$file = new CFile();
$canFile  = $file->canCreate($object);
$cr = new CCompteRendu();
$canDoc  = $cr->canCreate($object);

// To add the modele selector in the toolbar
switch ($object_class) {
  case "CConsultation":
    $object->loadRefPlageConsult();
    $praticienId = $object->_praticien_id;
    break;
  case "CConsultAnesth":
    $object->_ref_consultation->loadRefPlageConsult();
    $praticienId = $object->_ref_consultation->_praticien_id;
    break;
  case "CSejour":
    $praticienId = $object->praticien_id;
    break;
  case "COperation":
    $praticienId = $object->chir_id;
    break;
  default:
    if ($mediuser->isPraticien()) {
      $praticienId = $mediuser->_id;
    }
}

$affichageFile = CDocumentItem::loadDocItemsByObject($object, $order_docitems);

$appfine_active = CModule::getActive("appFineClient");

foreach ($affichageFile as $_cat) {
  if (!isset($_cat["items"])) {
    break;
  }

  foreach ($_cat["items"] as $_item) {
    /** @var CDocumentItem $_item */
    if (!$_item->annule) {
      $nbItems++;
    }
    $category = $_item->_ref_category;
    $category->countReadFiles();
    $_item->canDo();

    $_item->countSynchronizedRecipients();

    if ($appfine_active) {
      if ($_item instanceof CFile) {
        CAppFineClient::loadBackRefOrderItem($_item);
      }
    }
  }
}

if ($appfine_active) {
  CAppFineClient::loadIdex($object);
}

$prefixe = $typeVue ? "_colonne" : "";

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("canFile"       , $canFile);
$smarty->assign("canDoc"        , $canDoc);
$smarty->assign("listCategory"  , $listCategory);
$smarty->assign("praticienId"   , $praticienId);
$smarty->assign("object"        , $object);
$smarty->assign("nbItems"       , $nbItems);
$smarty->assign("typeVue"       , $typeVue);
$smarty->assign("accordDossier" , $accordDossier);
$smarty->assign("affichageFile" , $affichageFile);
$smarty->assign("order_docitems", $order_docitems);

if ($only_list) {
  $smarty->assign("category_id", $category_id ? : 0);
  $smarty->assign("list", $affichageFile[$category_id ? : 0]["items"]);
  $smarty->display("inc_list_files$prefixe.tpl");
}
else {
  $smarty->display("inc_list_view$prefixe.tpl");
}
