<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Ccam\CFavoriCCAM;
use Ox\Mediboard\SalleOp\CActeCCAM;

/**
 * dPccam
 */
CCanDo::checkRead();

$_filter_class = CValue::get("_filter_class");
$tag_id       = CValue::get("tag_id");

$list = array();

$user = CUser::get();

if (!$tag_id) {
  $actes = new CActeCCAM();
  $codes = $actes->getFavoris($user->_id, $_filter_class);
  $i = 0;

  foreach ($codes as $value) {
    $code = CDatedCodeCCAM::get($value["code_acte"]);
    $code->getChaps();

    $code->favoris_id = 0;
    $code->occ = $value["nb_acte"];
    $code->class = $value["object_class"];

    $chapitre =& $code->chapitres[0];
    $list[$chapitre["code"]]["nom"] = $chapitre["nom"];
    $list[$chapitre["code"]]["codes"][$value["code_acte"]]= $code;
  }
}

$fusion = $list;

$codesByChap = CFavoriCCAM::getOrdered($user->_id, $_filter_class, true, $tag_id);

//Fusion des deux tableaux
foreach ($codesByChap as $keychapter => $chapter) {
  if (!array_key_exists($keychapter, $fusion)) {
    $fusion[$keychapter] = $chapter;
    continue;
  }
  
  foreach ($chapter["codes"] as $keycode => $code) {
    if (!array_key_exists($keycode, $fusion[$keychapter]["codes"])) {
      $fusion[$keychapter]["codes"][$keycode] = $code;
      continue;
    }
    // Référence vers le favoris pour l'ajout de tags
    $fusion[$keychapter]["codes"][$keycode]->favoris_id = $code->favoris_id;
    $fusion[$keychapter]["codes"][$keycode]->_ref_favori = $code->_ref_favori;
  }
}

$tag_tree = CFavoriCCAM::getTree($user->_id);

$favoris = new CFavoriCCAM();
$favoris->_filter_class = $_filter_class;

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("favoris", $favoris);
$smarty->assign("list", $list);
$smarty->assign("fusion", $fusion);
$smarty->assign("codesByChap", $codesByChap);
$smarty->assign("tag_tree", $tag_tree);
$smarty->assign("tag_id",   $tag_id);
$smarty->display("vw_idx_favoris.tpl");
