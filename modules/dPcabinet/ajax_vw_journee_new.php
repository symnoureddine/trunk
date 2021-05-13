<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Cabinet\CRessourceCab;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Mediboard\SmsProviders\CLotSms;
use Ox\Mediboard\System\CPlanningEvent;
use Ox\Mediboard\System\CPlanningRange;
use Ox\Mediboard\System\CPlanningWeek;

CCanDo::checkRead();
$request_form     = (bool)CView::get("request_form", "bool default|0");
$date             = CView::get("date", "date default|now", true);
$function_id      = CView::getRefCheckRead("function_id", "ref class|CFunctions", true);
$show_free        = CView::get("show_free", "bool default|1");
$cancelled        = CView::get("cancelled", "bool");
$hide_in_conge    = CView::get("hide_in_conge", "bool default|0");
$hide_empty_range = CView::get("hide_empty_range", "bool default|1", true);
$facturated       = CView::get("facturated", "str");
$finished         = CView::get("finished", "str");
$actes            = CView::get("actes", "str");
$ressources_ids   = CValue::sessionAbs("planning_ressources_ids");
$prats_selected   = CValue::sessionAbs("planning_prats_selected");
$scroll_top       = CView::get("scroll_top", "num default|0");

$is_cabinet = (CAppUI::isCabinet()) ? 1 : 0;
$highlight   = CView::get("highlight", "bool default|" . $is_cabinet, $is_cabinet);

if ($request_form) {
  $ressources_ids = CView::get("ressources_ids", "str");
  $prats_selected = CView::get("prats_selected", ["str"]);

  CValue::setSessionAbs("planning_ressources_ids", $ressources_ids);
  CValue::setSessionAbs("planning_prats_selected", $prats_selected);
}

CView::checkin();

$function = new CFunctions();
$function->load($function_id);
$user  = new CMediusers();
$users = $user->loadProfessionnelDeSanteByPref(PERM_READ, $function_id, null, true);

$prats_selected = ($prats_selected) ? array_filter($prats_selected) : [];
if (count($prats_selected)) {
  foreach ($users as $_user) {
    if (!in_array($_user->_id, $prats_selected)) {
      unset($users[$_user->_id]);
    }
  }
}

//Filtre sur le nom des plages de consultations
if ($hide_empty_range) {
  foreach ($users as $_user) {
    if (!$_user->checkRangeConsult($date)) {
      unset($users[$_user->_id]);
    }
  }
}

$ressources = array();
if (count($ressources_ids)) {
  $ressource = new CRessourceCab();
  $where     = array(
    "ressource_cab_id" => CSQLDataSource::prepareIn($ressources_ids)
  );

  $ressources = $ressource->loadList($where);
}

$nb_days       = count($users) + count($ressources);
$bank_holidays = CMbDT::getHolidays($date);

$planning               = new CPlanningWeek(0, 0, $nb_days, $nb_days, false, "auto");
$planning->title        = CAppUI::tr("Planning-of") . " " . CMbDT::format($date, CAppUI::conf("longdate"));
$planning->guid         = "planning_j_n";
$planning->dragndrop    = 1;
$planning->hour_divider = 12;
$planning->show_half    = true;

$style = "color:black;
    font-size:1.3em;
    text-shadow: 0 0 10px white;";

