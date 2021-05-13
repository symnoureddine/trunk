<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CCodageCCAM;

$codage_id = CValue::get('codage_id', 0);
$acte_id   = CValue::get('acte_id', 0);

$codage = new CCodageCCAM();
$codage->load($codage_id);

if ($codage->_id) {
  $codage->canDo();
  if (!$codage->_can->edit) {
    CAppUI::accessDenied();
  }

  $codage->loadCodable();
  $codage->loadPraticien()->loadRefFunction();
  $codage->_ref_praticien->isAnesth();
  $codage->loadActesCCAM();
  $codage->checkRules();
  // Chargement du codable et des actes possibles
  $codage->loadCodable();

  foreach ($codage->_ref_actes_ccam as $_acte) {
    $_acte->getTarif();
    $_activite = $_acte->_ref_code_ccam->activites[$_acte->code_activite];
    $_phase = $_activite->phases[$_acte->code_phase];

    /* Verification des modificateurs codés */
    if (property_exists($_phase, '_modificateurs')) {
      foreach ($_phase->_modificateurs as $modificateur) {
        $position = strpos($_acte->modificateurs, $modificateur->code);
        if ($position !== false) {
          if ($modificateur->_double == "1") {
            $modificateur->_checked = $modificateur->code;
          }
          elseif ($modificateur->_double == "2") {
            $modificateur->_checked = $modificateur->code . $modificateur->_double;
          }
          else {
            $modificateur->_checked = null;
          }
        }
        else {
          $modificateur->_checked = null;
        }
      }
    }

    CCodageCCAM::precodeModifiers($_phase->_modificateurs, $_acte, $codage->_ref_codable);
  }

  $smarty = new CSmartyDP();
  $smarty->assign('codage', $codage);
  $smarty->assign('acte_id', $acte_id);
  $smarty->display('inc_duplicate_codage.tpl');
}