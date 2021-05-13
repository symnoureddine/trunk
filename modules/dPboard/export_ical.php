<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Core\FileUtil\CMbCalendar;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Bloc\CSalle;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;

/**
 * Turn a iso time to a string representation for iCal exports
 *
 * @param string $time Time to convert
 *
 * @return string
 */

CCanDo::checkRead();

// Récupération des paramètres
$prat_id      = CView::get("prat_id", "ref class|CMediusers default|" . CMediusers::get()->_id);
$group        = CView::get("group", "enum list|0|1 default|0");
$details      = CView::get("details", "bool default|1");
$anonymize    = CView::get("anonymize", "bool");
$export       = CValue::get("export", array("consult"));
$weeks_before = CView::get("weeks_before", "num default|1");
$weeks_after  = CView::get("weeks_after", "num default|4");

$date = CView::get("date", "date default|now");

CView::checkin();
CView::enableSlave();

/**
 * Group:
 * 0 - No grouping
 * 1 - Grouping per day
 */

$debut = CMbDT::date("-$weeks_before week", $date);
$debut = CMbDT::date("last sunday", $debut);
$fin   = CMbDT::date("+$weeks_after week", $date);
$fin   = CMbDT::date("next sunday", $fin);

$praticien = CMediusers::get($prat_id);
$praticien->needsEdit();

$listDays = array();

$plageConsult = new CPlageconsult();
/** @var CPlageconsult[] $plagesConsult */
$plagesConsult = array();

/** @var CSalle[] $listSalles */
$salle          = new CSalle();
$listSalles     = $salle->loadGroupList();
$plageOp        = new CPlageOp();
$plagesOp       = array();
$plagesPerDayOp = array();

for ($i = 0; CMbDT::date("+$i day", $debut) != $fin; $i++) {
  $date = CMbDT::date("+$i day", $debut);

  if (in_array("consult", $export)) {

    $where            = array();
    $where["chir_id"] = "= '$prat_id'";
    $where["date"]    = "= '$date'";
    /** @var CPlageconsult[] $plagesPerDayConsult */
    $plagesPerDayConsult = $plageConsult->loadList($where);

    CStoredObject::massLoadBackRefs($plagesPerDayConsult, "consultations", 'heure ASC');

    foreach ($plagesPerDayConsult as $key => $plageConsult) {
      $plageConsult->countPatients();
      $plageConsult->loadFillRate();
      $plageConsult->loadRefsConsultations(false);
    }

    $plagesConsult[$date] = $plagesPerDayConsult;
  }

  if (in_array("interv", $export)) {

    $where             = array();
    $where[]           = "chir_id = '$prat_id' OR anesth_id = '$prat_id'";
    $where["date"]     = "= '$date'";
    $where["salle_id"] = CSQLDataSource::prepareIn(array_keys($listSalles));
    /** @var CPlageOp[] $plagesPerDayOp */
    $plagesPerDayOp = $plageOp->loadList($where);

    $salles = CStoredObject::massLoadFwdRef($plagesPerDayOp, "salle_id");
    CStoredObject::massLoadFwdRef($salles, "bloc_id");

    CStoredObject::massLoadBackRefs($plagesPerDayOp, "operations", "rank, time_operation, rank_voulu, horaire_voulu");

    foreach ($plagesPerDayOp as $key => $plage) {
      $plage->loadRefSalle();
      $plage->_ref_salle->loadRefBloc();
      $plage->_ref_salle->_ref_bloc->loadRefGroup();

      $plage->loadRefsOperations(false);

      $sejours = CStoredObject::massLoadFwdRef($plage->_ref_operations, "sejour_id");
      CStoredObject::massLoadFwdRef($sejours, "patient_id");

      $plage->multicountOperations();
      $plagesOp[$plage->salle_id][$date][] = $plage;
    }
  }
}

function icaltime($time) {
  list($hour, $min) = explode(":", $time);

  return "{$hour}h{$min}";
}

// Création du calendrier
$v = new CMbCalendar("Planning");