$libelles_plages = CPlageconsult::getLibellesPref();
$i               = 0;
foreach ($users as $_user) {
  $see_notification = CModule::getActive("smsProviders") && count(CLotSms::loadForUser($_user, false)) ? 1 : 0;
  $user_id          = $_user->_id;

  // plages conge
  $conge   = new CPlageConge();
  $where   = array(
    "user_id" => "= '$_user->_id'"
  );
  $where[] = "'$date' BETWEEN DATE(date_debut) AND DATE(date_fin)";

  $nb_conges = $conge->countList($where);

  if ($nb_conges) {
    $conge->loadObject($where);
    $date_min_conge = max("$date 00:00:00", $conge->date_debut);
    $date_max_conge = min("$date 23:59:59", $conge->date_fin);

    $libelle = '<h3 style="text-align: center">
      ' . CAppUI::tr("CPlageConge|pl") . '</h3>
      <p style="text-align: center">' . $conge->libelle . '</p>';
    if ($conge->replacer_id) {
      $replacer = $conge->loadRefReplacer();
      $libelle  .= '<p style="text-align: center">' . CAppUI::tr("CConsultation.replaced_by") . ' : ' . $replacer->_view . '</p>';
    }

    $event        = new CPlanningEvent(
      $conge->_guid,
      "$i " . CMbDT::time($date_min_conge),
      CMbDT::minutesRelative($date_min_conge, $date_max_conge),
      $libelle,
      "#ffe87e",
      true,
      "hatching",
      null,
      false
    );
    $event->below = 1;
    $planning->addEvent($event);
  }

  $style .= $nb_conges ? "text-decoration: line-through;" : null;

  // add prat to the calendar
  $planning->addDayLabel(
    $i, "<span style=\"$style\">" . $_user->_view . "</span>", null, "#" . $_user->_color, "ObjectTooltip.createEx(this, '" . $_user->_guid . "');", false, array("user_id" => $user_id));

  // if no conges or we want to hide plage
  if ($nb_conges && $hide_in_conge) {
    $i++;
    continue;
  }

  // if public holiday, no watching plage
  if (array_key_exists($date, $bank_holidays) && !CAppUI::pref("show_plage_holiday")) {
    $i++;
    continue;
  }

  $where            = array();
  $where["chir_id"] = " = '$user_id'";
  $where["date"]    = " = '$date'";
  //Filtre sur le nom des plages de consultations
  if (count($libelles_plages)) {
    $where["libelle"] = CSQLDataSource::prepareIn($libelles_plages);
  }
  $plage  = new CPlageconsult();
  $plages = $plage->loadList($where, "date, debut");
  foreach ($plages as $_plage) {
    /** @var CPlageconsult $_plage */
    // range
    $range       = new CPlanningRange(
      $_plage->_guid,
      $i . " " . $_plage->debut,
      CMbDT::minutesRelative($_plage->debut, $_plage->fin),
      $_plage->libelle,
      $_plage->color
    );
    $range->type = "plageconsult";

    $planning->addRange($range);

    // consults libres
    if ($show_free) {
      $_plage->loadRefsConsultations(false);
      $utilisation = $_plage->getUtilisation();
      $_plage->colorPlanning($user_id);
      foreach ($utilisation as $_timing => $_nb) {
        if (!$_nb) {
          $heure              = CMbDT::format($_timing, "%H:%M");
          $debute             = "$i $_timing";
          $title              = "<strong>$heure</strong>";
          $event              = new CPlanningEvent($debute, $debute, $_plage->_freq, $title, $_plage->_color_planning, true, "droppable", null, false);
          $event->type        = "rdvfree";
          $event->plage["id"] = $_plage->_id;
          if ($_plage->locked == 1) {
            $event->disabled = true;
          }
          $event->plage["color"] = $_plage->color;
          //Ajout de l'évènement au planning
          $planning->addEvent($event);
        }
      }
    }

    // load consultations manually
    $consult                  = new CConsultation();
    $where                    = array();
    $where["plageconsult_id"] = " = '$_plage->_id' ";
    $where["annule"]          = " = '$cancelled'";
    if ($finished) {
      $where["chrono"] = " = '$finished'";
    }
    if ($facturated) {
      $where["facture"] = " = '1'";
    }
    /** @var CConsultation[] $consults */
    $consults = $consult->loadList($where, "heure");
    if ($see_notification) {
      CStoredObject::massLoadBackRefs($consults, "context_notifications");
    }
    $dossiers_anesth = CStoredObject::massLoadBackRefs($consults, "consult_anesth");
    CStoredObject::massLoadFwdRef($consults, "categorie_id");
    CMbObject::countAlertDocs($consults);
    CMbObject::countAlertDocs($dossiers_anesth);
    CMbObject::countLockedAlertDocs($consults);
    CMbObject::countLockedAlertDocs($dossiers_anesth);

    // consultations prises
    foreach ($consults as $_consult) {
      foreach ($_consult->_back["consult_anesth"] as $_consult_anesth) {
        $_consult->_alert_docs        += $_consult_anesth->_alert_docs;
        $_consult->_locked_alert_docs += $_consult_anesth->_locked_alert_docs;
      }
      $_consult->loadPosition();

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

      $debute = "$i $_consult->heure";
      $motif  = $_consult->motif;
      $heure  = CMbDT::format($_consult->heure, "%H:%M");
      $_consult->colorPlanning();

      // Display resources
      $res_title = "";
      $_consult->loadRefReservedRessources();
      $plage_resource_id = CStoredObject::massLoadFwdRef($_consult->_ref_reserved_ressources, "plage_ressource_cab_id");
      CStoredObject::massLoadFwdRef($plage_resource_id, "ressource_cab_id");
      foreach ($_consult->_ref_reserved_ressources as $_reserved) {
        $resource = $_reserved->loadRefPlageRessource()->loadRefRessource();

        $res_title .= '<span class="texticon me-margin-2" style="color: #'.$resource->color.'; border: 1px solid #'.$resource->color.';">';
        $res_title .= $resource->libelle;
        $res_title .= '</span>';
      }

      if ($_consult->patient_id) {
        $_consult->loadRefPatient();
        $_consult->loadRefCategorie();

        $title = "";
        if ($_consult->_consult_sejour_out_of_nb) {
          $title .= "<span style='float:right;'>$_consult->_consult_sejour_nb/ $_consult->_consult_sejour_out_of_nb</span>";
        }
        //Ajout du cartouche de DHE dans la vue journée du nouveau semainier
        $_consult->loadRefConsultAnesth();
        $_consult->checkDHE();
        if ($_consult->_alert_docs) {
          if ($_consult->_count["alert_docs"] == $_consult->_count["locked_alert_docs"]) {
            $title .= "<i class=\"far fa-file\" style=\"float: right; font-size: 1.3em;color:green;background-color:lightgreen;" . "\" " .
              "title=\"" . CAppUI::tr("CCompteRendu-alert_locked_docs_object.all") . "\"></i>";
          }
          else {
            $title .= "<i class=\"far fa-file\" style=\"float: right; font-size: 1.3em;" . "\" " .
              "title=\"" . CAppUI::tr("CCompteRendu-alert_docs_object") . "\"></i>";
          }
        }
        if ($_consult->_ref_consult_anesth && $_consult->_ref_consult_anesth->_id && $_consult->_etat_dhe_anesth) {
          if ($_consult->_etat_dhe_anesth == "associe") {
            $title .= "<span class=\"texticon texticon-allergies-ok\"";
            $title .= "title=\"" . CAppUI::tr("CConsultation-_etat_dhe_anesth-associe") . "\" style=\"float: right;\">";
            $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
          }
          elseif ($_consult->_etat_dhe_anesth == "dhe_exist") {
            $title .= "<span class=\"texticon texticon-atcd\"";
            $title .= "title=\"" . CAppUI::tr("CConsultation-_etat_dhe_anesth-dhe_exist") . "\" style=\"float: right;\">";
            $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
          }
          elseif ($_consult->_etat_dhe_anesth == "non_associe") {
            $title .= "<span class=\"texticon texticon-stup texticon-stroke\"";
            $title .= "title=\"" . CAppUI::tr("CConsultation-_etat_dhe_anesth-non_associe") . "\" style=\"float: right;\">";
            $title .= CAppUI::tr("COperation-event-dhe") . "</span>";
          }
        }

        if ($see_notification && $_consult->_ref_patient->allow_sms_notification) {
          $_consult->loadRefNotification();
          $title .= $_consult->smsPlaning();
        }

        $title .= "<strong>$heure</strong> ";

        if ($_consult->visite_domicile) {
          $title .= "<i class=\"fa fa-home\" style=\"font-size: 1.2em;\" " .
            "title=\"" . CAppUI::tr("CConsultation-visite_domicile-desc") . "\"></i> ";
        }

        $title .= $_consult->_ref_patient->_view . "\n" . $motif;

        // Display resources
        $title .= $res_title;

        $event = new CPlanningEvent(
          $_consult->_guid,
          $debute,
          $_consult->duree * $_plage->_freq,
          $title,
          $_consult->_color_planning,
          true,
          null,
          $_consult->_guid,
          false
        );
        $event->border_color = $_consult->_ref_categorie->couleur;
        $event->border_title = $_consult->_ref_categorie->nom_categorie;
      }
      else {
        $title = "[" . CAppUI::tr("CConsultation-" . ($_consult->no_patient ? "MEETING" : "PAUSE")) . "] $motif";
        // Display resources
        $title .= "\n $res_title";

        $event = new CPlanningEvent(
          $_consult->_guid,
          $debute, $_consult->duree * $_plage->_freq,
          $title,
          $_consult->_color_planning,
          true,
          null,
          null,
          false
        );
      }
      $event->type                = "rdvfull";
      $event->plage["id"]         = $_plage->_id;
      $event->plage["consult_id"] = $_consult->_id;
      if ($_plage->locked == 1) {
        $event->disabled = true;
      }

      $_consult->loadRefCategorie();
      if ($_consult->categorie_id) {
        $event->icon      = "./modules/dPcabinet/images/categories/" . $_consult->_ref_categorie->nom_icone;
        $event->icon_desc = $_consult->_ref_categorie->nom_categorie;
      }

      $can_edit = $_consult->canDo()->edit;
      if ($_consult->patient_id) {
        $event->draggable /*= $event->resizable */ = $can_edit;
        $event->hour_divider                       = 60 / CMbDT::minutesRelative("00:00:00", $_plage->freq);
      }

      if ($can_edit) {
        $event->addMenuItem("add", CAppUI::tr("CConsultation-add"));
        if ($_consult->chrono == 16) {
          $event->addMenuItem("tick", CAppUI::tr("CConsultation-notify_arrive-court"));
        }
        if ($_consult->chrono == 32) {
          $event->addMenuItem("tick_cancel", CAppUI::tr("CConsultation-cancel_arrive"));
        }

        if (!$_consult->annule) {
          $event->addMenuItem("cancel", CAppUI::tr("CConsultation-cancel_rdv"));
        }
        else {
          $event->addMenuItem("change", CAppUI::tr("Restore"));
        }
      }

      //Ajout de l'évènement au planning
      $event->plage["color"] = $_plage->color;
      $planning->addEvent($event);
    }
  }
  $i++;
}

