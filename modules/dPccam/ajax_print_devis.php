<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CDevisCodage;
use Ox\Mediboard\CompteRendu\CCompteRendu;

$devis_id = CValue::get('devis_id');

$devis = new CDevisCodage();
$devis->load($devis_id);

if ($devis->_id) {
  $devis->updateFormFields();
  $devis->loadRefPatient();
  $devis->loadRefCodable();
  $devis->loadRefPraticien();
  $devis->_ref_praticien->loadRefFunction();
  $devis->getActeExecution();
  $devis->countActes();
  $devis->loadRefsActes();
  $devis->loadRefsFraisDivers();

  foreach ($devis->_ref_actes_ccam as $_acte) {
    $_acte->getTarif();
  }
  foreach($devis->_ref_frais_divers as $_frais) {
    $_frais->loadRefType();
  }


  $model = CCompteRendu::getSpecialModel($devis->_ref_praticien, $devis->_class, '[DEVIS]');

  if ($model->_id) {
    CCompteRendu::streamDocForObject($model, $devis);
  }

  $smarty = new CSmartyDP();
  $smarty->assign('devis', $devis);
  $smarty->display('print_devis_codage.tpl');
}