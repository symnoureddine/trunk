<?php
/**
 * @package Mediboard\cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/* Création d'un fichier CDA Lettre de Liaison (LDL) d'entrée d'établissement ou de sortie d'établissement */

use Ox\Core\CAppUI;
use Ox\Core\CMbSecurity;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\CCdaTools;
use Ox\Interop\Dmp\CDMPRequest;
use Ox\Interop\Hl7\Events\XDM\CHL7v3EventXDMDistributeDocumentSetOnMedia;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\PlanningOp\CSejour;

$object_id    = CView::get("object_id"   , "str");
$object_class = CView::get("object_class", "str");
$type_ldl     = CView::get("type_ldl"    , "str");
CView::checkin();

// Contexte du VSM
$object = new $object_class;
$object->load($object_id);

if (!$object || !$object->_id) {
  CAppUI::stepAjax('common-error-Object not found', UI_MSG_ERROR);
}

if (($type_ldl == 'LDL-SES' && !$object instanceof CSejour) || ($type_ldl == 'LDL-EES' && (!$object instanceof CConsultation && !$object instanceof CSejour))) {
  CAppUI::stepAjax("ObjectClass incorrecte", UI_MSG_ERROR);
}

$patient = $object->loadRefPatient();

if (!$patient || !$patient->_id) {
  CAppUI::stepAjax('common-error-Object not found', UI_MSG_ERROR);
}

// Création du dossier médical car indispensable pour la création du CDA
$patient = $object->loadRefPatient();
CDossierMedical::dossierMedicalId($patient->_id, $patient->_class);

// Création du CFile avec un contenu vide pour le moment
$file = new CFile();
$file->setObject($object);
$file->file_type    = "application/xml";
$file->type_doc_dmp = CCDAFactory::getMetadata($type_ldl, 'type_doc');
$file->setObject($object);
// Seulement un fichier LDL par contexte
$file->loadMatchingObject();
$file->file_name      = CCDAFactory::getMetadata($type_ldl, 'file_name_mb');
$file->_file_name_cda = CCDAFactory::getMetadata($type_ldl, 'file_name');
$file->author_id    = CAppUI::$instance->user_id;

if (!$file->_id) {
  $charset                  = array_merge(range('a', 'f'), range(0, 9));
  $filename_length          = CFile::FILENAME_LENGTH;
  $file->file_real_filename = CMbSecurity::getRandomAlphaNumericString($charset, $filename_length);
}

$file->setContent('Creation de la lettre de liaison en cours');
$file->file_date = "now";
if ($msg = $file->store()) {
  CAppUI::stepAjax("Erreur dans l'enregistrement du fichier", UI_MSG_ERROR);
}

// Construction du CDA et du XDS
$iti32                        = new CHL7v3EventXDMDistributeDocumentSetOnMedia();
$iti32->type                  = CDMPRequest::$type;
$iti32->type_cda              = $type_ldl;
$iti32->code_loinc_cda        = CCDAFactory::getMetadata($type_ldl, 'code_loinc');
$file->_not_generate_exchange = true;
$iti32->build($file);

if ($iti32->report) {
    $smarty = new CSmartyDP();
    $smarty->assign('report', $iti32->report);
    $smarty->display("inc_create_cda_vsm.tpl");
    return;
}

$file->setContent($iti32->content_cda);
if ($msg = $file->store()) {
  CAppUI::stepAjax("Impossible d'enregistrer le CDA : $msg", UI_MSG_ERROR);
}
else {
  CAppUI::stepAjax("Fichier Lettre de liaison créé");
}

// Création du ZIP IHE XDM
CCdaTools::createZipIheXDM($object, $file, $iti32->msg_hl7);
