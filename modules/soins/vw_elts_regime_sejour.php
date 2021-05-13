<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Prescription\CPrescription;

CCanDo::checkRead();
/* @var CPrescription $prescription */
$prescription = mbGetObjectFromGet('object_class', 'object_id', 'object_guid');
$a_jeun       = CView::get('a_jeun', 'bool default|0');
CView::checkin();

if ($a_jeun) {
  $elts_colonne_regime = CAppUI::gconf("soins UserSejour elts_colonne_jeun");
}
else {
  $elts_colonne_regime = CAppUI::gconf("soins UserSejour elts_colonne_regime");
}
$id_elts_regime = array();
foreach (explode("|", $elts_colonne_regime) as $_elt_regime) {
  $explode_elt_regime = explode(":", $_elt_regime);
  $id_elts_regime[]   = $explode_elt_regime[0];
}

$prescription->_ref_object->loadRefPatient();
if ($a_jeun) {
  $prescription->loadRefsLinesJeun($id_elts_regime);
}
else {
  $prescription->loadRefsLinesRegime($id_elts_regime);
}

$praticien = new CMediusers();

$smarty = new CSmartyDP();

$smarty->assign("prescription", $prescription);
$smarty->assign("praticien", $praticien);

$smarty->display("vw_elts_regime_sejour");
