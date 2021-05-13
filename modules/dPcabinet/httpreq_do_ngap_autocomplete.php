<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CCodeNGAP;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::check();

$object_class = CView::get("object_class", 'str');
$object_id    = CView::get("object_id", "ref class|$object_class");
$code         = CView::post("code", 'str');
$executant_id = CView::post("executant_id", 'ref class|CMediusers');
$date         = CView::post('date', array('date', 'default' => CMbDT::date()));
$speciality   = CView::post('speciality', 'str');

CView::checkin();
CView::enableSlave();

$ds = CSQLDataSource::get("ccamV2");

// Chargement de l'object
/** @var CCodable $object */
$object = new $object_class;
$object->load($object_id);

// Chargement de ses actes NGAP
$object->countActes();
$object->loadRefsActes();

$praticien = CMediusers::get($executant_id);
if (!$executant_id && $speciality) {
  $praticien = new CMediusers();
  $praticien->spec_cpam_id = $speciality;
}
$spe_undefined = $praticien->spec_cpam_id ? false : true;

$codes = CCodeNGAP::search($code, $praticien, $date, $object->_count_actes ? false : true);

foreach ($codes as $_key => $_code) {
  $_code->getTarifFor($praticien, $date);

  if (!$_code->lettre_cle && !empty($_code->associations)) {
    $authorized_asso = false;
    if (in_array('NGAP', $_code->associations) && count($object->_ref_actes_ngap)) {
      $authorized_asso = true;
    }
    elseif (in_array('CCAM', $_code->associations) && count($object->_ref_actes_ccam)) {
      $authorized_asso = true;
    }
    else {
      foreach ($object->_ref_actes_ngap as $_acte) {
        if (in_array($_acte->code, $_code->associations)) {
          $authorized_asso = true;
          break;
        }
      }
    }

    if (!$authorized_asso) {
      unset($codes[$_key]);
    }
  }
}

// Création du template
$smarty = new CSmartyDP();
$smarty->debugging = false;

$smarty->assign("code"         , $code);
$smarty->assign('codes'        , $codes);
$smarty->assign('spe_undefined', $spe_undefined);
$smarty->assign("nodebug"      , true);

$smarty->display("httpreq_do_ngap_autocomplete.tpl");