// Création des évènements plages de consultation
if (in_array("consult", $export)) {
  foreach ($plagesConsult as $_date => $plagesPerDay) {
    if (!$plagesPerDay) {
      continue;
    }

    switch ($group) {
      case '0':
        foreach ($plagesPerDay as $rdv) {
          $description = "$rdv->_nb_patients patient(s)";

          // Evènement détaillé
          if ($details) {
            foreach ($rdv->_ref_consultations as $consult) {
              if ($consult->annule) {
                continue;
              }

              /** @var CConsultation $consult */
              $when = icaltime($consult->heure);

              if ($anonymize) {
                $what = ($consult->motif) ?: CAppUI::tr($consult->_class);
              }
              else {
                $patient = $consult->loadRefPatient();
                $what    = $patient->_id ? "$patient->_civilite $patient->nom" : "Pause: $consult->motif";
              }

              $description .= "\n$when: $what";
            }
          }

          $deb = "$rdv->date $rdv->debut";
          $fin = "$rdv->date $rdv->fin";
          $v->addEvent("", "Consultation - $rdv->libelle", $description, null, $rdv->_guid, $deb, $fin);
        }
        break;

      case '1':
        $deb = "{$_date} " . min(CMbArray::pluck($plagesPerDay, 'debut'));
        $fin = "{$_date} " . max(CMbArray::pluck($plagesPerDay, 'fin'));

        $_guid = 'CPlageconsult-' . implode('-', CMbArray::pluck($plagesPerDay, '_id'));

        $summary     = '';
        $description = '';
        foreach ($plagesPerDay as $_plage) {
          $_debut = icaltime($_plage->debut);
          $_fin   = icaltime($_plage->fin);

          $summary .= "\n[{$_debut} - {$_fin}] " . ($_plage->libelle) ?: CAppUI::tr($_plage->_class);

          /** @var CConsultation $_consult */
          foreach ($_plage->_ref_consultations as $_consult) {
            if ($_consult->annule) {
              continue;
            }

            $description .= "\n[" . icaltime($_consult->heure) . '] ';

            if (!$_consult->patient_id) {
              $description .= 'PAUSE';
            }
            else {
              $description .= (($_consult->motif) ?: CAppUI::tr($_consult->_class));

              if ($details && !$anonymize) {
                $patient     = $_consult->loadRefPatient();
                $description .= " : {$patient->_civilite} {$patient->nom}";
              }
            }
          }
        }
        $summary     = trim($summary);
        $description = trim($description);

        $v->addEvent("", $summary, $description, null, $_guid, $deb, $fin);
        break;

      default:
    }
  }
}

// Création des évènements plages d'interventions
if (in_array("interv", $export)) {
  switch ($group) {
    case '0':
      foreach ($plagesOp as $salle) {
        foreach ($salle as $plagesPerDay => $_plages) {
          foreach ($_plages as $_plage_id => $rdv) {
            $description = "{$rdv->_count_operations} intervention(s)";

            // Evènement détaillé
            if ($details) {
              foreach ($rdv->_ref_operations as $op) {
                if ($op->annulee) {
                  continue;
                }

                /** @var COperation $op */
                $op->loadRefPatient();
                $op->loadRefPlageOp();
                $duration = icaltime($op->temp_operation);
                $when     = icaltime(CMbDT::time($op->_datetime));

                $what = ($op->libelle) ?: CAppUI::tr($op->_class);

                if (!$anonymize) {
                  $what .= " {$op->_ref_patient->_view}";
                }

                $description .= "\n$when: $what (duree: $duration)";
              }
            }

            $deb = "$rdv->date $rdv->debut";
            $fin = "$rdv->date $rdv->fin";

            $location = $rdv->_ref_salle->_ref_bloc->_ref_group->_view;
            $v->addEvent($location, $rdv->_ref_salle->_view, $description, null, $rdv->_guid, $deb, $fin);
          }
        }
      }
      break;

    case '1':
      foreach ($plagesOp as $_salle_id => $salle) {
        $_salle = new CSalle();
        $_salle->load($_salle_id);

        foreach ($salle as $plagesPerDay => $_plages) {
          $deb       = "{$plagesPerDay} " . min(CMbArray::pluck($_plages, 'debut'));
          $fin       = "{$plagesPerDay} " . max(CMbArray::pluck($_plages, 'fin'));
          $nb_interv = array_sum(CMbArray::pluck($_plages, '_count_operations'));
          $_guid     = 'CPlageOp-' . implode('-', CMbArray::pluck($_plages, '_id'));

          $summary     = '';
          $description = '';

          foreach ($_plages as $_plage_id => $rdv) {
            $_debut = icaltime($rdv->debut);
            $_fin   = icaltime($rdv->fin);

            $summary .= "\n[{$_debut} - {$_fin}] {$_salle->_view}";

            // Evènement détaillé
            if ($details) {
              foreach ($rdv->_ref_operations as $op) {
                if ($op->annulee) {
                  continue;
                }

                /** @var COperation $op */
                $op->loadRefPatient();
                $op->loadRefPlageOp();
                $duration = icaltime($op->temp_operation);
                $when     = icaltime(CMbDT::time($op->_datetime));

                $what = ($op->libelle) ?: CAppUI::tr($op->_class);

                if (!$anonymize) {
                  $what .= " {$op->_ref_patient->_view}";
                }

                $description .= "\n$when: $what (duree: $duration)";
              }
            }
          }

          $summary     = trim($summary);
          $description = trim($description);

          $location = $_salle->loadRefBloc()->loadRefGroup()->_view;
          $v->addEvent($location, $summary, $description, null, $_guid, $deb, $fin);
        }
      }
      break;

    default:
  }
}

// Conversion du calendrier en champ texte
$str = $v->createCalendar();

//echo "<pre>$str</pre>"; return;

header("Content-disposition: attachment; filename=agenda.ics");
header("Content-Type: text/calendar; charset=" . CApp::$encoding);
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Content-Length: " . strlen($str));
echo $str;
