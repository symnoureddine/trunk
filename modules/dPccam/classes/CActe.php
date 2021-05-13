<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSEAct;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Classe non persistente d'acte pouvant �tre associ�es � un codable
 *
 * @see CCodable
 */
class CActe extends CMbObject {
  public $montant_depassement;
  public $montant_base;
  public $execution;
  public $gratuit;

  // DB References
  public $executant_id;
  public $facturable;
  public $num_facture;

  // Meta
  public $object_id;
  public $object_class;
  public $_ref_object;

  // Form fields
  public $_preserve_montant;
  public $_montant_facture;

  // Derived fields
  public $_full_code;

  // Behaviour fields
  public $_check_coded = true;
  /** @var boolean If true, use less constraints on the creation of an act (such as checks on sejour or consultation dates) */
  public $_permissive;
  public $_no_synchro_eai = false;
  public $_delete;

  /* Indicate that the execution of the act is in a billing period */
  public $_billed = false;

  // Distant object
  /** @var CSejour */
  public $_ref_sejour;
  /** @var CPatient */
  public $_ref_patient;
  /** @var CMediusers Probable user */
  public $_ref_praticien;
  /** @var CMediusers Actual user */
  public $_ref_executant;

  /** @var CMediusers[] */
  public $_list_executants;

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_montant_facture = (float)$this->montant_base + (float)$this->montant_depassement;

