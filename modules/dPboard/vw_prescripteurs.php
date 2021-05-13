<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CMedecin;

/**
 * dPboard
 */
global $prat;

$start_prescripteurs = CValue::get("start_prescripteur", 0);
$step_prescripteurs  = 20;

CView::checkin();

$ds = CSQLDataSource::get("std");

$ds->exec(
  "CREATE TEMPORARY TABLE prat_patient (
     patient_id INT(11) UNSIGNED,
     medecin_id INT(11) UNSIGNED,
     origin ENUM('consultation','sejour')
   )"
);

$ds->exec(
  "INSERT INTO prat_patient (patient_id, medecin_id, origin)
   SELECT DISTINCT(sejour.patient_id), patients.medecin_traitant, 'sejour'
   FROM sejour
   LEFT JOIN patients
     ON sejour.patient_id = patients.patient_id
   WHERE praticien_id = $prat->_id"
);

$ds->exec(
  "INSERT INTO prat_patient (patient_id, medecin_id, origin)
   SELECT DISTINCT(consultation.patient_id), patients.medecin_traitant, 'consultation'
   FROM consultation
   LEFT JOIN plageconsult
     ON consultation.plageconsult_id = plageconsult.plageconsult_id
   LEFT JOIN patients
     ON consultation.patient_id = patients.patient_id
   WHERE plageconsult.chir_id = $prat->_id"
);

$prescripteurs = $ds->loadHashList(
  "SELECT medecin_id, COUNT(DISTINCT(patient_id)) AS nb_patients
   FROM prat_patient
   WHERE medecin_id IS NOT NULL
   GROUP BY medecin_id
   ORDER BY nb_patients DESC
   LIMIT $start_prescripteurs, $step_prescripteurs"
);

$total_prescripteurs = $ds->loadResult(
  "SELECT COUNT(DISTINCT(medecin_id))
   FROM prat_patient
   WHERE medecin_id IS NOT NULL"
);

// Chargement des medecins trouvés
$medecin             = new CMedecin();
$where               = array();
$where["medecin_id"] = CSQLDataSource::prepareIn(array_keys($prescripteurs));
$medecins            = $medecin->loadList($where);

// Variables de templates
$smarty = new CSmartyDP();

$smarty->assign("start_prescripteurs", $start_prescripteurs);
$smarty->assign("step_prescripteurs", $step_prescripteurs);
$smarty->assign("total_prescripteurs", $total_prescripteurs);
$smarty->assign("medecins", $medecins);
$smarty->assign("prescripteurs", $prescripteurs);

$smarty->display("vw_prescripteurs");
