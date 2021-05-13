<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\SmsProviders\CLotSms;
use Ox\Mediboard\System\CPlanningEvent;
use Ox\Mediboard\System\CPlanningRange;
use Ox\Mediboard\System\CPlanningWeek;

CCanDo::checkRead();
$chirSel     = CValue::getOrSession("chirSel");
$function_id = CValue::get("function_id");
$today       = CMbDT::date();

$show_free      = CValue::get("show_free");
$show_cancelled = CView::get("show_cancelled", "bool default|0", true);
$facturated     = CValue::get("facturated");
$status         = CValue::get("status");
$actes          = CValue::get("actes");
$hide_in_conge  = CValue::get("hide_in_conge", 0);
$type_view      = CValue::getOrSession("type_view", null);
$print          = CValue::get("print", 0);
$scroll_top     = CView::get("scroll_top", "num default|0");
$debut          = CValue::getOrSession("debut");
CView::checkin();

$min_hour = 23;

// gathering prat ids
$ids      = [];
$function = new CFunctions();
$function->load($function_id);
if ($function->_id) {
    $function->loadRefsUsers();
    foreach ($function->_ref_users as $_user) {
        $ids[] = $_user->_id;
    }
}

if (!$function_id && $chirSel) {
    $ids[] = $chirSel;
}

// Nombre de visites à domicile
$nb_visite_domicile = 0;
$chir               = CMediusers::get($chirSel);
$whereChir          = $chir->getUserSQLClause();

// Liste des consultations a avancer si desistement
$count_si_desistement = CConsultation::countDesistementsForDay($ids, $today);

// Période
$debut = CMbDT::date("last sunday", $debut);
$fin   = CMbDT::date("next sunday", $debut);
$debut = CMbDT::date("+1 day", $debut);

$prev = CMbDT::date("-1 week", $debut);
$next = CMbDT::date("+1 week", $debut);

$dateArr   = CMbDT::date("+6 day", $debut);
$nbDays    = 7;
$listPlage = new CPlageconsult();

$whereInterv        = [];
$whereHP            = [];
$where              = [];
$where["date"]      = $whereInterv["date"] = $whereHP["date"] = "= '$dateArr'";
$whereHP["chir_id"] = $whereChir;
$whereInterv[]      = "chir_id $whereChir OR spec_id = '$chir->function_id'";
$where[]            = "chir_id $whereChir OR remplacant_id $whereChir";

if (!$listPlage->countList($where)) {
    $nbDays--;
    // Aucune plage le dimanche, on peut donc tester le samedi.
    $dateArr       = CMbDT::date("+5 day", $debut);
    $where["date"] = "= '$dateArr'";
    if (!$listPlage->countList($where)) {
        $nbDays--;
    }
}

$bank_holidays = array_merge(CMbDT::getHolidays($debut), CMbDT::getHolidays($fin));

// Planning Week
$planning = new CPlanningWeek($debut, $debut, $fin, $nbDays, false, $print ? "1000" : "auto");

$user = new CMediusers();
$user->load($chirSel);
$see_notification = CModule::getActive("smsProviders") && $chirSel && count(CLotSms::loadForUser($user, false)) ? 1 : 0;
if ($user->_id) {
    $user->loadRefFunction();
    $planning->title = $user->_view;
} else {
    $planning->title = "";
}

$can_edit = CCanDo::edit();

$planning->guid               = $user->_guid;
$planning->hour_min           = "07";
$planning->hour_max           = "20";
$planning->pauses             = ["07", "12", "19"];
$planning->dragndrop          = $planning->resizable = $can_edit ? 1 : 0;
$planning->hour_divider       = 60 / CAppUI::gconf('dPcabinet CPlageconsult minutes_interval');
$planning->no_dates           = 0;
$planning->reduce_empty_lines = 1;

$plage = new CPlageconsult();

$whereHP["plageop_id"] = " IS NULL";

$users      = [];
$conges_day = [];
if ($user->_id) {
    $muser = new CMediusers();
    $users = $muser->loadUsers(PERM_READ, $user->function_id);
}
$libelles_plages = CPlageconsult::getLibellesPref();

