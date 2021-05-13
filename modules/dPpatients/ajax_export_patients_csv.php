<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\Import\CMbObjectExport;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkAdmin();

$praticien_id = CView::get("praticien_id", "str");
$date_min     = CView::get('date_min', 'str');
$date_max     = CView::get('date_max', 'str');
$patient_id   = CView::get('patient_id', 'ref class|CPatient');
$all_prats    = CView::get('all_prats', 'str');

CView::enforceSlave();
CView::checkin();

// Set system limits
CApp::setTimeLimit(600);
CApp::setMemoryLimit("1024M");

$group = CGroups::loadCurrent();

$file = tempnam(rtrim(CAppUI::conf('root_dir'), '/\\') . "/tmp", 'export-patients');

if ($patient_id) {
  $patient = new CPatient();
  $patient->load($patient_id);
  if (!$patient->_id) {
    CAppUI::commonError("CPatient.none");
  }

  $patient->needsRead();
  $patients = array($patient);
}
else {
  if ($all_prats) {
    $praticiens   = CMbObjectExport::getPraticiensFromGroup();
    $praticien_id = CMbArray::pluck($praticiens, 'user_id');
  }
  list($patients) = CMbObjectExport::getPatientsToExport($praticien_id, $date_min, $date_max);
}

if ($patients) {
  CPatient::massLoadIPP($patients, $group->_id);

  $header = array(
    '_IPP', 'nom', 'prenom', 'naissance', 'sexe', 'prenoms', 'nom_jeune_fille', 'nom_soundex2',
    'prenom_soundex2', 'nomjf_soundex2', 'medecin_traitant_declare', 'matricule', 'code_regime', 'caisse_gest', 'centre_gest',
    'code_gestion', 'centre_carte', 'regime_sante', 'civilite', 'adresse', 'province', 'is_smg', 'ville', 'cp', 'tel', 'tel2',
    'tel_autre', 'email', 'vip', 'situation_famille', 'tutelle', 'incapable_majeur', 'ATNC', 'avs', 'deces', 'rques', 'cmu', 'ame',
    'ald', 'code_exo', 'libelle_exo', 'deb_amo', 'fin_amo', 'notes_amo', 'notes_amc', 'rang_beneficiaire', 'qual_beneficiaire',
    'rang_naissance', 'fin_validite_vitale', 'code_sit', 'regime_am', 'mutuelle_types_contrat', 'pays', 'pays_insee', 'lieu_naissance',
    'cp_naissance', 'pays_naissance_insee', 'profession', 'csp', 'status', 'assure_nom', 'assure_prenom', 'assure_prenoms',
    'assure_nom_jeune_fille', 'assure_sexe', 'assure_civilite', 'assure_naissance',
    'assure_adresse', 'assure_ville', 'assure_cp', 'assure_tel', 'assure_tel2', 'assure_pays', 'assure_pays_insee',
    'assure_lieu_naissance', 'assure_cp_naissance', 'assure_pays_naissance_insee', 'assure_profession', 'assure_rques',
    'assure_matricule', 'date_lecture_vitale', 'allow_sms_notification', 'allow_sisra_send',
  );

  $fp  = fopen($file, 'w+');
  $csv = new CCSVFile($fp);
  $csv->setColumnNames($header);
  $csv->writeLine($header);

  /** @var CPatient $_patient */
  foreach ($patients as $_patient) {
    $line = array();
    foreach ($header as $_field) {
      $line[] = $_patient->$_field;
    }

    $csv->writeLine($line);
  }

  $csv->close();
}

// Direct download of the file
// BEGIN extra headers to resolve IE caching bug (JRP 9 Feb 2003)
// [http://bugs.php.net/bug.php?id=16173]
header("Pragma: ");
header("Cache-Control: ");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");  //HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
// END extra headers to resolve IE caching bug

header("MIME-Version: 1.0");

header("Content-disposition: attachment; filename=\"patients-{$group->text}.csv\";");
header("Content-type: text/csv");
header("Content-length: " . filesize($file));

readfile($file);
unlink($file);
