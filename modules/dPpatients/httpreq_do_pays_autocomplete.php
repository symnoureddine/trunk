<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;

CCanDo::checkRead();

$field_pays       = CView::get("fieldpays", "str");
$pays             = $field_pays ? CView::get($field_pays, "str") : "";
$fieldnumericpays = CView::get("fieldnumericpays", "str");
$numPays          = $fieldnumericpays ? CView::get($fieldnumericpays, "numchar") : "";

CView::checkin();
CView::enableSlave();

$ds    = CSQLDataSource::get("INSEE");
$query = null;

if ($pays) {
  $query = "SELECT numerique, nom_fr FROM pays
            WHERE nom_fr LIKE '$pays%'
            ORDER BY nom_fr, numerique";
}

if ($numPays) {
  $query = "SELECT numerique, nom_fr FROM pays
            WHERE numerique LIKE '%$numPays%'
            ORDER BY nom_fr, numerique";
}

if (!$query) {
  return;
}

$result = $ds->loadList($query, 30);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("pays", $pays);
$smarty->assign("numPays", $numPays);
$smarty->assign("result", $result);
$smarty->assign("nodebug", true);

$smarty->display("httpreq_do_pays_autocomplete.tpl");
