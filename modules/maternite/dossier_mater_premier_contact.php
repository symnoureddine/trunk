<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkEdit();

$grossesse_id = CView::get("grossesse_id", "ref class|CGrossesse");
$print        = CView::get("print", "bool default|0");

CView::checkin();

$grossesse = new CGrossesse();
$grossesse->load($grossesse_id);
$grossesse->loadRefGroup();
$grossesse->loadRefPere();
$grossesse->getDateAccouchement();
$grossesse->loadRefsNaissances();

$patient = $grossesse->loadRefParturiente();
$patient->loadIPP($grossesse->group_id);
$patient->loadRefsCorrespondants();
$patient->loadRefsCorrespondantsPatient();

$dossier = $grossesse->loadRefDossierPerinat();

if ($dossier->date_premier_contact) {
  $sa_comp  = $grossesse->getAgeGestationnel($dossier->date_premier_contact);
  $age_gest = $sa_comp["SA"];
}
else {
  $age_gest = "--";
}

$consultations = $grossesse->loadRefsConsultations();
foreach ($consultations as $consult) {
  $consult->loadRefPraticien();
  $consult->loadRefSuiviGrossesse();
  $consult->getSA();
}

// Liste des consultants
$mediuser        = new CMediusers();
$listConsultants = $mediuser->loadProfessionnelDeSanteByPref(PERM_EDIT);

if (!$dossier->consultant_premier_contact_id && in_array(CAppUI::$user->_id, array_keys($listConsultants))) {
  $dossier->consultant_premier_contact_id = CAppUI::$user->_id;
}

$smarty = new CSmartyDP();

$smarty->assign("grossesse", $grossesse);
$smarty->assign("age_gest", $age_gest);
$smarty->assign("listConsultants", $listConsultants);
$smarty->assign("print", $print);

$smarty->display("dossier_mater_premier_contact.tpl");