if (count($ressources)) {
  $where = array("date" => "= '$date'");

  CStoredObject::massLoadBackRefs($ressources, "plages_cab", "date ASC", $where);

  /** @var CRessourceCab $_ressource */
  foreach ($ressources as $_ressource) {
    // add prat to the calendar
    $planning->addDayLabel(
      $i, "<span style=\"$style\">" . $_ressource->_view . "</span>", null, "#" . $_ressource->color, "ObjectTooltip.createEx(this, '" . $_ressource->_guid . "');", false, array("user_id" => $_ressource->_id));

    foreach ($_ressource->loadRefsPlages() as $_plage) {
      // range
      $range       = new CPlanningRange(
        $_plage->_guid,
        $i . " " . $_plage->debut,
        CMbDT::minutesRelative($_plage->debut, $_plage->fin),
        $_plage->libelle,
        $_plage->color
      );
      $range->type = "plageressource";

      $planning->addRange($range);

      $_plage->loadRefsReservations();

      foreach ($_plage->getUtilisation() as $_timing => $_nb) {
        if (!$_nb) {
          $heure              = CMbDT::format($_timing, "%H:%M");
          $debute             = "$i $_timing";
          $title              = "<strong>$heure</strong>";
          $event              = new CPlanningEvent($debute, $debute, $_plage->_freq, $title, "#94d2ff", true, "droppable", null, false);
          $event->type        = "resfree";
          $event->plage["id"] = $_plage->_id;
          //Ajout de l'évènement au planning
          $planning->addEvent($event);
        }
      }

      foreach ($_plage->loadRefsReservations() as $_reservation) {
        $heure              = CMbDT::format($_reservation->heure, "%H:%M");
        $debute             = "$i $_reservation->heure";
        $title              = "<strong>$heure</strong> [Réservé] " . $_reservation->motif;
        $event              = new CPlanningEvent(
          $debute,
          $debute,
          $_reservation->duree * $_plage->_freq,
          $title,
          "#ffbe9e",
          true,
          null,
          $_reservation->_guid,
          false
        );
        $event->type        = "resfull";
        $event->plage["id"] = $_plage->_id;
        $planning->addEvent($event);
      }
    }

    $i++;
  }
}

$planning->rearrange(true);

// Mise en surbrillance des créneaux en commun avec chacune des ressources affichées
if ($highlight) {
  $planning->highlight();
}

$planning->nb_week = CMbDT::weekNumber($date);

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("date", $date);
$smarty->assign("nday", CMbDT::date("+1 DAY", $date));
$smarty->assign("pday", CMbDT::date("-1 DAY", $date));
$smarty->assign("planning", $planning);
$smarty->assign("highlight", $highlight);
$smarty->assign("scroll_top", $scroll_top);
$smarty->assign("isCabinet", $is_cabinet);
$smarty->assign("height_calendar", CAppUI::pref("height_calendar", "2000"));
$smarty->display("inc_vw_journee_new");
