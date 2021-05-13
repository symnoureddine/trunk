<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;

CCanDo::checkRead();

$page          = intval(CValue::get('page', 0));
$step          = 22;
$keywords      = CValue::get('keywords');
$code          = CValue::get('code');
$date_demandee = CValue::get('date_demandee');
$result_chap1  = CValue::get('result_chap1');
$result_chap2  = CValue::get('result_chap2');
$result_chap3  = CValue::get('result_chap3');
$result_chap4  = CValue::get('result_chap4');

if ($date_demandee) {
  $date_version = CDatedCodeCCAM::mapDateToDash($date_demandee);
}

$smarty = new CSmartyDP();
if (!$code && $date_demandee) {
  $smarty->assign("no_code_for_date", "CDatedCodeCCAM-msg-No code for date");
  $smarty->display("inc_result_search_acte.tpl");
  CApp::rip();
}

if (!$code && !$keywords && !$result_chap1 && !$date_demandee) {
  $smarty->assign("no_filter", "CDatedCodeCCAM-msg-No filter");
  $smarty->display("inc_result_search_acte.tpl");
  CApp::rip();
}

if (!$date_demandee) {
  $query = "SELECT CODE
    FROM p_acte
    WHERE DATEFIN = '00000000' ";

  if ($code) {
    $query .= " AND CODE LIKE '" . addslashes($code) . "%'";
  }

  if ($keywords) {
    $list_keyword = explode(" ", $keywords);
    foreach ($list_keyword as $key => $value) {
      $query .= " AND (LIBELLELONG LIKE '%" . addslashes($value) . "%')";
    }
  }

  if ($result_chap4) {
    $query .= " AND ARBORESCENCE4 = '0000".$result_chap4."'";
  }
  if ($result_chap3) {
    $query .= " AND ARBORESCENCE3 = '0000".$result_chap3."'";
  }
  if ($result_chap2) {
    $query .= " AND ARBORESCENCE2 = '0000".$result_chap2."'";
  }
  if ($result_chap1) {
    $query .= " AND ARBORESCENCE1 = '0000".$result_chap1."'";
  }

  $ds     = CSQLDataSource::get("ccamV2");
  $total  = $ds->countRows($query);
  $result = $ds->exec($query);

  $query .= " ORDER BY CODE LIMIT $page , $step";

  $result = $ds->exec($query);
  $codes  = array();
  while ($row = $ds->fetchArray($result)) {
    $code                                = CDatedCodeCCAM::get($row["CODE"]);
    $code->_ref_code_ccam->date_creation = CDatedCodeCCAM::mapDateFrom($code->_ref_code_ccam->date_creation);
    foreach ($code->_ref_code_ccam->_ref_infotarif as $_infotarif) {
      $_infotarif->date_effet = CDatedCodeCCAM::mapDateFrom($_infotarif->date_effet);
    }
    $codes[] = $code;
  }
}
else {
  if ($code && $date_demandee) {
    $query = "SELECT CODEACTE
    FROM p_phase_acte
    WHERE DATEEFFET >= '".$date_version."'
    AND CODEACTE = '".$code."'";

    $query .= " ORDER BY CODEACTE LIMIT 0 , 1";

    $ds     = CSQLDataSource::get("ccamV2");
    $total  = $ds->countRows($query);
    $result = $ds->exec($query);

    $result = $ds->exec($query);
    $codes  = array();
    while ($row = $ds->fetchArray($result)) {
      $code                                = CDatedCodeCCAM::get($row["CODEACTE"]);
      $code->_ref_code_ccam->date_creation = CDatedCodeCCAM::mapDateFrom($code->_ref_code_ccam->date_creation);
      foreach ($code->_ref_code_ccam->_ref_infotarif as $_infotarif) {
        $_infotarif->date_effet = CDatedCodeCCAM::mapDateFrom($_infotarif->date_effet);
      }
      $codes[] = $code;
    }
  }
}

$smarty->assign("keywords_multiple", $keywords);
$smarty->assign("codes", $codes);
$smarty->assign("nbResultat", $total);
$smarty->assign("page", $page);
$smarty->assign("step", $step);
$smarty->assign("date_demandee", $date_demandee);
$smarty->display("inc_result_search_acte.tpl");