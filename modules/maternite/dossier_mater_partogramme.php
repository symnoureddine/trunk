<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Maternite\CGrossesse;

CCanDo::checkRead();
$grossesse_id       = CView::get("grossesse_id", "ref class|CGrossesse");
$operation_id       = CView::get("operation_id", "ref class|COperation");
$print              = CView::get("print", "bool default|0");
$isDossierPerinatal = CView::get("isDossierPerinatal", "bool default|0");
CView::checkin();

$grossesse = new CGrossesse();
$grossesse->load($grossesse_id);
$grossesse->loadRefDossierPerinat();
$grossesse->loadRefParturiente();

if (!$operation_id) {
  $grossesse->loadLastSejour(["annule" => "= '0'"]);

  if ($grossesse->_ref_last_sejour) {
    $grossesse->_ref_last_sejour->loadRefsOperations();
    if ($grossesse->_ref_last_sejour->_ref_last_operation->_id) {
      $operation_id = $grossesse->_ref_last_sejour->_ref_last_operation->_id;
    }
  }
}

if (!$operation_id) {
  CAppUI::stepAjax("Aucune intervention n'est reliée à la grossesse !");

  return;
}

CAccessMedicalData::logAccess("COperation-$operation_id");

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("grossesse"         , $grossesse);
$smarty->assign("operation_id"      , $operation_id);
$smarty->assign("print"             , $print);
$smarty->assign("isDossierPerinatal", $isDossierPerinatal);
$smarty->display("inc_dossier_mater_partogramme");
