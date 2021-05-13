<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Facturation\CReglement;
use Ox\Mediboard\Facturation\CFactureEtablissement;

CCanDo::checkEdit();
$file   = CValue::files("import");
$dryrun = CValue::post("dryrun");

$facture_class = CValue::post("facture_class");
if (!$facture_class) {
  $facture_class = CValue::get("facture_class");
}

$results  = array();
$totaux   = array(
  "impute"  =>  array("count" => 0, "total" => 0.00, "dates" => array()),
  "rejete"  =>  array("count" => 0, "total" => 0.00),
  "total"   =>  array("count" => 0, "total" => 0.00)
);
$i = 0;

if ($file && ($fp = fopen($file['tmp_name'], 'r'))) {
  // Each line
  while ($line = fgetcsv($fp, null, ";")) {
    $i++;

    // Skip empty lines
    if (!isset($line[0]) || $line[0] == "") {
      continue;
    }

    // Parsing
    $line = array_map("trim"      , $line);
    if (strlen($line[0]) < 100) {
      continue;
    }
    $line = array_map("addslashes", $line);
    $line = $line[0];
    $results[$i]["genre"]           = substr($line, 0, 3);
    $results[$i]["num_client"]      = substr($line, 3, 9);
    $results[$i]["reference"]       = substr($line, 12, 27);
    $results[$i]["montant"]         = substr($line, 39, 10);
    $results[$i]["ref_depot"]       = substr($line, 49, 10);
    $results[$i]["date_depot"]      = substr($line, 59, 6);
    $results[$i]["date_traitement"] = substr($line, 65, 6);
    $results[$i]["date_inscription"]= substr($line, 71, 6);
    $results[$i]["num_microfilm"]   = substr($line, 77, 9);
    $results[$i]["code_rejet"]      = substr($line, 86, 1);
    $results[$i]["reserve"]         = substr($line, 87, 9);
    $results[$i]["prix"]            = substr($line, 96, 4);

    $results[$i]["errors"] = array();
    $results[$i]["warning"] = array();


    if (!$results[$i]["reference"]) {
      $results[$i]["errors"][] = CAppUI::tr("CFacture.no_reference");
    }
    else {
      /* @var CFactureEtablissement $facture*/
      $facture = $facture_class::findFacture($results[$i]["reference"]);

      if (!$facture->_id) {
        //Facture introuvable
        $results[$i]["errors"][] = CAppUI::tr("CFacture.no_identity_reference");
      }
      $facture->loadRefPatient();
      $facture->loadRefsObjects();
      $facture->loadRefsReglements();
      $facture->loadRefsRelances();

      $reglement = new CReglement();
      $reglement->mode = "BVR";
      $reglement->object_id    = $facture->_id;
      $reglement->object_class = $facture->_class;
      $reglement->reference    = $results[$i]["reference"];
      $reglement->emetteur     = "patient";
      $date = $results[$i]["date_depot"];
      $reglement->date         = "20".substr($date, 0, 2)."-".substr($date, 2, 2)."-".substr($date, 4, 2)." 00:00:00";
      $results[$i]["date_depot"] = CMbDT::date($reglement->date);
      $date = $results[$i]["date_traitement"];
      $results[$i]["date_traitement"] = CMbDT::date("20".substr($date, 0, 2)."-".substr($date, 2, 2)."-".substr($date, 4, 2));
      $date = $results[$i]["date_inscription"];
      $results[$i]["date_inscription"] = CMbDT::date("20".substr($date, 0, 2)."-".substr($date, 2, 2)."-".substr($date, 4, 2));

      $montant = $results[$i]["montant"];
      $results[$i]["montant"] = sprintf("%.2f", substr($montant, 0, 8).".".substr($montant, 8, 2));
      $reglement->montant      = $results[$i]["montant"];
      // Field check final
      if ($reglement->montant == "") {
        $results[$i]["errors"][] = CAppUI::tr("CFacture.no_montant");
      }
      if ($reglement->date == "") {
        $results[$i]["errors"][] = CAppUI::tr("CFacture.no_date_depot");
      }

      if ($facture->_id && ($facture->patient_date_reglement || ($facture->_du_restant_patient-$reglement->montant) < 0)) {
        $type_error = CAppUI::gconf("dPfacturation Other autorise_excess_amount") ? "warning" : "errors";
        $results[$i][$type_error][] = CAppUI::tr("CFacture.no_identity_reference");
      }

      $results[$i]["facture"] = $facture;

      $totaux["total"]["count"] ++;
      $totaux["total"]["total"] += $reglement->montant;

      // No store on errors
      if (count($results[$i]["errors"])) {
        $totaux["rejete"]["count"] ++;
        $totaux["rejete"]["total"] += $reglement->montant;
        continue;
      }
      else {
        $totaux["impute"]["count"] ++;
        $totaux["impute"]["total"] += $reglement->montant;

        if (!isset($totaux["impute"]["dates"]["$reglement->date"])) {
          $totaux["impute"]["dates"]["$reglement->date"] = array("count" => 0, "total" => 0.00);
        }
        $totaux["impute"]["dates"]["$reglement->date"]["count"] ++;
        $totaux["impute"]["dates"]["$reglement->date"]["total"] += $reglement->montant;
      }

      if (($facture->_du_restant_patient-$reglement->montant) >0) {
        $results[$i]["warning"][] = CAppUI::tr("CFacture.paiement_no_total");
      }

      // Dry run to check references
      if ($dryrun) {
        continue;
      }

      // Creation
      $existing = $reglement->_id;
      $reglement->_acquittement_date = CMbDT::date($reglement->date);
      if ($msg = $reglement->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
        $results[$i]["errors"][] = $msg;
        continue;
      }

      CAppUI::setMsg($existing ? "CReglement-msg-modify" : "CReglement-msg-create", UI_MSG_OK);
    }
  }

  fclose($fp);
}

