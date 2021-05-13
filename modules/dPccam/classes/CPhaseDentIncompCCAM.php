<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

/**
 * Class CPhaseDentIncompCCAM
 * Table p_phase_dentsincomp
 *
 * Dents incompatibles avec la phase de l'acte
 * Niveau phase
 */
class CPhaseDentIncompCCAM extends CCCAM {

  public $localisation;
  /** @var  CDentCCAM */
  public $_ref_dent;

  /**
   * Mapping des données depuis la base de données
   *
   * @param array $row Ligne d'enregistrement de de base de données
   *
   * @return void
   */
  function map($row) {
    $this->localisation = $row["LOCDENT"];
  }

  /**
   * Chargement de a liste des dents incompatibles pour une phase
   *
   * @param string $code     Code CCAM
   * @param string $activite Activité CCAM
   * @param string $phase    Phase CCAM
   *
   * @return self[] Liste des dents
   */
  static function loadListFromCodeActivitePhase($code, $activite, $phase) {
    $ds = self::$spec->ds;

    $query = "SELECT p_phase_dentsincomp.*
      FROM p_phase_dentsincomp
      WHERE p_phase_dentsincomp.CODEACTE = %1
      AND p_phase_dentsincomp.ACTIVITE = %2
      AND p_phase_dentsincomp.PHASE = %3";
    $query = $ds->prepare($query, $code, $activite, $phase);
    $result = $ds->exec($query);

    $list_dents = array();
    while ($row = $ds->fetchArray($result)) {
      $dent = new CPhaseDentIncompCCAM();
      $dent->map($row);
      $list_dents[] = $dent;
    }

    return $list_dents;
  }

  /**
   * Chargement de la dent concernée
   *
   * @return bool dent identifiable ou non
   */
  function loadRefDent() {
    $this->_ref_dent = new CDentCCAM();
    return $this->_ref_dent->load($this->localisation);
  }
}