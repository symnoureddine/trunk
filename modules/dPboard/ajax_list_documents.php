<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$chir_id = CView::get("chir_id", "ref class|CMediusers");

CView::checkin();

$cr = new CCompteRendu();

$user = CMediusers::get($chir_id);

$where = array(
  "signataire_id"       => $user->getUserSQLClause(),
  "signature_mandatory" => "= '1'",
);

$where[] = "valide = '0' OR valide IS NULL";

$crs = $cr->loadList($where);

CStoredObject::massLoadFwdRef($crs, "object_id");

$affichageDocs = array();

/** @var CCompteRendu $_cr */
foreach ($crs as $_cr) {
  $context = $_cr->loadTargetObject();
  switch ($context->_class) {
    default:
      $context_cancelled = false;
      break;
    case "CConsultation":
    case "CSejour":
      $context_cancelled = $context->annule;
      break;
    case "CConsultAnesth":
      $context_cancelled = $context->loadRefConsultation()->annule;
      break;
    case "COperation":
      $context_cancelled = $context->annulee;
  }

  if ($_cr->isAutoLock() || $context_cancelled) {
    unset($crs[$_cr->_id]);
    continue;
  }

  $_cr->_ref_patient = $_cr->getIndexablePatient();

  $cat_id = $_cr->file_category_id ? : 0;
  $affichageDocs[$cat_id]["items"][$_cr->nom . "-$_cr->_guid"] = $_cr;
  if (!isset($affichageDocs[$cat_id]["name"])) {
    $affichageDocs[$cat_id]["name"] = $cat_id ? $_cr->_ref_category->nom : CAppUI::tr("CFilesCategory.none");
  }
}

foreach($affichageDocs as $categorie => $docs) {
  CMbArray::pluckSort($affichageDocs[$categorie]['items'], SORT_DESC, "creation_date");
}

$smarty = new CSmartyDP();

$smarty->assign("affichageDocs", $affichageDocs);
$smarty->assign("crs", $crs);

$smarty->display("inc_list_documents");