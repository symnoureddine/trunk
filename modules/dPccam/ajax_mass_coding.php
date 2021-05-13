<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CModelCodage;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$objects_guid    = CView::post("objects_guid", "str");
$chir_id         = CView::post("chir_id", "ref class|CMediusers");
$libelle         = CView::post("libelle", "str");
$protocole_id    = CView::post("protocole_id", "ref class|CProtocole");
$model_codage_id = CView::post("model_codage_id", "ref class|CModelCodage");
$object_class    = CView::post('object_class', 'enum list|COperation|CSejour-seances');

CView::checkin();

$chir = CMediusers::get($chir_id);
$listChirs = $chir->loadPraticiens(PERM_DENY);

$codable = new CCodable();
$model_codage = new CModelCodage();

if (!$model_codage->load($model_codage_id)) {
  $objects_guid_arr = explode("|", $objects_guid);
  if (count($objects_guid_arr) != 0) {
    /** @var CCodable $object */
    $object = CMbObject::loadFromGuid($objects_guid_arr[0]);
    $model_codage->libelle      = $libelle;
    $model_codage->praticien_id = $chir->_id;
    $model_codage->objects_guid = $objects_guid;
    $model_codage->setFromObject($object, $protocole_id);
  }
}

$model_codage->_objects_count = count(explode("|", $model_codage->objects_guid));
$model_codage->loadRefsCodagesCCAM();

if (array_key_exists($chir_id, $model_codage->_ref_codages_ccam)) {
  $codages = $model_codage->_ref_codages_ccam[$chir_id];
  foreach ($codages as $_codage) {
    $_codage->loadPraticien()->loadRefFunction();
    $_codage->_ref_praticien->isAnesth();
    $_codage->loadActesCCAM();
    $_codage->getTarifTotal();
    $_codage->checkRules();

    foreach ($_codage->_ref_actes_ccam as $_acte) {
      $_acte->getTarif();
    }

    // Chargement du codable et des actes possibles
    $_codage->loadCodable();
    $codable   = $_codage->_ref_codable;
    $praticien = $_codage->_ref_praticien;
  }
}

$model_codage->loadExtCodesCCAM();
$model_codage->loadRefsActesCCAM();
$praticien = $model_codage->loadRefPraticien();
$praticien->loadRefFunction();
$praticien->isAnesth();
$model_codage->getActeExecution();
$model_codage->loadPossibleActes($chir_id);

$smarty = new CSmartyDP();
$smarty->assign("subject"  , $model_codage);
$smarty->assign("codages"  , $codages);
$smarty->assign("praticien", $praticien);
$smarty->assign('object_class', $object_class);
$smarty->assign('acte_ngap', CActeNGAP::createEmptyFor($model_codage));
$smarty->display("inc_mass_coding.tpl");