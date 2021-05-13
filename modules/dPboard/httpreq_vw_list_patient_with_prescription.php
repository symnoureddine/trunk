<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admissions\CSejourLoader;
use Ox\Mediboard\Hospi\CObservationMedicale;
use Ox\Mediboard\Hospi\CPrestation;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\CDiscipline;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Prescription\CPrescriptionLineElement;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Ox\Mediboard\Mpm\CPrescriptionLineMix;

$ds = CSQLDataSource::get("std");
// get
$user             = CUser::get();
$date             = CView::get("date", "date default|" . CMbDT::date(), true);
$board            = CView::get("board", "bool default|0");
$praticien_id_sel = CView::get("pratSel", "ref class|CMediusers default|" . $user->_id, true);
$function_id_sel  = CView::get("functionSel", "ref class|CFunctions");

CView::checkin();

$date_sortie_min = $date . " 00:00:00";
$date_entree_max = $date . " 23:59:59";

$userSel  = CMediusers::get($praticien_id_sel);
$function = new CFunctions();

if ($praticien_id_sel == "" && !$praticien_id_sel) {
    $praticien_id_sel = -1;
}

if ($praticien_id_sel) {
    // Si un praticien est sélectionné, filtre sur le praticien
    $print_content_class = "CMediusers";
    $print_content_id    = $praticien_id_sel;

    $users = [$userSel];
} elseif ($praticien_id_sel) {
    // Si un cabinet est sélectionné, filtre sur le cabinet
    $print_content_class = "CFunctions";
    $print_content_id    = $praticien_id_sel;

    $function->load($praticien_id_sel);
    $users = $function->loadRefsUsers();
}

$sejour       = new CSejour();
$prescription = new CPrescription();
$observation  = new CObservationMedicale();

foreach ($users as $praticien) {
    $praticien_sql = $ds->prepare(" = ?", $praticien->_id);

    $where_prescription_line = [
        "praticien_id" => $praticien_sql,
    ];
    $where_observation       = [
        "user_id" => $praticien_sql,
    ];

    $prescriptions_ids = (new CPrescriptionLineElement())->loadColumn(
        "prescription_id",
        $where_prescription_line
    );
    if (CPrescription::isMPMActive()) {
        $prescriptions_ids = array_merge(
            $prescriptions_ids,
            (new CPrescriptionLineMedicament())->loadColumn(
                "prescription_id",
                $where_prescription_line
            )
        );
        $prescriptions_ids = array_merge(
            $prescriptions_ids,
            (new CPrescriptionLineMix())->loadColumn("prescription_id", $where_prescription_line)
        );
    }

    $where_prescription = [
        "prescription_id" => CSQLDataSource::prepareIn($prescriptions_ids),
        "object_class"    => "= 'CSejour'",
    ];

    $sejours_ids = $prescription->loadColumn("object_id", $where_prescription);

    //Récupération des séjours liés à une observation
    $sejours_ids = array_merge($sejours_ids, $observation->loadColumn("sejour_id", $where_observation));

    //Filtre des séjours ayant le même responsable, et au moment de la date sélectionnée
    $where                 = [];
    $where["praticien_id"] = $ds->prepare("!= ?", $praticien->_id);
    $where["entree"]       = $ds->prepare(" <= ?", $date_entree_max);
    $where["sortie"]       = $ds->prepare(" >= ?", $date_sortie_min);
    $where["sejour_id"]    = $ds->prepareIn($sejours_ids);
    $sejours_ids           = $sejour->loadIds($where);
    $sejours_ids           = array_unique($sejours_ids);
}

$sejours = $sejour->loadAll($sejours_ids);
$sejours = CSejourLoader::loadSejoursForSejoursView($sejours, $users, $date, false);

// Création du template
//$smarty = new CSmartyDP();
$smarty = new CSmartyDP("modules/soins");

$smarty->assign("date", $date);
$smarty->assign("praticien", $userSel);
$smarty->assign("sejours", $sejours);
$smarty->assign("board", $board);
$smarty->assign("service", new CService());
$smarty->assign("service_id", null);
$smarty->assign("etats_patient", []);
$smarty->assign("show_affectation", false);
$smarty->assign("function", $function);
$smarty->assign("sejour_id", null);
$smarty->assign("show_full_affectation", true);
$smarty->assign("only_non_checked", false);
$smarty->assign("print", false);
$smarty->assign("_sejour", new CSejour());
$smarty->assign('ecap', false);
$smarty->assign('services_selected', []);
$smarty->assign('visites', []);
$smarty->assign("discipline", new CDiscipline());
$smarty->assign("lite_view", true);
$smarty->assign("print_content_class", $print_content_class);
$smarty->assign("print_content_id", $print_content_id);
$smarty->assign("allow_edit_cleanup", 0);
$smarty->assign("tab_to_update", "tab-autre-responsable");

if (CAppUI::conf("ref_pays") == 2) {
    $smarty->assign("prestations", CPrestation::loadCurrentList());
}
$smarty->assign("my_patient", false);
$smarty->assign("count_my_patient", 0);
$smarty->display("inc_list_sejours_global");
