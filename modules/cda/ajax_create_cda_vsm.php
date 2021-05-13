<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/* Création d'un fichier CDA pour le volet de synthèse médicale (VSM)*/

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbSecurity;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\CCdaTools;
use Ox\Interop\Hl7\Events\XDM\CHL7v3EventXDMDistributeDocumentSetOnMedia;
use Ox\Interop\Dmp\CDMPRequest;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\PlanningOp\CSejour;

$object_id    = CView::get("object_id", "str");
$object_class = CView::get("object_class", "str");
CView::checkin();

$level_cda = 3;

if (!$object_class || ($object_class !== "CSejour" && $object_class !== "CConsultation")) {
  CAppUI::stepAjax("ObjectClass doit être égale à CSejour ou CConsultation", UI_MSG_ERROR);
}

if (!$object_id) {
  CAppUI::stepAjax("ObjectId ne peut pas être nul", UI_MSG_ERROR);
}

// Contexte du VSM
/** @var CConsultation|CSejour $object */
$object = new $object_class;
$object->load($object_id);

if (!$object || !$object->_id) {
  CAppUI::stepAjax("Impossible de charger l'objet", UI_MSG_ERROR);
}

// Création du dossier médical car indispensable pour la création du CDA
$patient = $object->loadRefPatient();
CDossierMedical::dossierMedicalId($patient->_id, $patient->_class);

CAccessMedicalData::logAccess($object);

// Création du CFile avec un contenu vide pour le moment
$file = new CFile();
$file->setObject($object);
$file->file_name    = CCDAFactory::$name_file_vsm;
$file->file_type    = "application/xml";
$file->type_doc_dmp = CCDAFactory::$type_doc_vsm;
$file->author_id    = CAppUI::$instance->user_id;
$file->loadMatchingObject();

if (!$file->_id) {
  $charset                  = array_merge(range('a', 'f'), range(0, 9));
  $filename_length          = CFile::FILENAME_LENGTH;
  $file->file_real_filename = CMbSecurity::getRandomAlphaNumericString($charset, $filename_length);
}

$file->file_date = "now";
$file->setContent('Creation de la VSM en cours');
if ($msg = $file->store()) {
  CAppUI::stepAjax("Erreur dans l'enregistrement du fichier", UI_MSG_ERROR);
}

// Construction du CDA et du XDS
$iti32                        = new CHL7v3EventXDMDistributeDocumentSetOnMedia();
$iti32->type                  = CDMPRequest::$type;
$iti32->type_cda              = "VSM";
$file->_not_generate_exchange = true;
$iti32->build($file);

if ($iti32->report) {
    $smarty = new CSmartyDP();
    $smarty->assign('report', $iti32->report);
    $smarty->display("inc_create_cda_vsm.tpl");
    return;
}

// Mise à jour du contenu du CFile SYNTH.xml
$file->setContent($iti32->content_cda);
if ($msg = $file->store()) {
  CAppUI::stepAjax("Impossible d'enregistrer le CDA : $msg", UI_MSG_ERROR);
}
else {
  CAppUI::stepAjax("Fichier VSM créé");
}

// Création du ZIP IHE XDM
CCdaTools::createZipIheXDM($object, $file, $iti32->msg_hl7);

// Génération du PDF de la VSM
CCdaTools::generatePdfVSM($object, $file);
