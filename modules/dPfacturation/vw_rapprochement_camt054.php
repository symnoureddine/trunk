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
use Ox\Core\CMbXMLDocument;
use Ox\Core\CMbXPath;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Facturation\CReglement;
use Ox\Mediboard\Facturation\CFactureEtablissement;

CCanDo::checkEdit();
$file   = CValue::files("import");
$dryrun = CValue::post("dryrun");
$facture_class = CValue::get("facture_class");
if (!$facture_class) {
  $facture_class = CView::post("facture_class", "enum list|CFactureCabinet|CFactureEtablissement");
}
CView::checkin();

$results  = array();
$totaux   = array(
  "impute"  =>  array("count" => 0, "total" => 0.00, "dates" => array()),
  "rejete"  =>  array("count" => 0, "total" => 0.00),
  "total"   =>  array("count" => 0, "total" => 0.00)
);

$valid = false;
$xml = false;
$format_invalid = false;
if ($file) {
  $xml = file_get_contents($file['tmp_name']);
}
$document = new CMbXMLDocument('UTF-8');
if ($xml) {
  $document->loadXML(utf8_encode($xml));
  // Vérification de la bonne forme du fichier xml importé
  $document->setSchema("modules/dPfacturation/xml/camt.054.001.04.xsd");
  $valid = $document->schemaValidate(null);
}
$format_invalid = !$valid && $file;

$i = 0;
if ($valid) {
  $type_error = CAppUI::gconf("dPfacturation Other autorise_excess_amount") ? "warning" : "errors";
  // Each line
  $reglements = $document->getElementsByTagName('Ntry');
  $i = 0;
  foreach ($reglements as $_reglement) {
    $_reglement = $reglements->item($i);
    $i++;

    $type_credit = $_reglement->getElementsByTagName("CdtDbtInd")->item(0)->nodeValue;
    if ($type_credit != "CRDT") {
      continue;
    }
    $montant = $_reglement->getElementsByTagName("Amt")->item(0)->nodeValue;
    $montant = sprintf("%.2f", substr($montant, 0, 8).".".substr($montant, 8, 2));

    $reglement = new CReglement();
    $reglement->mode = "BVR";
    $reglement->montant = $montant;
    // Field check final
    if ($reglement->montant == "") {
      $results[$i]["errors"][] = CAppUI::tr("CFacture.no_montant");
    }

    $reference = $_reglement->getElementsByTagName("Ref")->item(0)->nodeValue;
    if (!$reference) {
      $results[$i]["errors"][] = CAppUI::tr("CFacture.no_reference");
    }

    $date_depot = $_reglement->getElementsByTagName("BookgDt")->item(0)->getElementsByTagName("Dt")->item(0)->nodeValue;
    if ($date_depot == "") {
      $results[$i]["errors"][] = CAppUI::tr("CFacture.no_date_depot");
    }

    $facture = $facture_class::findFacture($reference);

    $results[$i]["reference"] = $reference;
    $results[$i]["date_depot"] = $date_depot;
    $results[$i]["montant"] = $montant;
    $results[$i]["facture"] = $facture;

    if (!$facture->_id) {
      //Facture introuvable
      $results[$i]["errors"][] = CAppUI::tr("CFacture.no_identity_reference");
    }
    else {
      $facture->loadRefPatient();
      $facture->loadRefsObjects();
      $facture->loadRefsReglements();
      $facture->loadRefsRelances();
      if ($facture->_id && ($facture->patient_date_reglement || ($facture->_du_restant_patient-$reglement->montant) < 0)) {
        $results[$i][$type_error][] = CAppUI::tr("CFacture.reglement_sup");
      }
    }

    $results[$i]["facture"] = $facture;
    $reglement->object_id    = $facture->_id;
    $reglement->object_class = $facture->_class;
    $reglement->reference    = $reference;
    $reglement->emetteur     = "patient";
    $reglement->date         = $date_depot." 00:00:00";

    $totaux["total"]["count"] ++;
    $totaux["total"]["total"] += $reglement->montant;

    // No store on errors
    if (isset($results[$i]["errors"])) {
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

    // Creation du règlement
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

CAppUI::callbackAjax('$("systemMsg").insert', CAppUI::getMsg());

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("results"      , $results);
$smarty->assign("totaux"       , $totaux);
$smarty->assign("facture_class", $facture_class);
$smarty->assign("format_invalid", $format_invalid);

$smarty->display("vw_rapprochement_camt054");