CAppUI::callbackAjax('$("systemMsg").insert', CAppUI::getMsg());

// Génération du csv
$result_csv = new CCSVFile();
$result_csv->writeLine(
  array(
    CAppUI::tr("compta-transmission"),
    CAppUI::tr("compta-num_adherent"),
    CAppUI::tr("CFacture-type_sejour"),
    CAppUI::tr("CFacture"),
    CAppUI::tr("CDebiteur"),
    CAppUI::tr("CFacture-montant"),
    CAppUI::tr("compta-reference"),
    CAppUI::tr("compta-date_depot"),
    CAppUI::tr("compta-date_traitement"),
    CAppUI::tr("compta-date_valeur"),
    CAppUI::tr("compta-rejet"),
    "R",
    CAppUI::tr("compta-microfilm"),
    CAppUI::tr("compta-erreur"),
  )
);
foreach ($results as $_result) {
  $_facture = $_result["facture"];
  $_error = "";
  foreach (array("errors", "warning") as $_error_type) {
    if (isset($_result[$_error_type]) && count($_result[$_error_type]) > 0) {
      $_error .= ($_error !== "" ? "," : "") . implode(",", $_result[$_error_type]) ;
    }
  }
  $result_csv->writeLine(
    array(
      $_result["genre"],
      $_result["num_client"],
      $_facture->_class === "CFactureEtablissement" ? $_facture->_ref_last_sejour->_id : "",
      $_facture,
      $_facture->_ref_patient,
      $_result["montant"],
      $_result["reference"],
      CMbDT::format($_result["date_depot"], CAppUI::conf("date")),
      CMbDT::format($_result["date_traitement"], CAppUI::conf("date")),
      CMbDT::format($_result["date_inscription"], CAppUI::conf("date")),
      $_result["code_rejet"],
      count($_facture->_ref_relances),
      $_result["num_microfilm"],
      $_error
    )
  );
}
$result_csv = $result_csv->getContent();

$totaux_csv = new CCSVFile();
$totaux_csv->writeLine(
  array(
    CAppUI::tr("Date"),
    CAppUI::tr("compta-recordings"),
    CAppUI::tr("CFacture-montant"),
  )
);

foreach ($totaux["impute"]["dates"] as $_date=>$_ligne) {
  $totaux_csv->writeLine(
    array(
      CMbDT::format($_date, CAppUI::conf("date")),
      $_ligne["count"],
      $_ligne["total"]
    )
  );
}
$totaux_csv->writeLine(
  array(
    CAppUI::tr("compta-total_impute"),
    $totaux["impute"]["count"],
    $totaux["impute"]["total"]
  )
);
$totaux_csv->writeLine(
  array(
    CAppUI::tr("compta-total_rejet"),
    $totaux["rejete"]["count"],
    $totaux["rejete"]["total"]
  )
);
$totaux_csv->writeLine(
  array(
    CAppUI::tr("compta-total_ptt"),
    $totaux["total"]["count"],
    $totaux["total"]["total"]
  )
);
$totaux_csv = $totaux_csv->getContent();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("results"      , $results);
$smarty->assign("totaux"       , $totaux);
$smarty->assign("facture_class", $facture_class);
$smarty->assign("result_csv"   , $result_csv);
$smarty->assign("totaux_csv"   , $totaux_csv);

$smarty->display("vw_rapprochement_banc");