    if ($this->_id) {
      $this->_billed = ($this->checkBillingPeriods() !== null);
    }
  }

  /**
   * Charge le s�jour associ�
   *
   * @return CSejour
   */
  function loadRefSejour() {
    /** @var CCodable $object */
    if (null == $object = $this->loadTargetObject()) {
      return null;
    }

    return $this->_ref_sejour = $object->loadRefSejour();
  }

  /**
   * Charge le patient associ�
   *
   * @return CPatient
   */
  function loadRefPatient() {
    /** @var CCodable $object */
    if (null == $object = $this->loadTargetObject()) {
      return null;
    }

    return $this->_ref_patient = $object->loadRefPatient();
  }

  /**
   * Charge le praticien associ�
   *
   * @return CMediusers
   */
  function loadRefPraticien() {
    /** @var CCodable $object */
    if (null == $object = $this->loadTargetObject()) {
      return null;
    }

    return $this->_ref_praticien = $object->loadRefPraticien();
  }

  /**
   * Charge l'ex�cutant associ�
   *
   * @return CMediusers
   */
  function loadRefExecutant() {
    /** @var CMediusers $executant */
    $executant = $this->loadFwdRef("executant_id", true);
    $executant->loadRefFunction();

    return $this->_ref_executant = $executant;
  }

  /**
   * Charge les ex�cutants possibles
   *
   * @return CMediusers[]|null Ex�cutants possible, null si ex�cutant d�termin�
   */
  function loadListExecutants() {
    $user                   = CMediusers::get();
    $this->_list_executants = $user->loadProfessionnelDeSante(PERM_READ);

    // No executant guess for the existing acte
    if ($this->executant_id || $this->_id) {
      return null;
    }

    // User executant
    if ((CAppUI::pref("user_executant") || CAppUI::gconf('dPccam codage rights') == 'self')
      && $user->isProfessionnelDeSante()
    ) {
      $this->executant_id = $user->_id;

      return null;
    }

    // Referring pratician executant
    $praticien = $this->loadRefPraticien();
    if ($praticien && $praticien->_id) {
      $this->executant_id = $praticien->_id;

      return null;
    }

    return $this->_list_executants;
  }

  /**
   * Renseigne l'executant
   *
   * @return void
   */
  function guessExecutant() {
    $user = CMediusers::get();

    // No executant guess for the existing acte
    if ($this->executant_id || $this->_id) {
      return;
    }

    // User executant
    if ((CAppUI::pref("user_executant") || CAppUI::gconf('dPccam codage rights') == 'self')
      && $user->isProfessionnelDeSante()
    ) {
      if ($user->loadRefRemplacant($this->execution)) {
        $user = $user->_ref_remplacant;
      }
      $this->executant_id   = $user->_id;
      $this->_ref_executant = $user;

      return;
    }

    // Referring pratician executant
    $praticien = $this->loadRefPraticien();
    if ($praticien && $praticien->_id) {
      if ($praticien->loadRefRemplacant($this->execution)) {
        $praticien = $praticien->_ref_remplacant;
      }
      $this->executant_id   = $praticien->_id;
      $this->_ref_executant = $praticien;

      return;
    }
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props["object_id"]    = "ref notNull class|CCodable meta|object_class";
    $props["object_class"] = "str notNull class show|0";

    $props["executant_id"]        = "ref notNull class|CMediusers";
    $props["montant_base"]        = "currency";
    $props["montant_depassement"] = "currency";
    $props["execution"]           = "dateTime notNull";
    $props["facturable"]          = "bool notNull default|1 show|0";
    $props["num_facture"]         = "num notNull min|1 default|1";
    $props['gratuit']             = 'bool notNull default|0';

    $props["_montant_facture"] = "currency";

    return $props;
  }

  /**
   * Check if linked object is already coded
   *
   * @return string|null Error message, null when succesfull
   */
  function checkCoded() {
    if (!$this->_check_coded || $this->_forwardRefMerging) {
      return null;
    }

    $this->completeField("object_class");
    $this->completeField("object_id");
    /** @var CCodable $object */
    $object = new $this->object_class;
    $object->load($this->object_id);
    if ($object->_coded == "1") {
      return CAppUI::tr($object->_class) . " d�j� valid�e : Impossible de coter l'acte";
    }

    return null;
  }

  /**
   * Tell wether acte is ready for precoding
   *
   * @return bool
   */
  function getPrecodeReady() {
    return false;
  }

  /**
   * Return a full serialised code
   *
   * @return string Serialised full code
   */
  function makeFullCode() {
    return $this->_full_code = "";
  }

  /**
   * Precode with a full serialised code for the act
   *
   * @param string $code Serialised full code
   *
   * @return void
   */
  function setFullCode($code) {
  }

  /**
   * Update montant
   *
   * @return string|null Error message
   */
  function updateMontant() {
    if ($this->_preserve_montant || $this->_forwardRefMerging) {
      return null;
    }

    /** @var CCodable $object */
    $object = new $this->object_class;
    $object->load($this->object_id);

    // Permet de mettre a jour le montant dans le cas d'une consultation
    return $object->doUpdateMontants();
  }

  /**
   * Calcule le montant de base de l'acte
   *
   * @return float
   */
  function updateMontantBase() {

  }

  /**
   * Charge l'ex�cution
   *
   * @return void
   */
  function loadExecution() {
    /** @var CCodable $object */
    $object = $this->loadTargetObject();
    $object->getActeExecution();
    $this->execution = CAppUI::pref("use_acte_date_now") ? "now" : CMbDT::format($object->_acte_execution, '%Y-%m-%d %H:%M:00');
  }

  /**
   * @see parent::store()
   */
  function store() {
    $this->loadTargetObject();

    if (!$this->_permissive && !$this->_forwardRefMerging) {
      $dateTime_execution = CMbDT::dateTime(null, $this->execution);
      switch ($this->object_class) {
        case 'COperation':
          $sejour = $this->_ref_object->loadRefSejour();
          $this->_ref_object->loadRefsBillingPeriods();
          $sejour->loadRefsBillingPeriods();
          $date = CMbDT::date(null, $this->execution);
          if (($date > CMbDT::date('+2 day', $this->_ref_object->date)
            || $date < CMbDT::date('-1 day', $this->_ref_object->date))
          ) {
            return CAppUI::tr("CActe-error-execution_out_of_boundary-$this->object_class");
          }
          elseif ($dateTime_execution < CMbDT::format($sejour->entree, '%Y-%m-%d %H:%M:00')
            || $dateTime_execution > CMbDT::format($sejour->sortie, '%Y-%m-%d %H:%M:00')
          ) {
            return CAppUI::tr("CActe-error-execution_out_of_boundary-CSejour");
          }
          break;
        case 'CSejour':
          $this->_ref_object->loadRefsBillingPeriods();
          if ($dateTime_execution < CMbDT::format($this->_ref_object->entree, '%Y-%m-%d %H:%M:00')
            || ((($this->_ref_object->sortie_reelle && CAppUI::gconf("dPccam codage block_with_real_sejour_dates"))
                || (!CAppUI::gconf("dPccam codage block_with_real_sejour_dates")))
              && $dateTime_execution > CMbDT::format($this->_ref_object->sortie, '%Y-%m-%d %H:%M:00'))
          ) {
            return CAppUI::tr("CActe-error-execution_out_of_boundary-$this->object_class");
          }
          break;
        default:
      }

      if ($msg = $this->checkBillingPeriods()) {
        return $msg;
      }
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    return $this->updateMontant();
  }

  /**
   * @see parent::delete()
   */
  function delete() {
    if (CModule::getActive('oxPyxvital')) {
      /** @var CPyxvitalFSEAct[] $fse_acts */
      $fse_acts = $this->loadBackRefs('fse_links');
      foreach ($fse_acts as $fse_act) {
        $fse = $fse_act->loadRefFSE();
        if ($fse->state == 'cancelled' || $fse->state == 'creating') {
          $fse_act->delete();
        }
      }
    }

    if ($msg = $this->checkBillingPeriods()) {
      return $msg;
    }

    if ($msg = parent::delete()) {
      return $msg;
    }

    if (!$this->_purge) {
      return $this->updateMontant();
    }

    return null;
  }

  /**
   * V�rifie si l'acte peut �tre cod� en fonction des p�riodes de facturation du s�jour
   *
   * @return string|null
   */
  public function checkBillingPeriods() {
    $this->loadTargetObject();

    switch ($this->_ref_object && $this->_ref_object->_class) {
      case 'CConsultation':
      case 'COperation':
        $sejour = $this->_ref_object->loadRefSejour();
        break;
      case 'CSejour':
        $sejour = $this->_ref_object;
        break;
      default:
        $sejour = new CSejour();
    }

    if ($sejour && $sejour->_id) {
      $sejour->loadRefsBillingPeriods();
      if (CCodable::hasBillingPeriods($sejour)) {
        /** @var CBillingPeriod $_billing_period */
        foreach ($sejour->_ref_billing_periods as $_billing_period) {
          if ($_billing_period->period_statement != '0'
            && $this->execution > CMbDT::format($_billing_period->period_start, '%Y-%m-%d 00:00:00')
            && $this->execution < CMbDT::format($_billing_period->period_end, '%Y-%m-%d 23:59:59')
          ) {
            return CAppUI::tr(
              "CActe-error-execution_in_billing_period-CSejour-period_statement.{$_billing_period->period_statement}",
              CMbDT::dateToLocale($_billing_period->period_start),
              CMbDT::dateToLocale($_billing_period->period_end)
            );
          }
        }
      }
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  function isExportable($prat_ids = array(), $date_min = null, $date_max = null) {
    return !$prat_ids || !in_array($this->executant_id, $prat_ids);
  }



  /**
   * @param CStoredObject $object
   * @deprecated
   * @todo redefine meta raf
   * @return void
   */
  public function setObject(CStoredObject $object) {
    CMbMetaObjectPolyfill::setObject($this, $object);
  }

  /**
   * @param bool $cache
   * @deprecated
   * @todo redefine meta raf
   * @return mixed
   * @throws Exception
   */
  public function loadTargetObject($cache = true) {
    return CMbMetaObjectPolyfill::loadTargetObject($this, $cache);
  }

  /**
   * @inheritDoc
   * @todo remove
   */
  function loadRefsFwd() {
    parent::loadRefsFwd();
    $this->loadTargetObject();
  }
}
