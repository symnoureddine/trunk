<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCando;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkEdit();

$praticien_id    = CView::getRefCheckRead("praticien_id", "ref class|CMediusers", true);
$plageconsult_id = CView::getRefCheckRead("plageconsult_id", "ref class|CPlageconsult");
CView::checkin();

$mediuser = new CMediusers();
$mediuser->load($praticien_id);

$plage_consult = new CPlageconsult();
$plage_consult->load($plageconsult_id);

$medecin = new CMedecin();
$medecin->rpps = $mediuser->rpps;
$medecin->loadMatchingObject();

$smarty = new CSmartyDP();
$smarty->assign('plage_consult', $plage_consult);

$exercice_places = [];
if (!$medecin || !$medecin->_id) {
    $smarty->assign('exercice_places', $exercice_places);
    $smarty->display('inc_get_exercice_place');
    return;
}

$exercice_places = $medecin->getExercicePlaces();
if (!$exercice_places) {
    $smarty->assign('exercice_places', $exercice_places);
    $smarty->display('inc_get_exercice_place');
    return;
}

$smarty->assign('exercice_places', $exercice_places);
$smarty->display('inc_get_exercice_place');
