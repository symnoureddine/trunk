<?php
/**
 * @package Mediboard\Stats
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\CDiscipline;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Affichage du graphique de la réparition des patients par service
 *
 * @param string $debut         Début de la période
 * @param string $fin           Fin de la période
 * @param int    $prat_id       Filtre sur un praticien
 * @param int    $service_id    Filtre sur un service
 * @param string $type_adm      Filtre sur le type d'admission
 * @param int    $func_id       Filtre sur un cabinet
 * @param int    $discipline_id Filtre sur une discipline
 * @param int    $septique      Filtre sur les patients septiques
 * @param string $type_data     Choix du type de données
 *
 * @return array
 */
function graphPatParService(
  $debut = null, $fin = null,
  $prat_id = 0, $service_id = 0,
  $type_adm = "", $func_id = 0, $discipline_id = 0,
  $septique = 0, $type_data = "prevue"
) {
  if (!$debut) {
    $debut = CMbDT::date("-1 YEAR");
  }
  if (!$fin) {
    $fin = CMbDT::date();
  }

  $group_id = CGroups::loadCurrent()->_id;

  $prat = new CMediusers;
  $prat->load($prat_id);

  $discipline = new CDiscipline;
  $discipline->load($discipline_id);

  $ticks       = array();
  $serie_total = array(
    'label'   => 'Total',
    'data'    => array(),
    'markers' => array('show' => true),
    'bars'    => array('show' => false)
  );
  for ($i = $debut; $i <= $fin; $i = CMbDT::date("+1 MONTH", $i)) {
    $ticks[]               = array(count($ticks), CMbDT::transform("+0 DAY", $i, "%m/%Y"));
    $serie_total['data'][] = array(count($serie_total['data']), 0);
  }

  $where = array();
  if ($service_id) {
    $where["service_id"] = "= '$service_id'";
  }
  $service  = new CService();
  $services = $service->loadGroupList($where);

  $sejour     = new CSejour;
  $listHospis = array(
      1 => "Hospi complètes + ambu"
    ) + $sejour->_specs["type"]->_locales;

  $total  = 0;
  $series = array();

  // Patients placés

  foreach ($services as $service) {
    $serie = array(
      'data'  => array(),
      'label' => $service->nom
    );

    $query = "SELECT COUNT(DISTINCT sejour.sejour_id) AS total, service.nom AS nom,
      DATE_FORMAT(affectation.entree, '%m/%Y') AS mois,
      DATE_FORMAT(affectation.entree, '%Y%m') AS orderitem
      FROM sejour
      LEFT JOIN users_mediboard ON sejour.praticien_id = users_mediboard.user_id
      LEFT JOIN affectation ON sejour.sejour_id = affectation.sejour_id
      LEFT JOIN service ON affectation.service_id = service.service_id
      WHERE
        sejour.annule = '0' AND
        sejour.group_id = '$group_id' AND
        affectation.entree < '$fin 23:59:59' AND
        affectation.sortie > '$debut 00:00:00' AND
        service.service_id = '$service->_id'";

    if ($type_data == "reelle") {
      $query .= "\nAND sejour.entree_reelle BETWEEN  '$debut 00:00:00' AND '$fin 23:59:59'";
    }
    if ($prat_id) {
      $query .= "\nAND sejour.praticien_id = '$prat_id'";
    }
    if ($discipline_id) {
      $query .= "\nAND users_mediboard.discipline_id = '$discipline_id'";
    }
    if ($septique) {
      $query .= "\nAND sejour.septique = '$septique'";
    }

    if ($type_adm) {
      if ($type_adm == 1) {
        $query .= "\nAND (sejour.type = 'comp' OR sejour.type = 'ambu')";
      }
      else {
        $query .= "\nAND sejour.type = '$type_adm'";
      }
    }
    $query .= "\nGROUP BY mois ORDER BY orderitem";

    $result = $sejour->_spec->ds->loadlist($query);

    foreach ($ticks as $i => $tick) {
      $f = true;
      foreach ($result as $r) {
        if ($tick[1] == $r["mois"]) {
          $serie["data"][]            = array($i, $r["total"]);
          $serie_total["data"][$i][1] += $r["total"];
          $total                      += $r["total"];
          $f                          = false;
          break;
        }
      }
      if ($f) {
        $serie["data"][] = array(count($serie["data"]), 0);
      }
    }
    $series[] = $serie;
  }

  // Patients non placés

  if (!$service_id) {
    $serie = array(
      'data'  => array(),
      'label' => "Non placés"
    );

    $query = "SELECT COUNT(DISTINCT sejour.sejour_id) AS total, 'Non placés' AS nom,
      DATE_FORMAT(sejour.entree_$type_data, '%m/%Y') AS mois,
      DATE_FORMAT(sejour.entree_$type_data, '%Y%m') AS orderitem
      FROM sejour
      LEFT JOIN users_mediboard ON sejour.praticien_id = users_mediboard.user_id
      LEFT JOIN  affectation ON sejour.sejour_id = affectation.sejour_id
      WHERE 
        sejour.annule = '0' AND
        sejour.group_id = '$group_id' AND
        sejour.entree_$type_data < '$fin 23:59:59' AND
        sejour.sortie_$type_data > '$debut 00:00:00' AND

        affectation.affectation_id IS NULL";

    if ($prat_id) {
      $query .= "\nAND sejour.praticien_id = '$prat_id'";
    }
    if ($discipline_id) {
      $query .= "\nAND users_mediboard.discipline_id = '$discipline_id'";
    }
    if ($septique) {
      $query .= "\nAND sejour.septique = '$septique'";
    }

    if ($type_adm) {
      if ($type_adm == 1) {
        $query .= "\nAND (sejour.type = 'comp' OR sejour.type = 'ambu')";
      }
      else {
        $query .= "\nAND sejour.type = '$type_adm'";
      }
    }
    $query .= "\nGROUP BY mois ORDER BY orderitem";

    $resultNP = $sejour->_spec->ds->loadlist($query);

    foreach ($ticks as $i => $tick) {
      $f = true;
      foreach ($resultNP as $r) {
        if ($tick[1] == $r["mois"]) {
          $serie["data"][]            = array($i, $r["total"]);
          $serie_total["data"][$i][1] += $r["total"];
          $total                      += $r["total"];
          $f                          = false;
          break;
        }
      }
      if ($f) {
        $serie["data"][] = array(count($serie["data"]), 0);
      }
    }
    $series[] = $serie;
  }

  $series[] = $serie_total;

  $subtitle = "$total passages";
  if ($prat_id) {
    $subtitle .= " - Dr $prat->_view";
  }
  if ($discipline_id) {
    $subtitle .= " - $discipline->_view";
  }
  if ($type_adm) {
    $subtitle .= " - " . $listHospis[$type_adm];
  }
  if ($septique) {
    $subtitle .= " - Septiques";
  }

  $options = array(
    'title'       => "Nombre de patients par service - $type_data",
    'subtitle'    => $subtitle,
    'xaxis'       => array('labelsAngle' => 45, 'ticks' => $ticks),
    'yaxis'       => array('min' => 0, 'autoscaleMargin' => 5),
    'bars'        => array('show' => true, 'stacked' => true, 'barWidth' => 0.8),
    'HtmlText'    => false,
    'legend'      => array('show' => true, 'position' => 'nw'),
    'grid'        => array('verticalLines' => false),
    'spreadsheet' => array(
      'show'             => true,
      'csvFileSeparator' => ';',
      'decimalSeparator' => ',',
      'tabGraphLabel'    => 'Graphique',
      'tabDataLabel'     => 'Données',
      'toolbarDownload'  => 'Fichier CSV',
      'toolbarSelectAll' => 'Sélectionner tout le tableau'
    )
  );

  return array('series' => $series, 'options' => $options);
}
