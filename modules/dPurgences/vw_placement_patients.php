<?php

/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Core\Handlers\Facades\HandlerManager;
use Ox\Core\Module\CModule;
use Ox\Interop\Imeds\CImeds;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CLit;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Urgences\CRPU;

CCanDo::checkRead();
// Récupération des paramètres
$sejour_id   = CView::get("sejour_id", "ref class|CSejour");
$name_grille = CView::get("name_grille", "str");
$zone_id     = CView::get("zone_id", "ref class|CChambre");
CView::checkin();

$date           = CMbDT::dateTime();
$date_tolerance = CAppUI::conf("dPurgences date_tolerance");
$date_before    = CMbDT::date("-$date_tolerance DAY", $date);
$date_after     = CMbDT::date("+1 DAY", $date);

$group = CGroups::get();

$services_id = [
    "uhcd"     => null,
    "urgences" => null,
];
$sejours = [];
if ($sejour_id) {
    $sejour              = new CSejour();
    $sejours[$sejour_id] = $sejour->load($sejour_id);

    CAccessMedicalData::logAccess($sejour);
} else {
    //recherche des chambres d'urgences placées
    $chambre          = new CChambre();
    $ljoin            = [];
    $ljoin["service"] = "service.service_id = chambre.service_id";

    $where                     = [];
    $where["annule"]           = "= '0'";
    $where[]                   = "service.urgence = '1' OR service.radiologie = '1'";
    $where["service.group_id"] = "= '" . $group->_id . "'";
    $chambres_urgences         = $chambre->loadList($where, null, null, "chambre_id", $ljoin);

    $where                     = [];
    $where["annule"]           = "= '0'";
    $where["service.uhcd"]     = "= '1'";
    $where["service.group_id"] = "= '" . $group->_id . "'";
    $chambres_uhcd             = $chambre->loadList($where, null, null, "chambre_id", $ljoin);

    $_chambres = $chambres_urgences;
    foreach ($chambres_uhcd as $_chambre_uhcd) {
        $_chambres[$_chambre_uhcd->_id] = $_chambre_uhcd;
    }
    $lits = CStoredObject::massLoadBackRefs($_chambres, "lits");

    $ljoin                         = [];
    $ljoin["rpu"]                  = "rpu.sejour_id = sejour.sejour_id";
    $where                         = [];
    $where["sejour.entree"]        = " BETWEEN '$date_before' AND '$date_after'";
    $where["sejour.sortie_reelle"] = "IS NULL";
    $where["sejour.annule"]        = " = '0'";
    $where["sejour.group_id"]      = "= '" . $group->_id . "'";

    $temp = "";
    if (CAppUI::conf("dPurgences create_affectation")) {
        $ljoin["affectation"] = "affectation.sejour_id = sejour.sejour_id";
        $ljoin["service"]     = "service.service_id = affectation.service_id";
        $ljoin["lit"]         = "lit.lit_id = affectation.lit_id";
        $ljoin["chambre"]     = "chambre.chambre_id = lit.chambre_id";

        $where[] = "'$date' BETWEEN affectation.entree AND affectation.sortie";
        if (!CAppUI::conf("dPurgences view_rpu_uhcd")) {
            $temp = "service.urgence = '1' OR service.radiologie = '1'";
        }
        $where["chambre.chambre_id"] = CSQLDataSource::prepareIn(array_keys($_chambres));
    } else {
        $where["rpu.box_id"] = CSQLDataSource::prepareIn(array_keys($lits));
    }

    if (!CAppUI::conf("dPurgences create_sejour_hospit")) {
        $where[] = "rpu.mutation_sejour_id IS NULL";
    }

    if (!CAppUI::conf("dPurgences view_rpu_uhcd")) {
        $where["sejour.UHCD"] = " = '0'";
    }

    $where_temp = $where;
    if ($temp != "") {
        $where_temp[] = $temp;
    }
    $sejours_chambre = [];
    $sejour          = new CSejour();
    /** @var CSejour[] $sejours */
    $sejours = $sejour->loadList($where_temp, null, null, "sejour_id", $ljoin, "entree");

    if (!CAppUI::conf("dPurgences view_rpu_uhcd")) {
        $where["sejour.UHCD"] = " = '1'";
        $sejours_uhcd         = $sejour->loadList($where, null, null, "sejour_id", $ljoin, "entree");
        foreach ($sejours_uhcd as $sejour_uhcd) {
            $sejours[$sejour_uhcd->_id] = $sejour_uhcd;
        }
    }

    $patients = CStoredObject::massLoadFwdRef($sejours, "patient_id");
    CStoredObject::massLoadBackRefs($patients, "bmr_bhre");
    CStoredObject::massLoadFwdRef($sejours, "praticien_id");

    $service = new CService();
    $where   = [
        "urgence"   => " = '1'",
        "cancelled" => " = '0'",
    ];
    $service->loadObject($where);
    $services_id["urgences"] = $service->_id;
    $where                   = [
        "UHCD"      => " = '1'",
        "cancelled" => " = '0'",
    ];
    $service->loadObject($where);
    $services_id["uhcd"] = $service->_id;
}

CMbObject::massCountDocItems($sejours);

CSejour::massLoadNDA($sejours);

