<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbPDFMerger;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Prescription\CPrescription;

/**
 * Impression d'une sélection de documents
 */

$nbDoc   = CView::get("nbDoc", "str");
$nbFile  = CView::get("nbFile", "str");
$nbPresc = CView::get("nbPresc", "str");

CView::checkin();

$documents = array();
$tmp_files = array();
$pdf = new CMbPDFMerger();

if (is_array($nbDoc)) {
  CMbArray::removeValue("0", $nbDoc);
}

if (is_array($nbFile)) {
  CMbArray::removeValue("0", $nbFile);
}

if (is_array($nbPresc)) {
  CMbArray::removeValue("0", $nbPresc);
}

if ((!$nbDoc || !count($nbDoc)) && (!$nbFile || !count($nbFile)) && (!$nbPresc || !count($nbPresc))) {
  CAppUI::stepAjax("Aucun document à imprimer !");
  CApp::rip();
}

if (count($nbDoc)) {
  $compte_rendu = new CCompteRendu();
  $where = array("compte_rendu_id" => CSQLDataSource::prepareIn(array_keys($nbDoc)));

  /** @var $_compte_rendu CCompteRendu */
  foreach ($compte_rendu->loadList($where) as $_compte_rendu) {
    $_compte_rendu->date_print = CMbDT::dateTime();
    $_compte_rendu->store();
    $_compte_rendu->makePDFpreview(1);

    $nb_print = $nbDoc[$_compte_rendu->_id];
    for ($i = 1; $i <= $nb_print; $i++) {
      $pdf->addPDF($_compte_rendu->_ref_file->_file_path);
    }
  }
}

if (is_countable($nbFile) && count($nbFile)) {
  $file = new CFile();
  $where = array("file_id" => CSQLDataSource::prepareIn(array_keys($nbFile)));

  /** @var $_file CFile */
  foreach ($file->loadList($where) as $_file) {
    $nb_print = $nbFile[$_file->_id];
    for ($i = 1 ; $i <= $nb_print; $i++) {
      $tmp_file     = tempnam("./tmp", "file");
      $tmp_file_pdf = $tmp_file . ".pdf";
      $tmp_files[] = $tmp_file_pdf;
      $pdf->addPDF($pdf->convertPDToVersion14($_file->file_type, $tmp_file_pdf, $_file->_file_path));
    }
  }
}

if (is_countable($nbPresc) && count($nbPresc)) {
  $prescription = new CPrescription();
  $where = array("prescription_id" => CSQLDataSource::prepareIn(array_keys($nbPresc)));
  foreach ($prescription->loadList($where) as $_prescription) {
    $prats = array($_prescription->_ref_object->praticien_id);

    // Une ordonnance par praticien prescripteur
    $_prescription->loadRefsLinesMedComments("0", "0", "1", "", "", "1");
    $_prescription->loadRefsLinesElementsComments("0", "0", "", "", "", "1");
    $_prescription->loadRefsPrescriptionLineMixes("", "0", "0", "1", "", "1");

    $prats = array_merge($prats, CMbArray::pluck($_prescription->_ref_prescription_lines, "praticien_id"));
    $prats = array_merge($prats, CMbArray::pluck($_prescription->_ref_prescription_line_mixes, "praticien_id"));
    $prats = array_merge($prats, CMbArray::pluck($_prescription->_ref_prescription_lines_element, "praticien_id"));

    $prats = array_unique($prats);

    foreach ($prats as $_prat_id) {
      $params = array(
        "prescription_id"     => $_prescription->_id,
        "praticien_sortie_id" => $_prat_id,
        "stream"              => 0
      );

      CApp::fetch("dPprescription", "print_prescription_fr", $params);

      $file = new CFile();

      $where = array(
        "object_class" => "= '" . $_prescription->_ref_object->_class . "'",
        "object_id"    => "= '" . $_prescription->_ref_object->_id . "'",
        "file_name"    => "LIKE 'Ordonnance %'"
      );

      if (!$file->loadObject($where, "file_id DESC")) {
        continue;
      }

      $nb_print = $nbPresc[$_prescription->_id];
      for ($i = 1; $i <= $nb_print; $i++) {
        $pdf->addPDF($file->_file_path);
      }
    }
  }
}

// Stream du PDF au client avec ouverture automatique
// Si aucun pdf, alors PDFMerger génère une exception que l'on catche
try {
  $pdf->merge("browser", "documents.pdf");
}
catch(Exception $e) {
  CApp::rip();
}

foreach ($tmp_files as $_tmp_file) {
  unlink($_tmp_file);
}
