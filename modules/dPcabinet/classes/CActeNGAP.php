<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Ccam\CActe;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CCodeNGAP;
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Facturation\CFactureItem;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSE;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSEAct;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\SalleOp\CActeCCAM;

/**
 * Actes NGAP concrets pouvant être associé à n'importe quel codable
 */
class CActeNGAP extends CActe {
  // DB key
  public $acte_ngap_id;

  // DB fields
  public $quantite;
  public $code;
  public $coefficient;
  public $taux_abattement;
  public $demi;
  public $complement;
  public $lettre_cle;
  public $lieu;
  public $exoneration;
  public $ald;
  public $numero_dent;
  public $comment;
  public $major_pct;
  public $major_coef;
  public $minor_pct;
  public $minor_coef;
  public $numero_forfait_technique;
  public $numero_agrement;
  public $rapport_exoneration;
  public $prescripteur_id;
  public $qualif_depense;
  public $accord_prealable;
  public $date_demande_accord;
  public $reponse_accord;
  public $prescription_id;
  public $other_executant_id;
  public $motif;
  public $motif_unique_cim;

  /** @var CCodeNGAP */
  public $_code;

  // Distant fields
  public $_libelle;

  // Tarif final
  public $_tarif;

  /** @var float The minimum value authorized for the coefficient */
  public $_min_coef;

  /** @var float The maximum value authorized for the coefficient */
  public $_max_coef;

  /** @var array The list of the forbidden complements for this act */
  public $_forbidden_complements;

  /** @var bool If true, a DEP might be needed */
  public $_dep;

  /** @var CMediusers */
  public $_ref_prescripteur;

  /** @var CPrescription */
  public $_ref_prescription;

  /** @var array A list of code that are executed at the patient's home */
  public static $codes_domicile = array(
    'V', 'VA', 'VL', 'VNP', 'VRS', 'VS', 'VU', 'MD', 'MDD', 'MDE', 'MDI', 'MDN', 'MED', 'MEI', 'MEN', 'MM', 'VG', 'VGS'
  );

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table  = 'acte_ngap';
    $spec->key    = 'acte_ngap_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props["object_id"]               .= " back|actes_ngap";
    $props["code"]                     = "str notNull maxLength|5";
    $props["quantite"]                 = "num notNull min|1 maxLength|2";
    $props["coefficient"]              = "float notNull min|0.1";
    $props['taux_abattement']          = 'float';
    $props["demi"]                     = "enum list|0|1 default|0";
    $props["complement"]               = "enum list|N|F|U";
    $props["lettre_cle"]               = "enum list|0|1 default|0";
    $props["lieu"]                     = "enum list|C|D default|C";
    $props["exoneration"]              = "enum list|N|3|7 default|N";
    $props["ald"]                      = "enum list|0|1 default|0";
    $props["numero_dent"]              = "num min|11 max|85";
    $props["comment"]                  = "str";
    $props["major_pct"]                = "num";
    $props["major_coef"]               = "float";
    $props["minor_pct"]                = "num";
    $props["minor_coef"]               = "float";
    $props["numero_forfait_technique"] = "num min|1 max|99999";
    $props["numero_agrement"]          = "num min|1 max|99999999999999";
    $props["rapport_exoneration"]      = "enum list|4|7|C|R";
    $props['prescripteur_id']          = 'ref class|CMediusers back|actes_ngap_prescrits';
    $props['qualif_depense']           = 'enum list|d|e|f|g|n|a|b|l';
    $props['accord_prealable']         = 'bool default|0';
    $props['date_demande_accord']      = 'date';
    $props['reponse_accord']           = 'enum list|no_answer|accepted|emergency|refused';
    $props["prescription_id"]          = "ref class|CPrescription back|actes_ngap";
    $props["executant_id"]            .= " back|actes_ngap_executes";
    $props["other_executant_id"]       = "ref class|CMedecin back|actes_ngap";
    $props["motif"]                    = "text helped";
    $props["motif_unique_cim"]         = "code cim10 show|0";
    $props['_tarif']                   = 'currency';

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    // Vue codée
    $this->_shortview = $this->quantite > 1 ? "{$this->quantite}x" : "";
    $this->_shortview.= $this->code;
    if ($this->coefficient != 1) {
      $this->_shortview.= $this->coefficient;
    }
    if ($this->demi) {
      $this->_shortview.= "/2";
    }

