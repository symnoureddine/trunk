<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CTarif;

CCanDo::checkEdit();

$tarif_id = CView::get("tarif_id", "ref class|CTarif", true);
CView::checkin();

$tarif = CTarif::findOrFail($tarif_id);
$tarif->loadActes();

$tab = array(
  "tarmed" => "_ref_tarmed",
  "caisse" => "_ref_prestation_caisse"
);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("tarif", $tarif);
$smarty->assign("tab",   $tab);

$smarty->display("vw_codes_tarif.tpl");