for ($i = 0; $i < $nbDays; $i++) {
    $jour       = CMbDT::date("+$i day", $debut);
    $is_holiday = array_key_exists($jour, $bank_holidays);

    $planning->addDayLabel($jour, '<span style="font-size: 1.4em">' . CMbDT::format($jour, "%a %d %b") . '</span>');

    // conges dans le header
    if (count($users)) {
        if (CModule::getActive("dPpersonnel")) {
            $_conges = CPlageConge::loadForIdsForDate(array_keys($users), $jour);
            foreach ($_conges as $key => $_conge) {
                $_conge->loadRefUser();
                $conges_day[$i][] = $_conge->_ref_user->_shortview;
            }
        }
    }
    $where["date"] = $whereInterv["date"] = $whereHP["date"] = "= '$jour'";

    if (CAppUI::pref("showIntervPlanning")) {
        if (!$is_holiday || CAppUI::pref("show_plage_holiday")) {
            //INTERVENTIONS
            /** @var CPlageOp[] $intervs */
            $interv  = new CPlageOp();
            $intervs = $interv->loadList($whereInterv);
            CStoredObject::massLoadFwdRef($intervs, "chir_id");
            foreach ($intervs as $_interv) {
                $range = new CPlanningRange(
                    $_interv->_guid,
                    $jour . " " . $_interv->debut,
                    CMbDT::minutesRelative($_interv->debut, $_interv->fin),
                    CAppUI::tr($_interv->_class),
                    "bbccee",
                    "plageop"
                );
                $planning->addRange($range);
            }

            //HORS PLAGE
            $horsPlage = new COperation();
            /** @var COperation[] $horsPlages */
            $horsPlages = $horsPlage->loadList($whereHP);
            CStoredObject::massLoadFwdRef($horsPlages, "chir_id");
            foreach ($horsPlages as $_horsplage) {
                $lenght = (CMBDT::minutesRelative("00:00:00", $_horsplage->temp_operation));
                $op     = new CPlanningRange(
                    $_horsplage->_guid,
                    $jour . " " . $_horsplage->time_operation,
                    $lenght,
                    $_horsplage->_view,
                    "3c75ea",
                    "horsplage"
                );
                $planning->addRange($op);
            }
        }
    }

    // PLAGES CONGE
    $is_conge = false;
    if (CModule::getActive("dPpersonnel")) {
        $conge                  = new CPlageConge();
        $where_conge            = [];
        $where_conge[]          = "'$jour' BETWEEN DATE(date_debut) AND DATE(date_fin)";
        $where_conge["user_id"] = "= '$chirSel'";
        /** @var CPlageconge[] $conges */
        $conges   = $conge->loadList($where_conge);
        $is_conge = count($conges) > 0;
        foreach ($conges as $_conge) {
            $libelle = '<h3 style="text-align: center">
        ' . CAppUI::tr("CPlageConge|pl") . '</h3>
        <p style="text-align: center">' . $_conge->libelle . '</p>';

            $_date = $_conge->date_debut;

            while ($_date < $_conge->date_fin) {
                $length       = CMbDT::minutesRelative($_date, $_conge->date_fin);
                $event        = new CPlanningEvent(
                    $_conge->_guid . $_date,
                    $_date,
                    $length,
                    $libelle,
                    "#ddd",
                    true,
                    "hatching",
                    null,
                    false
                );
                $event->below = 1;
                $planning->addEvent($event);
                $_date = CMbDT::dateTime("+1 DAY", CMbDT::date($_date));
            }
        }
    }

    //PLAGES CONSULT

    // férié & pref
    if ($is_holiday && !CAppUI::pref("show_plage_holiday")) {
        continue;
    }

    // conges
    if ($is_conge && $hide_in_conge) {
        continue;
    }

    //Filtre sur le nom des plages de consultations
    if (count($libelles_plages)) {
        $where["libelle"] = CSQLDataSource::prepareIn($libelles_plages);
    }

    /** @var CPlageConsult[] $plages */
    $plages = $plage->loadList($where, "date, debut");

    $chirs_plages = CStoredObject::massLoadFwdRef($plages, "chir_id");
    CStoredObject::massLoadFwdRef($chirs_plages, "function_id");
    CStoredObject::massLoadFwdRef($plages, "remplacant_id");
    CStoredObject::massLoadFwdRef($plages, "pour_compte_id");
    foreach ($plages as $_plage) {
        $_plage->loadRefChir();
        $_plage->loadRefRemplacant();
        $_plage->loadRefPourCompte();
        $_plage->loadRefsConsultations($show_cancelled);
        $_plage->loadRefChir()->loadRefFunction();
        $_plage->loadRefAgendaPraticien();

        if (CMbDT::format($_plage->debut, "%H") < $min_hour) {
            $min_hour = CMbDT::format($_plage->debut, "%H");
        }

        // Affichage de la plage sur le planning
        $range = new CPlanningRange(
            $_plage->_guid,
            $jour . " " . $_plage->debut,
            CMbDT::minutesRelative($_plage->debut, $_plage->fin),
            $_plage->libelle,
            $_plage->color
        );

        if ($_plage->_ref_agenda_praticien->sync) {
            $range->icon      = "fas fa-sync-alt";
            $range->icon_desc = CAppUI::tr("CAgendaPraticien-sync-desc");
        if (!CMediusers::get()->isAdmin()) {
                $range->disabled = true;
            }
        }

        $range->type = "plageconsult";
        $planning->addRange($range);

        //RdvFree
        if ($show_free) {
            $utilisation = $_plage->getUtilisation();
            $_plage->colorPlanning($chirSel);
            foreach ($utilisation as $_timing => $_nb) {
                if (!$_nb) {
                    $debute             = "$jour $_timing";
                    $event              = new CPlanningEvent(
                        $debute,
                        $debute,
                        $_plage->_freq,
                        "",
                        $_plage->_color_planning,
                        true,
                        "droppable",
                        null
                    );
                    $event->type        = "rdvfree$type_view";
                    $event->plage["id"] = $_plage->_id;
                    if ($_plage->locked == 1) {
                        $event->disabled = true;
                    }
                    $event->plage["color"] = $_plage->color;

                    if ($_plage->_ref_agenda_praticien->sync) {
                        $event->disabled = true;
                    }

                    $event->datas = ["meeting_id" => "", "pause" => "0"];

                    //Ajout de l'évènement au planning
                    $planning->addEvent($event);
                }
            }
        }

        //consultations
        $consults = [];
        foreach ($_plage->_ref_consultations as $_consult) {
            /* @var CConsultation $_consult */
            if ($status && $_consult->chrono != $status) {
                continue;
            }
            $_consult->loadRefFacture();
            if (
                ($facturated === "1" && !$_consult->_ref_facture->_id)
                || ($facturated === "0" && $_consult->_ref_facture->_id)
            ) {
                continue;
            }
            $consults[$_consult->_id] = $_consult;
        }

        CStoredObject::massLoadFwdRef($consults, "patient_id");
        CStoredObject::massLoadFwdRef($consults, "categorie_id");
        CStoredObject::massLoadFwdRef($consults, "reunion_id");
        $dossiers_anesth = CStoredObject::massLoadBackRefs($consults, "consult_anesth");
        CMbObject::countAlertDocs($consults);
        CMbObject::countAlertDocs($dossiers_anesth);
        CMbObject::countLockedAlertDocs($consults);
        CMbObject::countLockedAlertDocs($dossiers_anesth);

        if ($see_notification) {
            CStoredObject::massLoadBackRefs($consults, "context_notifications");
        }
        foreach ($consults as $_consult) {
            foreach ($_consult->_back["consult_anesth"] as $_consult_anesth) {
                $_consult->_alert_docs += $_consult_anesth->_alert_docs;
            }

            if ($_consult->heure < $min_hour) {
                $min_hour = CMbDT::format($_consult->heure, "%H");
            }

            if ($actes != "") {
                $_actes   = $_consult->loadRefsActes();
                $nb_actes = $_consult->_count_actes;
                // avec des actes
                if ($actes && !$nb_actes) {
                    continue;
                }
                // sans actes
                if ($actes === "0" && $nb_actes > 0) {
                    continue;
                }
            }

            $consult_termine = ($_consult->chrono == CConsultation::TERMINE) ? "hatching" : "";

            $_consult->loadPosition();
            $debute = "$jour $_consult->heure";
            $motif  = $_consult->motif;
            $_consult->colorPlanning();
            if ($_consult->patient_id) {
                $_consult->loadRefPatient();

                $style = "";
                if ($_consult->annule) {
                    $style .= "text-decoration:line-through;";
                }

                $title = "";
                if ($_consult->_consult_sejour_out_of_nb) {
                    $nb    = $_consult->_consult_sejour_nb;
                    $of    = $_consult->_consult_sejour_out_of_nb;
                    $title .= "<span style=\"float:right;\">$nb / $of</span>";
                }

                if ($_consult->visite_domicile) {
                    $title .= "<i class=\"fa fa-home\" style=\"font-size: 1.2em;\" " .
                        "title=\"" . CAppUI::tr("CConsultation-visite_domicile-desc") . "\"></i> ";
                    $nb_visite_domicile++;
                }

                if ($_consult->_alert_docs) {
                    if ($_consult->_count["alert_docs"] == $_consult->_count["locked_alert_docs"]) {
                        $title .= "<i class=\"far fa-file\" " .
                            "style=\"float: right; font-size: 1.3em;color:green;background-color:lightgreen;" . "\" " .
                            "title=\"" . CAppUI::tr("CCompteRendu-alert_locked_docs_object.all") . "\"></i>";
                    } else {
                        $title .= "<i class=\"far fa-file\" style=\"float: right; font-size: 1.3em;" . "\" " .
                            "title=\"" . CAppUI::tr("CCompteRendu-alert_docs_object") . "\"></i>";
                    }
                }

                $_consult->loadRefFacture();
                $title .= "<i class=\"";
                if ($_consult->_ref_facture && $_consult->_ref_facture->_id && !$_consult->_ref_facture->annule) {
                    $title .= "texticon texticon-ok\" title = \"" . CAppUI::tr("CConsultation-has_facture");
                } else {
                    $title .= "texticon texticon-gray\" title = \"" . CAppUI::tr("CConsultation-has_no_facture");
                }

                $title .= "\" style=\"float:right\">F</i>";

                // Display resources
                $res_title = "";
                $_consult->loadRefReservedRessources();
                $plage_resource_id = CStoredObject::massLoadFwdRef(
                    $_consult->_ref_reserved_ressources,
                    "plage_ressource_cab_id"
                );
                CStoredObject::massLoadFwdRef($plage_resource_id, "ressource_cab_id");
                foreach ($_consult->_ref_reserved_ressources as $_reserved) {
                    $resource = $_reserved->loadRefPlageRessource()->loadRefRessource();

                    $res_title .= '<span class="texticon me-margin-2" style="color: #' . $resource->color . '; ' .
                        'border: 1px solid #' . $resource->color . ';">';
                    $res_title .= $resource->libelle;
                    $res_title .= '</span>';
                }

                //Ajout du cartouche de DHE dans le nouveau semainier
                $_consult->loadRefConsultAnesth();
                $_consult->checkDHE();
                $consult_anesth = $_consult->_ref_consult_anesth;
                if ($consult_anesth && $consult_anesth->_id && $_consult->_etat_dhe_anesth) {
                    if ($_consult->_etat_dhe_anesth == "associe") {
                        $title .= "<span class=\"texticon texticon-allergies-ok\"";
                        $title .= "title=\"" . CAppUI::tr(
                                "CConsultation-_etat_dhe_anesth-associe"
                            ) . "\" style=\"float: right;\">";
                        $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
                    } elseif ($_consult->_etat_dhe_anesth == "dhe_exist") {
                        $title .= "<span class=\"texticon texticon-atcd\"";
                        $title .= "title=\"" . CAppUI::tr(
                                "CConsultation-_etat_dhe_anesth-dhe_exist"
                            ) . "\" style=\"float: right;\">";
                        $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
                    } elseif ($_consult->_etat_dhe_anesth == "non_associe") {
                        $title .= "<span class=\"texticon texticon-stup texticon-stroke\"";
                        $title .= "title=\"" . CAppUI::tr(
                                "CConsultation-_etat_dhe_anesth-non_associe"
                            ) . "\" style=\"float: right;\">";
                        $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
                    }
                }

                if ($see_notification && $_consult->_ref_patient->allow_sms_notification) {
                    $_consult->loadRefNotification();
                    $title .= $_consult->smsPlaning();
                }

                $title .= "<span style=\"$style\">";
                $title .= $_consult->_ref_patient->_view . "\n" . $motif;
                $title .= "</span>";

                if ($_consult->adresse_par_prat_id) {
                    $medecin = $_consult->loadRefAdresseParPraticien();
                    $title   .= "<span class='compact'>" . "Adressé par Dr $medecin->nom $medecin->prenom" . "</span>";
                }
                $_consult->loadRefCategorie();

                $title .= $res_title;

                $event               = new CPlanningEvent(
                    $_consult->_guid,
                    $debute,
                    $_consult->duree * $_plage->_freq,
                    $title,
                    $_consult->_color_planning,
                    true,
                    "droppable $debute",
                    $_consult->_guid,
                    false
                );
                $event->border_color = $_consult->_ref_categorie->couleur;
                $event->border_title = $_consult->_ref_categorie->nom_categorie;
            } else {
                $title_consult = CAppUI::tr("CConsultation-PAUSE");

                if ($_consult->groupee && $_consult->no_patient) {
                    $title_consult = CAppUI::tr("CConsultation-MEETING");
                }

                $_consult->loadRefCategorie();

                $title = "[" . $title_consult . "] $motif";

                $event               = new CPlanningEvent(
                    $_consult->_guid,
                    $debute,
                    $_consult->duree * $_plage->_freq,
                    $title,
                    $_consult->_color_planning,
                    true,
                    "droppable $debute",
                    $_consult->_guid,
                    false
                );
                $event->border_color = $_consult->_ref_categorie->couleur;
                $event->border_title = $_consult->_ref_categorie->nom_categorie;
            }
            $event->type                    = "rdvfull$type_view";
            $event->plage["id"]             = $_plage->_id;
            $event->plage["consult_id"]     = $_consult->_id;
            $event->plage["patient_id"]     = $_consult->patient_id;
            $event->plage["patient_status"] = $_consult->loadRefPatient()->status;
            if ($_plage->locked == 1) {
                $event->disabled = true;
            }

            $_consult->loadRefCategorie();
            if ($_consult->categorie_id) {
                $event->icon      = "./modules/dPcabinet/images/categories/" . $_consult->_ref_categorie->nom_icone;
                $event->icon_desc = $_consult->_ref_categorie->nom_categorie;
            }
            if ($_consult->_id && !$print) {
                $can_edit                                  = $_consult->canDo()->edit;
                $event->draggable /*= $event->resizable */ = $can_edit;
                $freq                                      = 1;
                if ($_plage->freq) {
                    $freq = intval(CMbDT::transform($_plage->freq, null, "%H")) * 60 + intval(
                            CMbDT::transform($_plage->freq, null, "%M")
                        );
                }
                $event->hour_divider = 60 / $freq;

                if ($can_edit) {
                    $event->addMenuItem("copy", CAppUI::tr("CConsultation-copy"));
                    $event->addMenuItem("cut", CAppUI::tr("CConsultation-cut"));
                    if ($_consult->patient_id) {
                        $event->addMenuItem("add", CAppUI::tr("CConsultation-add"));
                        if ($_consult->chrono == CConsultation::PLANIFIE) {
                            $event->addMenuItem("tick", CAppUI::tr("CConsultation-notify_arrive-court"));
                        }
                        if ($_consult->chrono == CConsultation::PATIENT_ARRIVE) {
                            $event->addMenuItem("tick_cancel", CAppUI::tr("CConsultation-cancel_arrive"));
                        }
                    }

                    if (
                        $_consult->chrono != CConsultation::TERMINE
                        && $_consult->chrono != CConsultation::PATIENT_ARRIVE
                    ) {
                        $event->addMenuItem("cancel", CAppUI::tr("CConsultation-cancel_rdv"));
                    }
                }
            }

            $event->status = $consult_termine;

            $_consult->loadRefReunion();
            $meeting_id   = ($_consult->_ref_reunion->reunion_id) ? $_consult->_ref_reunion->reunion_id : '';
            $pause        = ($_consult->patient_id) ? "0" : "1";
            $event->datas = [
                "meeting_id"     => $meeting_id,
                "pause"          => $pause,
                "patient_id"     => $_consult->patient_id,
                "patient_status" => $_consult->_ref_patient->status,
            ];

            //Ajout de l'évènement au planning
            $event->plage["color"] = $_plage->color;
            $event->below          = 0;
            $planning->addEvent($event);
        }
    }
}

$planning->hour_min = $min_hour;

// conges
foreach ($conges_day as $key => $_day) {
    $conges_day[$key] = implode(", ", $_day);
}

$planning->rearrange(true);

$smarty = new CSmartyDP();
$smarty->assign("planning", $planning);
$smarty->assign("debut", $debut);
$smarty->assign("fin", $fin);
$smarty->assign("prev", $prev);
$smarty->assign("next", $next);
$smarty->assign("chirSel", $chirSel);
$smarty->assign("conges", $conges_day);
$smarty->assign("function_id", $function_id);
$smarty->assign("user", $user);
$smarty->assign("today", $today);
$smarty->assign("height_calendar", CAppUI::pref("height_calendar", "2000"));
$smarty->assign("bank_holidays", $bank_holidays);
$smarty->assign("count_si_desistement", $count_si_desistement);
$smarty->assign("print", $print);
$smarty->assign("nb_visite_dom", $nb_visite_domicile);
$smarty->assign("scroll_top", $scroll_top);
$smarty->assign("show_cancelled", $show_cancelled);
$smarty->display("inc_vw_planning.tpl");