foreach ($sejours as $sejour) {
    $sejour->loadRefPatient()->updateBMRBHReStatus();
    $sejour->loadRefPraticien();
    $sejour->loadRefCurrAffectation()->loadRefService();
    $sejour->countDocItems();
    if (!$sejour->loadRefRPU()->_id) {
        $sejour->_ref_rpu = $sejour->loadUniqueBackRef("rpu_mute");
        if (!$sejour->_ref_rpu) {
            $sejour->_ref_rpu = new CRPU();
        }
    }
    $rpu = $sejour->_ref_rpu;
    $rpu->loadRefMotifSFMU();
    $rpu->loadRefsNotes();
    $rpu->getColorCIMU();
    $rpu->loadRefIDEResponsable()->loadRefFunction();
    $rpu->loadRefsLastAttentes();
    $reservation  = $rpu->loadRefReservation();
    $prescription = $sejour->loadRefPrescriptionSejour();

    if ($prescription->_id) {
        if (HandlerManager::isObjectHandlerActive('CPrescriptionAlerteHandler')) {
            $prescription->_count_fast_recent_modif = $prescription->countAlertsNotHandled("medium");
            $prescription->_count_urgence["all"]    = $prescription->countAlertsNotHandled("high");
        } else {
            $prescription->countFastRecentModif();
            $prescription->loadRefsLinesMedByCat();
            $prescription->loadRefsLinesElementByCat();
            $prescription->countUrgence(CMbDT::date($date));
        }
    }
    $chambre_id = $sejour->_ref_curr_affectation->loadRefLit()->chambre_id;
    if (!$chambre_id && !CAppUI::conf("dPurgences create_affectation")) {
        $lit = new CLit();
        $lit->load($sejour->_ref_rpu->box_id);
        $chambre_id = $lit->chambre_id;
    }
    $sejours_chambre[$chambre_id][] = $sejour;

    if ($sejour->_ref_rpu->_id && $reservation->_id && $sejour->_ref_rpu->box_id != $reservation->lit_id) {
        $chambre_resa                     = $reservation->loadRefLit()->chambre_id;
        $sejours_chambre[$chambre_resa][] = $sejour;
    }

    // Le chargement du rpu écrase le chargement de l'affectation courante
    $sejour->_ref_curr_affectation->loadRefPraticien()->loadRefFunction();
}

CPrescription::massLoadLinesElementImportant(
    array_combine(
        CMbArray::pluck($sejours, "_ref_prescription_sejour", "_id"),
        CMbArray::pluck($sejours, "_ref_prescription_sejour")
    )
);

// Mass loading des catégories sur les rpu
$rpus = [];
foreach ($sejours as $_sejour) {
    if (!$_sejour->_ref_rpu->_id) {
        continue;
    }
    $rpus[$_sejour->_ref_rpu->_id] = $_sejour->_ref_rpu;
}
CRPU::massLoadCategories($rpus);

$smarty = new CSmartyDP();
$smarty->assign("isImedsInstalled", (CModule::getActive("dPImeds") && CImeds::getTagCIDC($group)));
$smarty->assign("date", $date);

if ($sejour_id) {
    $zone = new CChambre();
    $zone->load($zone_id);

    $smarty->assign("_sejour", $sejour);
    $smarty->assign("name_grille", $name_grille);
    $smarty->assign("_zone", $zone);
    $smarty->assign("with_div", 0);
    $smarty->display("inc_patient_placement");

    return;
}

$grilles       = $listSejours = $lits_occupe = [];
$name_services = [];
$topologie = [
    "urgence" => $chambres_urgences,
    "uhcd"    => $chambres_uhcd,
];
if (!CAppUI::gconf("dPurgences Placement superposition_service")) {
    $topologie = [];
    foreach ($chambres_urgences as $_chambre_urg) {
        $topologie[$_chambre_urg->service_id][$_chambre_urg->_id] = $_chambre_urg;
        if (!isset($name_services[$_chambre_urg->service_id])) {
            $name_services[$_chambre_urg->service_id] = $_chambre_urg->loadRefService()->_view;
        }
    }
    $topologie["uhcd"] = $chambres_uhcd;
}

// Add affectations which are not linked to a stay (e.g. blocked bedroom)
// Go through services, load affectations which have a stay id === null
$emergency_services = array_filter(
    (new CService())->loadGroupList(),
    function (CService $service): bool {
        return (bool)$service->urgence;
    }
);
foreach ($emergency_services as $_emergency_services) {
    $affectations = $_emergency_services->loadRefsAffectations(CMbDT::date());
    foreach ($affectations as $_affectation) {
        if ($_affectation->sejour_id === null) {
            $topologie["urgence"][$_affectation->loadRefLit()->loadRefChambre()->_id] = $_affectation;
        }
    }
}

$exist_plan = [];
foreach ($topologie as $nom => $chambres) {
    $exist_plan["$nom"] = count($chambres);
    CService::vueTopologie($chambres, $grilles[$nom], $listSejours[$nom], $sejours_chambre, $lits_occupe[$nom]);
}

// Création du template
$smarty->assign("listSejours", $listSejours);
$smarty->assign("lits_occupe", $lits_occupe);
$smarty->assign("grilles", $grilles);
$smarty->assign("suiv", CMbDT::date("+1 day", $date));
$smarty->assign("prec", CMbDT::date("-1 day", $date));
$smarty->assign("exist_plan", $exist_plan);
$smarty->assign("services_id", $services_id);
$smarty->assign("name_services", $name_services);
$smarty->assign("avis_maternite_refresh_frequency", CAppUI::conf("dPurgences avis_maternite_refresh_frequency"));
$smarty->display("vw_placement_patients");
