<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Facturation\CFactureCabinet;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\CompteRendu\CHtmlToPDF;
use Ox\Mediboard\CompteRendu\CTemplateManager;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkEdit();
$facture_id     = CValue::getOrSession("facture_id");
$facture_class  = CValue::getOrSession("facture_class");

/* @var CFactureCabinet $facture*/
$facture = new $facture_class;
$facture->load($facture_id);
$facture->loadRefPatient();
$facture->loadRefPraticien();
$facture->loadRefsObjects();
$facture->loadRefsItems();
$facture->loadRefsReglements();

$template_header = new CTemplateManager();
$template_footer = new CTemplateManager();
$header_height   = $footer_height = 100;

$titre = $facture_class == "CFactureCabinet" ? "[ENTETE FACTURE CABINET]" : "[ENTETE FACTURE ETAB]";
$header = CCompteRendu::getSpecialModel($facture->_ref_praticien, $facture_class, $titre);

if ($header->_id) {
  $header->loadContent();
  $facture->fillTemplate($template_header);
  $template_header->renderDocument($header->_source);
  if ($header->height) {
    $header_height = $header->height+75;
  }
}

$titre = $facture_class == "CFactureCabinet" ? "[PIED DE PAGE FACT CABINET]" : "[PIED DE PAGE FACT ETAB]";
$footer = CCompteRendu::getSpecialModel($facture->_ref_praticien, $facture_class, $titre);

if ($footer->_id) {
  $footer->loadContent();
  $facture->fillTemplate($template_footer);
  $template_footer->renderDocument($footer->_source);
  if ($footer->height) {
    $footer_height = $footer->height;
  }
}

$style = file_get_contents("style/mediboard_ext/tables.css");
$smarty = new CSmartyDP();

$smarty->assign("style"        , $style);
$smarty->assign("facture"      , $facture);
$smarty->assign("header_height", $header_height);
$smarty->assign("footer_height", $footer_height);
$smarty->assign("header"       , $template_header->document);
$smarty->assign("footer"       , $template_footer->document);
$smarty->assign("body_height"  , 980-$header_height-$footer_height);

$content = $smarty->fetch("print_facture.tpl");

$file = new CFile();
$file->file_name = "Impression de la facture.pdf";
$file->setObject($facture);
$file->file_type  = "application/pdf";
$file->author_id = CMediusers::get()->_id;
$file->fillFields();
$file->updateFormFields();
$file->forceDir();

$htmltopdf = new CHtmlToPDF("CDomPDFConverter");
$cr = new CCompteRendu();
$cr->_page_format = "a4";
$cr->_orientation = "portrait";

if ($htmltopdf->generatePDF($content, 1, $cr, $file)) {
  $file->store();
}
