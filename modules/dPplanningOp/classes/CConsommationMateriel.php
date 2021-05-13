<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp;

use Ox\Core\CMbObject;
use Ox\Mediboard\Dmi\CDMSterilisation;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Stock\CProductOrderItemReception;


/**
 * Consommation des matériels de bloc
 */
class CConsommationMateriel extends CMbObject {
  /** @var integer Primary key */
  public $consommation_materiel_id;

  // DB fields
  public $materiel_operatoire_id;
  public $datetime;
  public $user_id;
  public $qte_consommee;
  public $lot_id;

  // References
  /** @var CMaterielOperatoire */
  public $_ref_materiel_operatoire;

  /** @var CMediusers */
  public $_ref_user;

  /** @var CProductOrderItemReception */
  public $_ref_lot;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "consommation_materiel";
    $spec->key   = "consommation_materiel_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["materiel_operatoire_id"] = "ref class|CMaterielOperatoire notNull back|consommation";
    $props["datetime"]               = "dateTime notNull";
    $props["user_id"]                = "ref class|CMediusers notNull back|consommations_materiel";
    $props["qte_consommee"]          = "num default|1";
    $props["lot_id"]                 = "ref class|CProductOrderItemReception back|consommations";
    return $props;
  }

  /**
   * @inheritDoc
   */
  function store() {
    $this->completeField("materiel_operatoire_id", "qte_consommee");

    $new = !$this->_id;

    if (!$this->_id || $this->fieldModified("qte_consommee")) {
      $this->user_id  = CMediusers::get()->_id;
      $this->datetime = "current";
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    // Création d'une déstérilisation
    $msg = $new ? $this->createDesterilisation() : null;

    return $msg;
  }

  /**
   * Charge le matériel opératoire
   *
   * @return CMaterielOperatoire
   */
  public function loadRefMaterielOperatoire() {
    return $this->_ref_materiel_operatoire = $this->loadFwdRef("materiel_operatoire_id", true);
  }

  /**
   * Charge l'utilisateur associé à la consommation
   *
   * @return CMediusers
   */
  public function loadRefUser() {
    return $this->_ref_user = $this->loadFwdRef("user_id", true);
  }

  /**
   * Charge le lot associé à la consommation
   *
   * @return CProductOrderItemReception
   */
  public function loadRefLot() {
    return $this->_ref_lot = $this->loadFwdRef("lot_id", true);
  }

  /**
   * Création d'une déstérilisation seulement pour les produits implantables
   */
  public function createDesterilisation() {
    $materiel_operatoire = $this->loadRefMaterielOperatoire();
    $dm = $materiel_operatoire->loadRefDM();

    if ($dm->type_usage !== "sterilisable") {
      return null;
    }

    $sterilisation = new CDMSterilisation();
    $sterilisation->consommation_id = $this->_id;
    $sterilisation->operation_id = $materiel_operatoire->operation_id;
    $sterilisation->date_desterilisation = "current";

    return $sterilisation->store();
  }
}
