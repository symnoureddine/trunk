<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Ccam\CFavoriCCAM;

/**
 * dPccam
 */
$codeacte     = CValue::get("codeacte");
$object_class = CValue::get("object_class");

$code = CDatedCodeCCAM::get($codeacte);
$favoris = new CFavoriCCAM();

// Variable permettant de savoir si l'affichage du code complet est necessaire
$codeComplet = false;
$codeacte = $code->code;

if ($code->_activite != "") {
  $codeComplet = true;
  $codeacte .= "-$code->_activite";  
  if ($code->_phase != "") {
    $codeacte .= "-$code->_phase";
  }
}

$smarty = new CSmartyDP;

$smarty->assign("code", $code);
$smarty->assign("favoris", $favoris);
$smarty->assign("object_class", $object_class);
$smarty->display("inc_vw_detail_ccam.tpl");