    $this->_view = "Acte NGAP $this->_shortview";
    if ($this->object_class && $this->object_id) {
      $this->_view .= " de $this->object_class-$this->object_id";
    }

    if ($this->_id) {
      $this->checkEntentePrealable();
      $this->getLibelle();
    }

    $this->_tarif = round((float)$this->montant_base + (float)$this->montant_depassement, 2);
  }

  /**
   * @see parent::updatePlainFields()
   */
  function updatePlainFields() {
    parent::updatePlainFields();

    if ($this->code) {
      $this->code = strtoupper($this->code);
    }
  }

  /**
   * Prepare un acte NGAP vierge en vue d'être associé à un codable
   *
   * @param CCodable   $codable   Codable ciblé
   * @param CMediusers $executant Un exécutant optionel
   *
   * @return CActeNGAP
   */
  static function createEmptyFor(CCodable $codable, $executant = null) {
    $acte = new self;
    $acte->setObject($codable);
    $acte->quantite    = 1;
    $acte->coefficient = 1;
    $acte->gratuit = '0';
    $acte->loadExecution();
    $acte->guessExecutant();

    if ($executant) {
      $acte->executant_id = $executant->_id;
      $acte->_ref_executant = $executant;
    }

    if (CAppUI::gconf('dPccam ngap prefill_prescriptor') && (($acte->object_class == 'CConsultation' && $acte->_ref_object->sejour_id)
        || ($acte->object_class == 'CSejour' && $acte->_ref_object->_id))
    ) {
      /** @var CSejour $sejour */
      $sejour = $acte->object_class == 'CConsultation' ? $acte->_ref_object->loadRefSejour() : $acte->_ref_object;
      $acte->prescripteur_id = $sejour->praticien_id;
      $acte->_ref_prescripteur = $sejour->loadRefPraticien();
    }

    if (($codable instanceof CConsultation && $codable->concerne_ALD === '1') || ($codable instanceof CSejour && $codable->ald === '1')) {
      $acte->ald = '1';
    }

    return $acte;
  }

  /**
   * @see parent::makeFullCode()
   */
  function makeFullCode() {
    return $this->_full_code =
      $this->quantite .
      "-" . $this->code .
      "-" . $this->coefficient .
      "-" . $this->montant_base .
      "-" . str_replace("-", "*", $this->montant_depassement) .
      "-" . $this->demi .
      "-" . $this->complement .
      "-" . $this->gratuit .
      "-" . $this->qualif_depense .
      '-' . $this->lieu .
      "-" . $this->exoneration;
  }

  /**
   * @inheritdoc
   */
  function setFullCode($code) {
    $details = explode("-", $code);

    $this->quantite    = $details[0];
    $this->code        = $details[1];
    $this->coefficient = $details[2];

    if (count($details) >= 4) {
      $this->montant_base = $details[3];
    }

    if (count($details) >= 5) {
      $this->montant_depassement = str_replace("*", "-", $details[4]);
    }

    if (count($details) >= 6) {
      $this->demi = $details[5];
    }

    if (count($details) >= 7) {
      $this->complement = $details[6];
    }

    if (count($details) >= 8) {
      $this->gratuit = $details[7];
    }

    if (count($details) >= 9) {
      $this->qualif_depense = $details[8];
    }

    if (count($details) >= 10) {
      $this->lieu = $details[9];
    }

    if (count($details) >= 11) {
      $this->exoneration = $details[10];
    }

    $this->getLibelle();
    if (!$this->lettre_cle) {
      $this->lettre_cle = 0;
    }

    $this->updateFormFields();
  }

  /**
   * @see parent::getPrecodeReady()
   */
  function getPrecodeReady() {
    return $this->quantite && $this->code && $this->coefficient;
  }

  /**
   * @see parent::check()
   */
  function check() {
    if ($msg = $this->checkCoded()) {
      return $msg;
    }

    if ($this->code) {
      $this->loadCode();

      /* Check if the act exists */
      if ($this->_code->_unknown) {
        return 'CActeNGAP-unknown';
      }

      /* Check if the act is deprecated */
      if ($this->_code->_deprecated) {
        CAppUI::setMsg('CActeNGAP-deprecated', UI_MSG_WARNING, $this->code);
      }
    }

    if ((in_array($this->complement, ['F', 'N']) || $this->code === 'MM') && $this->checkExclusiveModifiers()) {
      return $this->code === 'MM' ? CAppUI::tr('CActeNGAP-error-MM_exclusive_modifiers') : CAppUI::tr('CActeCCAM-error-NGAP_exclusive_modifiers');
    }

    return parent::check();
  }

  public function checkExclusiveModifiers(): bool
  {
    $ccam_acts = $this->getLinkedActesCCAM(true, true);
    foreach ($ccam_acts as $ccam_act) {
      if (count(array_intersect($ccam_act->_modificateurs, ['F', 'U', 'P', 'S']))) {
        return true;
      }
    }

    $ngap_acts = $this->getLinkedActesNGAP(true, true);
    foreach ($ngap_acts as $ngap_act) {
      if ($ngap_act->code === 'MM' || in_array($ngap_act->complement, ['N', 'U', 'F'])) {
        return true;
      }
    }

    return false;
  }

  /**
   * @see parent::store()
   */
  public function store() {
    // Chargement du oldObject

    if ($this->code == 'MTO') {
      return null;
    }

    $oldObject = new CActeNGAP();
    $oldObject->load($this->_id);

    /* Synchronization du champ gratuit et du motif de dépassement */
    if ($this->fieldModified('gratuit') || ($this->gratuit && !$this->_id)) {
      if ($this->gratuit) {
        $this->qualif_depense = 'g';
      }
      else {
        $this->qualif_depense = '';
      }
    }
    elseif ($this->fieldModified('qualif_depense') || ($this->qualif_depense && !$this->_id)) {
      if ($this->qualif_depense == 'g') {
        $this->gratuit = '1';
      }
      elseif ($this->_old && $this->_old->qualif_depense == 'g') {
        $this->gratuit = '0';
      }
    }

    $this->completeField('object_class');
    if ((!$this->_id || $this->fieldModified('execution')) && $this->object_class !== 'CModelCodage') {
      $this->completeField('code');
      $this->completeField('coefficient');
      $this->loadRefExecutant();
      $this->getForbiddenComplements();

      $date = CMbDT::date($this->execution);
      $time = CMbDT::time($this->execution);

      if ((((CMbDT::isHoliday($date) || CMbDT::format($date, '%w') === '0') && $time < '20:00:00' && $time >= '08:00:00')
            || ($this->_ref_executant->spec_cpam_id == 26 && CMbDT::format($date, '%w') === '6'
            && $time >= '12:00:00' && $time < '20:00:00'))
          && !in_array('F', $this->_forbidden_complements) && !$this->checkExclusiveModifiers()
          && ($this->coefficient >= 1 || is_null($this->coefficient))
      ) {
        $this->complement = 'F';
      }
      elseif ($this->complement == 'F') {
        $this->complement = '';
      }

      if ((($time >= '20:00:00' && $time <= '23:59:59') || ($time >= '06:00:00' && $time < '08:00:00'))
          && !in_array('N', $this->_forbidden_complements) && !$this->checkExclusiveModifiers()
          && ($this->coefficient >= 1 || is_null($this->coefficient))
      ) {
        $this->complement = 'N';
      }
      elseif ($this->complement == 'N') {
        $this->complement = '';
      }

      if (!$this->_id || $this->fieldModified('complement')) {
        $this->updateMontantBase();
      }
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    /* We create a link between the act and the fse in creation for the linked consultation */
    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation' && !$oldObject->_id) {
      $this->loadTargetObject();
      $fses = CPyxvitalFSE::loadForConsult($this->_ref_object);

      foreach ($fses as $_fse) {
        if ($_fse->state == 'creating') {
          $_link = new CPyxvitalFSEAct();
          $_link->fse_id = $_fse->_id;
          $_link->act_class = $this->_class;
          $_link->act_id = $this->_id;

          if ($msg = $_link->store()) {
            return $msg;
          }
        }
      }
    }

    return null;
  }

  /**
   * @see parent::delete()
   */
  public function delete() {
    /* We delete the links between the act and the fse that are in creation or cancelled */
    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation') {
      /** @var CPyxvitalFSEAct[] $fse_links */
      $fse_links = $this->loadBackRefs('fse_links');
      if ($fse_links) {
        foreach ($fse_links as $_link) {
          $_link->loadRefFSE();
          if ($_link->_ref_fse->state == 'creating' || $_link->_ref_fse->state == 'cancelled') {
            if ($msg = $_link->delete()) {
              return $msg;
            }
          }
        }
      }
    }

    return parent::delete();
  }

  /**
   * @see parent::canDeleteEx()
   */
  function canDeleteEx() {
    if ($msg = $this->checkCoded()) {
      return $msg;
    }

    $msg = parent::canDeleteEx();

    if ($msg) {
      return $msg;
    }

    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation') {
      /** @var CPyxvitalFSEAct[] $fse_links */
      $fse_links = $this->loadBackRefs('fse_links');
      if ($fse_links) {
        foreach ($fse_links as $_link) {
          $_link->loadRefFSE();
          if ($_link->_ref_fse->state != 'creating' && $_link->_ref_fse->state != 'cancelled') {
            $msg = CAppUI::tr('CMbObject-msg-nodelete-backrefs') . ': ' . count($fse_links) . ' ' . CAppUI::tr("CActe-back-fse_links");
          }
        }
      }
    }

    return $msg;
  }

  /**
   * Set the ist of forbidden complements
   *
   * @return void
   */
  function getForbiddenComplements() {
    $code = $this->loadCode();
    $code->getTarifFor($this->_ref_executant, CMbDT::date($this->execution));

    $this->_forbidden_complements = array();
    if ($code->_tarif) {
      if (!$code->_tarif->complement_ferie) {
        $this->_forbidden_complements[] = 'F';
      }
      if (!$code->_tarif->complement_nuit) {
        $this->_forbidden_complements[] = 'N';
      }
      if (!$code->_tarif->complement_urgence) {
        $this->_forbidden_complements[] = 'U';
      }
    }
  }

  /**
   * Calcule le montant de base de l'acte
   *
   * @return float
   */
  function updateMontantBase() {
    $this->loadRefExecutant();
    $this->_ref_executant->loadRefFunction();

    if ($this->gratuit) {
      return $this->montant_base = 0;
    }
    else {
      $code = $this->loadCode();
      $code->getTarifFor($this->_ref_executant, CMbDT::date($this->execution));

      if ($code->_tarif) {
        $this->_forbidden_complements = array();
        if (!$code->_tarif->complement_ferie) {
          $this->_forbidden_complements[] = 'F';
        }
        if (!$code->_tarif->complement_nuit) {
          $this->_forbidden_complements[] = 'N';
        }
        if (!$code->_tarif->complement_urgence) {
          $this->_forbidden_complements[] = 'U';
        }

        $this->_min_coef = $code->_tarif->coef_min;
        $this->_max_coef = $code->_tarif->coef_max;

        if ($this->coefficient == '' || $this->coefficient == 0) {
          $this->coefficient = 1;
        }

        if ($this->_min_coef && $this->coefficient < $this->_min_coef) {
          $this->coefficient = $this->_min_coef;
        }
        elseif ($this->_max_coef && $this->coefficient > $this->_max_coef) {
          $this->coefficient = $this->_max_coef;
        }

        if ($code->_tarif->entente_prealable) {
          $this->_dep = true;
        }

        $this->montant_base = $code->_tarif->tarif;
        $this->montant_base *= $this->coefficient;
        $this->montant_base *= $this->quantite;

        if ($this->demi) {
          $this->montant_base /= 2;
        }

        if ($this->complement == "F") {
          $this->montant_base += $code->_tarif->maj_ferie;
        }

        if ($this->complement == "N") {
          $this->montant_base += $code->_tarif->maj_nuit;
        }
      }
      else {
        $this->montant_base = 0;
      }
    }

    $this->montant_base = round($this->montant_base, 2, PHP_ROUND_HALF_UP);

    /* Gestion du taux d'abattement des indemnités kilométriques pour les infirmiers */
    if ($this->isIKInfirmier()) {
      $this->calculTauxAbattementIndemnitesKilometriques();
    }

    return $this->montant_base;
  }

  public function checkEntentePrealable() {
    $this->loadRefExecutant();
    $code = $this->loadCode();
    $code->getTarifFor($this->_ref_executant, CMbDT::date($this->execution));

    if ($code->_tarif && $code->_tarif->entente_prealable) {
      $this->_dep = $code->_tarif->entente_prealable;
    }
    else {
      $this->_dep = '0';
    }
  }

  /**
   * Produit le libellé NGAP complet de l'acte
   *
   * @return string
   */
  function getLibelle() {
    $this->loadCode();

    $this->_libelle = CAppUI::tr('CActeNGAP-Unknown or deleted act');

    if (!$this->_code->_unknown) {
      $this->_libelle = $this->_code->libelle;
      $this->lettre_cle = $this->_code->lettre_cle ? '1' : '0';
    }

    return $this->_libelle;
  }

  /**
   * Set the field Lieu depending on the act's code, and return it's value
   *
   * @return string
   */
  public function getLieu() {
    $this->lieu = 'C';

    if (in_array($this->code, self::$codes_domicile)) {
      $this->lieu = 'D';
    }

    return $this->lieu;
  }

  /**
   * Load the Code from the cache or the database
   *
   * @return CCodeNGAP
   */
  public function loadCode() {
    if (!$this->_code) {
      $this->_code = CCodeNGAP::get($this->code);
    }

    return $this->_code;
  }

  /**
   * Création d'un item de facture pour un code ngap
   *
   * @param CFacture $facture la facture
   *
   * @return string|null
   */
  function creationItemsFacture($facture) {
    $ligne = new CFactureItem();
    $ligne->libelle       = $this->_libelle;
    $ligne->code          = $this->code;
    $ligne->type          = $this->_class;
    $ligne->object_id     = $facture->_id;
    $ligne->object_class  = $facture->_class;
    $ligne->date          = CMbDT::date($this->execution);
    $ligne->montant_base  = $this->montant_base;
    $ligne->montant_depassement = $this->montant_depassement;
    $ligne->quantite      = $this->quantite;
    $ligne->coeff         = $this->coefficient;
    return $ligne->store();
  }

  /**
   * Load the prescriptor
   *
   * @return CMediusers
   */
  public function loadRefPrescripteur() {
    /** @var CMediusers $prescripteur */
    $prescripteur = $this->loadFwdRef('prescripteur_id', true);
    $prescripteur->loadRefFunction();
    return $this->_ref_prescripteur = $prescripteur;
  }

  /**
   * Load the related prescription
   *
   * @return CPrescription
   */
  public function loadRefPrescription() {
    return $this->_ref_prescription = $this->loadFwdRef("prescription_id", true);
  }

  /**
   * Vérifie si l'acte est une Indemnité kilométrique et que l'exécutant est un infirmier
   *
   * @return bool
   */
  public function isIKInfirmier() {
    $this->loadRefExecutant();
    if (in_array($this->code, ['IK', 'IKM', 'IKS']) && $this->_ref_executant->spec_cpam_id == 24) {
      return true;
    }

    return false;
  }

  /**
   * Calcule le taux d'abattement, ainsi que montant base de l'acte avec ce taux.
   *
   * @throws \Exception
   *
   * @return void
   */
  public function calculTauxAbattementIndemnitesKilometriques() {
    if (is_null($this->taux_abattement) || $this->taux_abattement === '') {
      $where = [
        'code'         => CSQLDataSource::prepareIn(['IK', 'IKS', 'IKM']),
        'executant_id' => " = $this->executant_id",
        "execution <= '$this->execution' AND execution >= '" . CMbDT::date($this->execution) . " 00:00:00'"
      ];

      if ($this->_id) {
        $where['acte_ngap_id'] = " != $this->acte_ngap_id";
      }

      $actes_ik = $this->loadList($where, 'execution ASC');

      $total_ik = 0;
      foreach ($actes_ik as $ik) {
        $total_ik += $ik->quantite;
      }

      /* Le calcul du taux d'abattement ne prend en compte que le 1er kilomètres de l'acte en cours */
      $total_ik++;

      /* Les différents taux d'abattements sont fournis dans la table 24 du CdC (ou dans l'Avenant 24) */
      if ($total_ik <= 299) {
        $this->taux_abattement = 1.00;
      }
      elseif ($total_ik <= 399) {
        $this->taux_abattement = 0.50;
      }
      else {
        $this->taux_abattement = 0.00;
      }
    }

    if ($this->taux_abattement === 0.50) {
      $this->montant_base *= 0.5;
    }
    elseif (in_array($this->taux_abattement, [0.00, '0'])) {
      $this->montant_base = 0;
      $this->gratuit = '1';
    }
  }

    /**
     * @param bool $same_executant
     * @param bool $same_day
     *
     * @return CActeCCAM[]
     * @throws \Exception
     */
    public function getLinkedActesCCAM(bool $same_executant = true, bool $same_day = false): array
    {
        $act = new CActeCCAM();

        $where = ['object_class' => " = '{$this->object_class}'", 'object_id' => " = '{$this->object_id}'"];

        if ($same_executant) {
            $where['executant_id'] = " = '{$this->executant_id}'";
        }

        if ($same_day) {
            $begin = CMbDT::format($this->execution, '%Y-%m-%d 00:00:00');
            $end = CMbDT::format($this->execution, '%Y-%m-%d 23:59:59');
            $where['execution'] = " BETWEEN '$begin' AND '$end'";
        }

        return $act->loadList($where);
    }

    /**
     * @param bool $same_executant
     * @param bool $same_day
     *
     * @return self[]
     * @throws \Exception
     */
    public function getLinkedActesNGAP(bool $same_executant = true, bool $same_day = false): array
    {
        $act = new self();

        $where = [
            'acte_ngap_id' => "<> '$this->_id'",
            'object_class' => " = '{$this->object_class}'",
            'object_id'    => " = '{$this->object_id}'"
        ];

        if ($same_executant) {
            $where['executant_id'] = " = '{$this->executant_id}'";
        }

        if ($same_day) {
            $begin = CMbDT::format($this->execution, '%Y-%m-%d 00:00:00');
            $end = CMbDT::format($this->execution, '%Y-%m-%d 23:59:59');
            $where['execution'] = " BETWEEN '$begin' AND '$end'";
        }

        return $act->loadList($where);
    }
}
