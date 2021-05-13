<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CTarif;
use Ox\Mediboard\Ccam\CDentCCAM;
use Ox\Mediboard\Ccam\CDevisCodage;
use Ox\Mediboard\Mediusers\CMediusers;

$devis_id = CValue::get('devis_id');
$action   = CValue::get('action', 'open');


$devis = new CDevisCodage();

if ($devis_id) {
  $devis->load($devis_id);
  $devis->loadRefCodable();
}

if ($devis->_id) {
  $devis->canDo();
  $devis->loadRefPatient();
  $devis->loadRefPraticien();
  $devis->getActeExecution();
  $devis->countActes();
  $devis->loadRefsActes();

  foreach ($devis->_ref_actes as $_acte) {
    $_acte->loadRefExecutant();
  }

  $devis->loadExtCodesCCAM();
  $devis->loadPossibleActes();

  // Chargement des règles de codage
  $devis->loadRefsCodagesCCAM();
  foreach ($devis->_ref_codages_ccam as $_codages_by_prat) {
    foreach ($_codages_by_prat as $_codage) {
      $_codage->loadPraticien()->loadRefFunction();
      $_codage->loadActesCCAM();
      $_codage->getTarifTotal();
      foreach ($_codage->_ref_actes_ccam as $_acte) {
        $_acte->getTarif();
      }
    }
  }
}

// Chargement des praticiens
$listAnesths = new CMediusers;
$listAnesths = $listAnesths->loadAnesthesistes(PERM_EDIT);

$listChirs = CConsultation::loadPraticiens(PERM_EDIT);

//Initialisation d'un acte NGAP
$acte_ngap = CActeNGAP::createEmptyFor($devis);
// Liste des dents CCAM
$dents = CDentCCAM::loadList();
$liste_dents = reset($dents);

$user = CMediusers::get();
$user->isPraticien();
$user->isProfessionnelDeSante();

$tarifs = CTarif::loadTarifsUser($devis->_ref_praticien);

$smarty = new CSmartyDP();
$smarty->assign("devis"         , $devis);
$smarty->assign("acte_ngap"     , $acte_ngap);
$smarty->assign("liste_dents"   , $liste_dents);
$smarty->assign("listAnesths"   , $listAnesths);
$smarty->assign("listChirs"     , $listChirs);
$smarty->assign("user"          , $user);
$smarty->assign("tarifs"        , $tarifs);

if ($action == "open") {
  $smarty->display("inc_edit_devis_container.tpl");
}
else {
  $smarty->display("inc_edit_devis.tpl");
}
