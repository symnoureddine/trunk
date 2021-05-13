<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Relevés associés aux redons
 */
class CReleveRedon extends CMbObject {
  /** @var integer Primary key */
  public $releve_redon_id;

  // DB fields
  public $redon_id;
  public $date;
  public $user_id;
  public $qte_observee;
  public $vidange_apres_observation;
  public $constantes_medicales_id;

  // References
  /** @var CRedon */
  public $_ref_redon;

  /** @var CConstantesMedicales */
  public $_ref_constantes_medicales;

  // Form fields
  public $_qte_diff;
  public $_qte_cumul;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "releve_redon";
    $spec->key   = "releve_redon_id";
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["redon_id"]                  = "ref class|CRedon notNull back|releves";
    $props["date"]                      = "dateTime notNull";
    $props["user_id"]                   = "ref class|CMediusers back|releves_redons";
    $props["qte_observee"]              = "num pos notNull";
    $props["vidange_apres_observation"] = "bool";
    $props["constantes_medicales_id"]   = "ref class|CConstantesMedicales back|releve_redon";
    $props['_qte_diff']                 = "num";
    $props["_qte_cumul"]                = "num";
    return $props;
  }

  function store() {
    $this->completeField("qte_observee", "redon_id", "constantes_medicales_id", "date", "vidange_apres_observation");

    $redon = $this->loadRefRedon();

    $last_releve = $redon->loadRefLastReleve();

    if ($last_releve->_id && !$last_releve->vidange_apres_observation && $this->qte_observee < $last_releve->qte_observee) {
      return CAppUI::tr("CReleveRedon-Current quantity must be equal or higher than last quantity");
    }

    $creation = !$this->_id;

    if ($creation) {
      $this->user_id = CMediusers::get()->_id;
    }

    if (($this->date < $redon->date_pose) || ($redon->date_retrait && ($this->date > $redon->date_retrait))) {
      return CAppUI::tr("CReleveRedon-Out of borns");
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    return null;
  }

  /**
   * @inheritDoc
   */
  function delete() {
    $this->completeField("constantes_medicales_id");

    $releve_cste = $this->loadRefsConstantesMedicales();

    if ($msg = parent::delete()) {
      return $msg;
    }

    if ($releve_cste->_id && ($msg = $releve_cste->delete())) {
      return $msg;
    }

    return $msg;
  }

  /**
   * Charge le redon associé
   *
   * @return CRedon
   * @throws \Exception
   */
  public function loadRefRedon() {
    return $this->_ref_redon = $this->loadFwdRef("redon_id", true);
  }

  /**
   * Charge le relevé de constante associé
   *
   * @return CConstantesMedicales
   * @throws \Exception
   */
  public function loadRefsConstantesMedicales() {
    return $this->_ref_constantes_medicales = $this->loadFwdRef("constantes_medicales_id", true);
  }

  public function getQteCumul() {
    $redon = $this->loadRefRedon();

    $sejour = $redon->loadRefSejour();

    list($constante, $list_datetimes, $list_contexts) = CConstantesMedicales::getFor(
      $sejour->patient_id,
      $this->date,
      [$redon->constante_medicale],
      $sejour,
      false,
      null,
      "DESC",
      null,
      $this->date
    );

    return $this->_qte_cumul = $constante->{"_" . $redon->constante_medicale . "_cumul"};
  }
}
