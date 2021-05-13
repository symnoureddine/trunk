<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbPDFMerger;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();

$sejour_id = CView::get("sejour_id", "ref class|CSejour");

CView::checkin();

$pdf = new CMbPDFMerger();

$sejour = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

// PDF du dossier de soins
$pdf_content = $sejour->getPrintDossierSoins(1);

$file_path = tempnam("./tmp", "dossier_soins");
file_put_contents($file_path, $pdf_content);

$pdf->addPDF($file_path);

unlink($file_path);

// PDFs des items documentaires du séjour
$sejour->loadRefsDocs();

/** @var CCompteRendu $_doc */
foreach ($sejour->_ref_documents as $_doc) {
  $_doc->date_print = CMbDT::dateTime();
  $_doc->store();
  $_doc->makePDFpreview(1);

  $pdf->addPDF($_doc->_ref_file->_file_path);
}

// Ajout des CFile si la conversion PDF est activée et possible
/** @var CFile $_file */
$sejour->loadRefsFiles();
foreach ($sejour->_ref_files as $_file) {
  $file_path_pdf = strpos($_file->file_type, "pdf") ? $_file->_file_path : null;

  if ($_file->isPDFconvertible() && $_file->convertToPDF()) {
    $file_path_pdf = $_file->loadPDFconverted()->_file_path;
  }

  if ($file_path_pdf) {
    $pdf->addPDF($file_path_pdf);
  }
}

foreach ($sejour->loadRefsOperations(array("annulee" => "= '0'")) as $_op) {
  /** @var CCompteRendu $_doc */
  $_op->loadRefsDocs();
  foreach ($_op->_ref_documents as $_doc) {
    $_doc->date_print = CMbDT::dateTime();
    $_doc->store();
    $_doc->makePDFpreview(1);

    $pdf->addPDF($_doc->_ref_file->_file_path);
  }

  if (CAppUI::conf("dPfiles CFile ooo_active")) {
    $_op->loadRefsFiles();

    foreach ($_op->_ref_files as $_file) {
      if ($_file->isPDFconvertible() && $_file->convertToPDF()) {
        $pdf->addPDF($_file->loadPDFconverted()->_file_path);
      }
    }
  }
}

// Stream au navigateur
try {
  $pdf->merge("browser", "documents.pdf");
}
catch (Exception $e) {
  CAppUI::stepAjax(utf8_encode("Aucun PDF à générer"));
  CApp::rip();
}