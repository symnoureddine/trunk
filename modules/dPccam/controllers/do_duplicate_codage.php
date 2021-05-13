<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCodageCCAM;

$codage_id = CView::post("codage_id", "ref class|CCodageCCAM");
$actes     = CView::post("actes", "str");
$date      = CView::post("date", "date");

CView::checkin();

$actes = explode("|", $actes);

$codage = new CCodageCCAM();

if ($codage->load($codage_id)) {
  $codage->canDo();

  if (!$codage->_can->edit) {
    CAppUI::accessDenied();
  }

  $codable = $codage->loadCodable();
  $codable->loadExtCodesCCAM();
  $codable->loadRefsActesCCAM();
  $codage->loadActesCCAM();

  /* Compte les actes non codés, triés par code, activité, et phase */
  $uncoded_acts = array();
  $coded_acts = $codable->_ref_actes_ccam;
  foreach ($codable->_ext_codes_ccam as $_code) {
    foreach ($_code->activites as $_activite) {
      foreach ($_activite->phases as $_phase) {
        $coded = false;
        foreach ($coded_acts as $_acte) {
          if ($_acte->code_acte == $_code->code && !$coded) {
            if ($_acte->code_activite == $_activite->numero && $_acte->code_phase == $_phase->phase) {
              $coded = true;
              unset($coded_acts[$_acte->_id]);
            }
          }
        }

        if (!$coded) {
          $key = "$_code->code-$_activite->numero-$_phase->phase";
          if (!array_key_exists($key, $uncoded_acts)) {
            $uncoded_acts[$key] = 1;
          }
          else {
            $uncoded_acts[$key] = $uncoded_acts[$key] + 1;
          }
        }
      }
    }
  }

  $date = CValue::first($date, $codage->_ref_codable->sortie);
  $days = CMbDT::daysRelative($codage->date . ' 00:00:00', CMbDT::format($date, '%Y-%m-%d 00:00:00'));

  for ($i = 1; $i <= $days; $i++) {
    $_date = CMbDT::date("+$i DAYS", $codage->date);

    $_codage = new CCodageCCAM();
    $_codage->praticien_id = $codage->praticien_id;
    $_codage->codable_class = $codage->codable_class;
    $_codage->codable_id = $codage->codable_id;
    $_codage->date = $_date;

    $_codage->loadMatchingObject();

    if ($codage->association_mode == 'user_choice') {
      $_codage->association_mode = $codage->association_mode;
      $_codage->association_rule = $codage->association_rule;
    }

    $_codage->store();

    foreach ($actes as $_acte_id) {
      if (array_key_exists($_acte_id, $codage->_ref_actes_ccam)) {
        $_acte = $codage->_ref_actes_ccam[$_acte_id];

        /* Si il n'y a pas d'acte non coté pour ce code ccam, on l'ajoute au codable */
        $key = "$_acte->code_acte-$_acte->code_activite-$_acte->code_phase";
        if (array_key_exists($key, $uncoded_acts)) {
          $uncoded_acts[$key] = $uncoded_acts[$key] - 1;
          if ($uncoded_acts[$key] == 0) {
            unset($uncoded_acts[$key]);
          }
        }
        else {
          $codable->codes_ccam .= "|$_acte->code_acte";
          $codable->updateFormFields();
          $codable->updateCCAMPlainField();
          $codable->store();
        }

        $_acte->execution = "$_date " . CMbDT::time(null, $_acte->execution);
        $_acte->_ref_object = $codable;
        $_acte->_id = null;
        $_acte->store();
      }
    }
  }
}