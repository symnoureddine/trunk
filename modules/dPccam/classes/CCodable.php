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
use Ox\Core\CMbArray;
use Ox\Core\CMbDay;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Core\FieldSpecs\CStrSpec;
use Ox\Core\FieldSpecs\CTextSpec;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CTarif;
use Ox\Mediboard\Lpp\CActeLPP;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\SalleOp\CActeCCAM;
use Ox\Mediboard\Tarmed\CActeCaisse;
use Ox\Mediboard\Tarmed\CActeTarmed;

/**
 * Classe non persistente permettant d'associer des manières abstraites des collections d'actes
 *
 * @see CActe
 */
class CCodable extends CMbObject {
  static $possible_actes_lite = false;
  public $codes_ccam;
  /** @var bool Séjour facturé ou non */
  public $facture;
  public $tarif;
  public $exec_tarif;
  public $consult_related_id;

  // Form fields
  public $_acte_execution;
  public $_acte_depassement;
  public $_acte_depassement_anesth;
  public $_anesth;
  public $_associationCodesActes;
  public $_count_actes;
  public $_actes_non_cotes;
  public $_datetime;
  public $_guess_status;    //0 => no chance, 1 => good date, 2=> 1 + good function_id, 3 => 2 + Good praticien
  public $_docitems_guid;

  public $_check_bounds = true;

  // Tarif
  public $_bind_tarif;
  public $_tarif_id;
  public $_tarif_user_id;

  // Abstract fields
  public $_praticien_id;
  /** @var bool Initialisation à 0 => codable qui peut etre codé ! */
  public $_coded = 0;
  public $_coded_message;

  // Actes CCAM
  public $_text_codes_ccam;
  public $_codes_ccam;
  public $_tokens_ccam;
  public $_temp_ccam;

  // Actes NGAP
  public $_empty_ngap;
  public $_store_ngap;
  public $_codes_ngap;
  public $_tokens_ngap;
  public $_count_actes_ngap;

  /* Actes LPP */
  public $_codes_lpp;
  public $_tokens_lpp;

  // Actes Tarmed
  public $_codes_tarmed;
  public $_tokens_tarmed;

  // Actes Caisse
  public $_codes_caisse;
  public $_tokens_caisse;

  // References
  /** @var CMediusers */
  public $_ref_anesth;
  /** @var CDatedCodeCCAM[] */
  public $_ext_codes_ccam;
  /** @var CDatedCodeCCAM[] */
  public $_ext_codes_ccam_princ;
  /** @var  CConsultation */
  public $_ref_consult_related;

  // Back references
  /** @var CActe[] */
  public $_ref_actes = array();
  /** @var CActeCCAM[] */
  public $_ref_actes_ccam = array();
  /** @var CCodageCCAM[] */
  public $_ref_codages_ccam = array();
  /** @var CActeNGAP[] */
  public $_ref_actes_ngap = array();
  /** @var CFraisDivers[] */
  public $_ref_frais_divers = array();
  /** @var CActeCaisse[] */
  public $_ref_actes_caisse = array();
  /** @var CActeTarmed[] */
  public $_ref_actes_tarmed = array();
  /** @var CActeLPP[] */
  public $_ref_actes_lpp = array();
  /** @var CBillingPeriod */
  public $_ref_billing_periods;

  /** @var CPrescription[] */
  public $_ref_prescriptions = array();

  // Distant references
  /** @var  CSejour */
  public $_ref_sejour;
  /** @var  CPatient */
  public $_ref_patient;
  /** @var  CMediusers */
  public $_ref_praticien;
  /** @var  CMediusers */
  public $_ref_executant;


  // Behaviour fields
  public $_delete_actes;
  public $_delete_actes_type;

  /**
   * @var array A list of acts whose activities 1 need to be hidden
   */
  public static $hidden_activity_1 = array(
    'YYYY041',
    'GELE001',
    'AHQJ021'
  );

  /**
   * Détruit les actes CCAM et NGAP
   *
   * @return string Store-like message
   */
  function deleteActes() {
    $this->_delete_actes = false;
    $this->exec_tarif    = "";

    // Suppression des anciens actes CCAM
    $this->loadRefsActesCCAM();
    foreach ($this->_ref_actes_ccam as $acte) {
      if ($msg = $acte->delete()) {
        return $msg;
      }
    }
    $this->codes_ccam = "";

    // Suppression des anciens actes NGAP
    $this->loadRefsActesNGAP();
    foreach ($this->_ref_actes_ngap as $acte) {
      if ($msg = $acte->delete()) {
        return $msg;
      }
    }
    $this->_tokens_ngap = "";

    // Suppression des frais divers
    $this->loadRefsFraisDivers(null);
    foreach ($this->_ref_frais_divers as $acte) {
      if ($msg = $acte->delete()) {
        return $msg;
      }
    }

    if (CModule::getActive("tarmed")) {
      if (!$this->_delete_actes_type || $this->_delete_actes_type == "tarmed") {
        // Suppression des anciens actes Tarmed
        $this->loadRefsActesTarmed();
        foreach ($this->_ref_actes_tarmed as $acte) {
          if ($msg = $acte->delete()) {
            return $msg;
          }
        }
        $this->_tokens_tarmed = "";
      }

      if (!$this->_delete_actes_type || $this->_delete_actes_type == "caisse") {
        $this->loadRefsActesCaisse();
        foreach ($this->_ref_actes_caisse as $acte) {
          if ($msg = $acte->delete()) {
            return $msg;
          }
        }
        $this->_tokens_caisse = "";
      }
    }

    if (CModule::getActive('lpp')) {
      $this->loadRefsActesLPP();
      foreach ($this->_ref_actes_lpp as $acte) {
        if ($msg = $acte->delete()) {
          return $msg;
        }
      }

      $this->_tokens_lpp = '';
    }

    return null;
  }


  /**
   * Load billing periods
   *
   * @return CBillingPeriod[]|CStoredObject[]
   */
  function loadRefsBillingPeriods() {
    return $this->_ref_billing_periods = $this->loadBackRefs("billing_periods");
  }

  /**
   * Delete billing periods
   *
   * @return string Store-like message
   */
  function deleteBillingPeriods() {
    $this->loadRefsBillingPeriods();
    foreach ($this->_ref_billing_periods as $_billing_period) {
      if ($msg = $_billing_period->delete()) {
        return $msg;
      }
    }
  }

  /**
   * Check if the Codable has billing periods with a statement equal to grouped or billed
   *
   * @param CCodable $codable The codable
   *
   * @return bool
   */
  public static function hasBillingPeriods($codable) {
    if (in_array($codable->_class, ['COperation', 'CConsultation'])) {
      $codable = $codable->loadRefSejour();
    }

    $has_billing_periods = false;
    if ($codable->_class == 'CSejour') {
      $codable->loadRefsBillingPeriods();
      foreach ($codable->_ref_billing_periods as $_period) {
        if ($_period->period_statement != '0') {
          $has_billing_periods = true;
          break;
        }
      }
    }

    return $has_billing_periods;
  }

  /**
   * @see parent::store()
   */
  function store() {
    if ($msg = parent::store()) {
      return $msg;
    }

    $this->completeField("codes_ccam");

    if ($this->_delete_actes && $this->_id) {
      if ($msg = $this->deleteActes()) {
        return $msg;
      }
    }
    // Prévention sur le vidage du champ codes_ccam alors que des actes sont cotés
    elseif (!$this->_delete_actes && $this->_id && $this->fieldModified("codes_ccam") && !$this->codes_ccam) {
      if ($this->countBackRefs("actes_ccam")) {
        return CAppUI::tr("CCodable-Alert codes ccam coted");
      }
    }

    if ($msg = $this->storeDocItems()) {
      return $msg;
    }

    return null;
  }

  /**
   * Charge le séjour associé
   *
   * @return CSejour
   */
  function loadRefSejour() {
  }

  /**
   * Charge le patient associé
   *
   * @return CPatient
   */
  function loadRefPatient() {
  }

  /**
   * Charge le praticien responsable associé
   *
   * @return CMediusers
   */
  function loadRefPraticien() {
  }

  /**
   * @see parent::loadView()
   */
  function loadView() {
    parent::loadView();
    $this->loadRefsActesCCAM();
    $this->loadExtCodesCCAM();
  }

  /**
   * Calcul de la date d'execution de l'acte
   *
   * @return void
   */
  function getActeExecution() {
    if (!$this->_acte_execution) {
      $this->_acte_execution = CMbDT::dateTime();
    }

    return $this->_acte_execution;
  }

  /**
   * Retourn si l'acte a été codé
   *
   * @return bool
   */
  function isCoded() {
    return $this->_coded;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    if ($this->codes_ccam) {
      $this->codes_ccam       = strtoupper($this->codes_ccam);
      $this->_text_codes_ccam = str_replace("|", ", ", $this->codes_ccam);
      $this->updateCCAMFormField();
    }
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props                       = parent::getProps();
    $props["codes_ccam"]         = "str show|0";
    $props["facture"]            = "bool default|0";
    $props["tarif"]              = "str show|0";
    $props["exec_tarif"]         = "dateTime";
    $props["consult_related_id"] = "ref class|CConsultation";

    $props["_tokens_ccam"]   = "";
    $props["_tokens_ngap"]   = "";
    $props["_tokens_lpp"]    = "";
    $props["_tokens_tarmed"] = "";
    $props["_tokens_caisse"] = "";
    $props["_codes_ccam"]    = "";
    $props["_codes_ngap"]    = "";
    $props["_codes_lpp"]     = "";
    $props["_codes_tarmed"]  = "";
    $props["_codes_caisse"]  = "";
    $props["_count_actes"]   = "num min|0";

    return $props;
  }

  /*
  function loadRefPrescription() {
    $this->_ref_prescription = $this->loadUniqueBackRef("prescriptions");
  }
  */

  /**
   * Association des codes prévus avec les actes codés
   *
   * @return void
   */
  function getAssociationCodesActes() {
    $this->updateFormFields();
    $this->loadRefsActesCCAM();
    if ($this->_ref_actes_ccam) {
      foreach ($this->_ref_actes_ccam as $_acte) {
        $_acte->loadRefExecutant();
      }
    }
    $this->_associationCodesActes = array();
    $listCodes                    = $this->_ext_codes_ccam;
    $listActes                    = $this->_ref_actes_ccam;
    foreach ($listCodes as $key_code => $_code) {
      $ccam                                               = $_code->code;
      $phase                                              = $_code->_phase;
      $activite                                           = $_code->_activite;
      $this->_associationCodesActes[$key_code]["code"]    = $_code->code;
      $this->_associationCodesActes[$key_code]["nbActes"] = 0;
      $this->_associationCodesActes[$key_code]["ids"]     = "";
      foreach ($listActes as $key_acte => $_acte) {
        $test = ($_acte->code_acte == $ccam);
        $test = $test && ($phase === null || $_acte->code_phase == $phase);
        $test = $test && ($activite === null || $_acte->code_activite == $activite);
        $test = $test && !isset($this->_associationCodesActes[$key_code]["actes"][$_acte->code_phase][$_acte->code_activite]);
        if ($test) {
          $this->_associationCodesActes[$key_code]["actes"][$_acte->code_phase][$_acte->code_activite] = $_acte;
          $this->_associationCodesActes[$key_code]["nbActes"]++;
          $this->_associationCodesActes[$key_code]["ids"] .= "$_acte->_id|";
          unset($listActes[$key_acte]);
        }
      }
    }
  }

  /**
   * Mise à jour du champs des codes CCAM prévus
   *
   * @return void
   */
  function updateCCAMPlainField() {
    if (null !== $this->_codes_ccam) {
      $codes            = array();
      $this->codes_ccam = '';
      foreach (array_count_values($this->_codes_ccam) as $code => $count) {
        $codes[] = $count > 1 ? "{$count}*{$code}" : $code;
      }
      $this->codes_ccam = implode('|', $codes);
    }
  }

  /**
   * Mise à jour du form field des codes CCAM
   *
   * @return void
   */
  public function updateCCAMFormField() {
    $this->_codes_ccam = array();
    if ($this->codes_ccam) {
      $codes = explode('|', $this->codes_ccam);

      foreach ($codes as $code) {
        if ($code != '') {
          if (strpos($code, '*') !== false) {
            list($count, $code) = explode('*', $code);
            for ($i = 0; $i < $count; $i++) {
              $this->_codes_ccam[] = $code;
            }
          }
          else {
            $this->_codes_ccam[] = $code;
          }
        }
      }
    }
  }

  /**
   * Update montant and store object
   *
   * @return string Store-like message
   */
  function doUpdateMontants() {
  }

  /**
   * @see parent::updatePlainFields()
   */
  function updatePlainFields() {
    // Should update codes CCAM. Very sensible, test a lot before uncommenting
    // $this->updateCCAMPlainField();
    parent::updatePlainFields();
  }

  /**
   * Préparation au chargement des actes possibles
   * à partir des codes prévus
   *
   * @return void
   */
  function preparePossibleActes() {
  }

  /**
   * Récupération de l'executant d'une activité donnée
   *
   * @param int $code_activite Code de l'activité
   *
   * @return int|null Id de l'executant
   */
  function getExecutantId($code_activite = null) {
    return null;
  }

  /**
   * Récupération de l'extensions documentaires
   *
   * @param integer $executant_id L'id du praticien executant
   *
   * @return int|null
   */
  function getExtensionDocumentaire($executant_id) {
    return null;
  }

  /**
   * Calcul le nombre d'actes pour l'objet et selon un executant
   *
   * @param int $user_id executant des actes
   *
   * @return void
   */
  function countActes($user_id = null) {
    $where = array();
    if ($user_id) {
      $where["executant_id"] = "= '$user_id'";
    }
    $this->_count_actes = 0;
    if (CAppUI::gconf('dPccam codage use_cotation_ccam')) {
      $this->_count_actes += $this->countBackRefs("actes_ngap", $where);
      $this->_count_actes += $this->countBackRefs("actes_ccam", $where);
    }

    if (CModule::getActive('tarmed') && CAppUI::gconf('tarmed CCodeTarmed use_cotation_tarmed')) {
      $this->_count_actes += $this->countBackRefs("actes_tarmed", $where);
      $this->_count_actes += $this->countBackRefs("actes_caisse", $where);
    }

    if (CModule::getActive('lpp') && CAppUI::gconf('lpp General cotation_lpp')) {
      $this->_count_actes += $this->countBackRefs("actes_lpp", $where);
    }
  }

  /**
   * @param array $codables
   *
   * @throws Exception
   */
  public static function massCountActes(array $codables) {
    if (CAppUI::gconf('dPccam codage use_cotation_ccam')) {
      self::massCountBackRefs($codables, "actes_ngap");
      self::massCountBackRefs($codables, "actes_ccam");
    }

    if (CModule::getActive('lpp') && CAppUI::gconf('lpp General cotation_lpp')) {
      self::massCountBackRefs($codables, "actes_lpp");
    }

    if (CModule::getActive('tarmed') && CAppUI::gconf('tarmed CCodeTarmed use_cotation_tarmed')) {
      self::massCountBackRefs($codables, "actes_tarmed");
      self::massCountBackRefs($codables, "actes_caisse");
    }
  }

  /**
   * Charge tous les actes du codable, quelque soit leur type
   *
   * @param int    $num_facture numéro de la facture concernée
   * @param int    $facturable  actes facturables
   * @param string $date_min    Only the acts made on the given date
   * @param string $date_max    Only the acts made on the given date
   *
   * @return CActe[] collection d'actes concrets
   */
  function loadRefsActes($num_facture = null, $facturable = null, $date_min = null, $date_max = null) {
    $this->_ref_actes = array();

    $this->loadRefsActesCCAM($facturable, null, $date_min, $date_max);
    $this->loadRefsActesNGAP($facturable, null, null, null, null, null, $date_min, $date_max);
    $this->loadRefsActesLPP();
    $this->loadRefsActesTarmed($num_facture);
    $this->loadRefsActesCaisse($num_facture);
    $this->loadRefsFraisDivers($num_facture);

    if ($num_facture == 1 || !$num_facture) {
      foreach ($this->_ref_actes_ccam as $acte_ccam) {
        $this->_ref_actes[] = $acte_ccam;
      }
      foreach ($this->_ref_actes_ngap as $acte_ngap) {
        $this->_ref_actes[] = $acte_ngap;
      }
    }

    if ($this->_ref_actes_lpp) {
      $this->_ref_actes = array_merge($this->_ref_actes, $this->_ref_actes_lpp);
    }

    if ($this->_ref_actes_tarmed) {
      foreach ($this->_ref_actes_tarmed as $acte_tarmed) {
        $this->_ref_actes[] = $acte_tarmed;
      }
    }

    if ($this->_ref_actes_caisse) {
      foreach ($this->_ref_actes_caisse as $acte_caisse) {
        $this->_ref_actes[] = $acte_caisse;
      }
    }
    if ($this->_ref_frais_divers) {
      foreach ($this->_ref_frais_divers as $acte_divers) {
        $this->_ref_actes[] = $acte_divers;
      }
    }

    $this->_count_actes = count($this->_ref_actes);

    return $this->_ref_actes;
  }

  /**
   * @param array $codables
   *
   * @throws Exception
   */
  public static function massLoadActes(array $codables) {
    if (CAppUI::gconf('dPccam codage use_cotation_ccam')) {
      self::massLoadBackRefs($codables, 'actes_ngap');
      self::massLoadBackRefs($codables, 'actes_ccam');
    }

    if (CModule::getActive('lpp') && CAppUI::gconf('lpp General cotation_lpp')) {
      self::massLoadBackRefs($codables, 'actes_lpp');
    }

    if (CModule::getActive('tarmed') && CAppUI::gconf('tarmed CCodeTarmed use_cotation_tarmed')) {
      self::massLoadBackRefs($codables, 'actes_tarmed');
      self::massLoadBackRefs($codables, 'actes_caisse');
    }
  }

  /**
   * Charge les actes CCAM codés
   *
   * @param int    $facturable   actes facturables
   * @param int    $executant_id Only Load the act made by the given user
   * @param string $date_min     Only the acts made on the given date
   * @param string $date_max     Only the acts made on the given date
   *
   * @return CActeCCAM[]
   * @throws Exception
   */
  function loadRefsActesCCAM($facturable = null, $executant_id = null, $date_min = null, $date_max = null) {
    if ($this->_ref_actes_ccam) {
      return $this->_ref_actes_ccam;
    }

    $order   = array();
    $order[] = "code_association ASC";
    $order[] = "code_acte";
    $order[] = "code_activite";
    $order[] = "code_phase";
    $order[] = "acte_id";

    $where = [];
    if (!is_null($facturable) && $facturable != '') {
      $where['facturable'] = " = '$facturable'";
    }
    if ($executant_id) {
      $where['executant_id'] = " = $executant_id";
    }

    if ($date_min && $date_max) {
      $where['execution'] = " BETWEEN '$date_min 00:00:00' AND '$date_max 23:59:59'";
    }
    elseif ($date_min) {
      $where[] = "DATE(execution) = '$date_min'";
    }

    if (count($where)) {
      self::$useObjectCache = false;
    }

    if (null === $this->_ref_actes_ccam = $this->loadBackRefs("actes_ccam", $order, null, null, null, null, null, $where)) {
      return $this->_ref_actes_ccam;
    }

    if (count($where)) {
      self::$useObjectCache = true;
    }

    $this->_temp_ccam = array();
    foreach ($this->_ref_actes_ccam as $_acte_ccam) {
      $this->_temp_ccam[] = $_acte_ccam->makeFullCode();
    }

    $this->_tokens_ccam = implode("|", $this->_temp_ccam);

    return $this->_ref_actes_ccam;
  }

  /**
   * Charge les éléments de codage CCAM
   *
   * @return CCodageCCAM[]
   */
  function loadRefsCodagesCCAM() {
    if ($this->_ref_codages_ccam) {
      return $this->_ref_codages_ccam;
    }

    $codages                 = $this->loadBackRefs("codages_ccam");
    $this->_ref_codages_ccam = array();
    foreach ($codages as $_codage) {
      if (!array_key_exists($_codage->praticien_id, $this->_ref_codages_ccam)) {
        $this->_ref_codages_ccam[$_codage->praticien_id] = array();
      }

      $this->_ref_codages_ccam[$_codage->praticien_id][] = $_codage;
    }

    return $this->_ref_codages_ccam;
  }

  /**
   * Relie les actes aux codages pour calculer les règles d'association
   *
   * @return void
   */
  function guessActesAssociation() {
    $this->loadRefsActesCCAM();
    $this->loadRefsCodagesCCAM();
    foreach ($this->_ref_codages_ccam as $_codages_by_prat) {
      /** @var CCodageCCAM $_codage */
      foreach ($_codages_by_prat as $_codage) {
        foreach ($this->_ref_actes_ccam as $_acte) {
          if ($_codage->praticien_id == $_acte->executant_id
              && (($_acte->code_activite == 4 && $_codage->activite_anesth)
              || ($_acte->code_activite != 4 && !$_codage->activite_anesth))
              && CMbDT::date(null, $_acte->execution) == $_codage->date
          ) {
            $_codage->_ref_actes_ccam[$_acte->_id] = $_acte;
          }
        }
        $_codage->guessActesAssociation();
      }
    }
  }

  /**
   * Charge les actes NGAP codés
   *
   * @param int    $facturable   actes facturables
   * @param string $order_col    Tri par colonne
   * @param string $order_way    Sens du tri par colonne
   * @param string $limit        A SQL limit
   * @param int    $executant_id Only Load the act made by the given user
   * @param int    $function_id  Only Load the act made by users of the given function (exclusive with the executant_id attribute)
   * @param string $date_min     Only the acts made on the given date
   * @param string $date_max     Only the acts made on the given date
   *
   * @return CActeNGAP[]
   * @throws Exception
   */
  function loadRefsActesNGAP(
      $facturable = null, $order_col = null, $order_way = null, $limit = null,
      $executant_id = null, $function_id = null, $date_min = null, $date_max = null
  ) {
    /* ajout d'un paramètre d'ordre à passer, ici "lettre_cle" qui vaut 0 ou 1
     * la valeur 1 étant pour les actes principaux et O pour les majorations
     * on souhaite que les actes principaux soient proritaires( donc '1' avant '0')
     */

    //$this->_empty_ngap = CActeNGAP::createEmptyFor($this);

    $order = 'lettre_cle DESC';
    $ljoin = array();
    if ($order_col == 'executant_id' && $order_way) {
      $ljoin['users'] = "users.user_id = acte_ngap.executant_id";
      $order          = "users.user_last_name $order_way";
    }
    elseif ($order_col && $order_way) {
      $order = "$order_col $order_way";
    }

    /* The acts are always ordered by execution date as a secondary order */
    if (strpos($order, 'execution') === false) {
      $order .= ", execution DESC";
    }

    $where = [];
    if (!is_null($facturable) && $facturable != '') {
      $where['facturable'] = " = '$facturable'";
    }
    if ($executant_id) {
      $where['executant_id'] = " = $executant_id";
    }
    elseif ($function_id) {
      $ljoin['users_mediboard'] = "users_mediboard.user_id = acte_ngap.executant_id";
      $where['function_id'] = " = '{$function_id}'";
    }

    if ($date_min && $date_max) {
      $where['execution'] = " BETWEEN '$date_min 00:00:00' AND '$date_max 23:59:59'";
    }
    elseif ($date_min) {
      $where['execution'] = " BETWEEN '$date_min 00:00:00' AND '$date_min 23:59:59'";
    }

    $this->_count_actes_ngap = $this->countBackRefs('actes_ngap', $where, $ljoin);
    /* Force the reload of the back refs if there is an order, a limit or some filters */
    if ($order || $limit || count($where)) {
      self::$useObjectCache = false;
    }

    if (null === $this->_ref_actes_ngap = $this->loadBackRefs("actes_ngap", $order, $limit, null, $ljoin, null, null, $where)) {
      return;
    }

    if ($order || $limit || count($where)) {
      self::$useObjectCache = true;
    }

    $this->_codes_ngap = array();
    foreach ($this->_ref_actes_ngap as $_acte_ngap) {
      /** @var CActeNGAP $_acte_ngap */
      $this->_codes_ngap[] = $_acte_ngap->makeFullCode();
      $_acte_ngap->loadRefExecutant();
      $_acte_ngap->getLibelle();
      $_acte_ngap->getForbiddenComplements();
      if (($this->_class == 'CConsultation' && $this->sejour_id) || $this->_class == 'CSejour') {
        $_acte_ngap->loadRefPrescripteur();
      }
    }
    $this->_tokens_ngap = implode("|", $this->_codes_ngap);

    return $this->_ref_actes_ngap;
  }

  /**
   * Charge les actes Tarmed codés
   *
   * @param int  $num_facture numéro de la facture concernée
   * @param bool $show_alerte chargement des alertes
   *
   * @return array
   */
  function loadRefsActesTarmed($num_facture = null, $show_alerte = false) {
    $this->_ref_actes_tarmed = array();

    if (CModule::getActive("tarmed") && CAppUI::gconf("tarmed CCodeTarmed use_cotation_tarmed")) {
      $where       = array();
      if ($num_facture) {
        $where["num_facture"] = " = '$num_facture'";
      }
      //Classement des actes par ordre chonologique, numéro de facture et par code
      $order = "date, num_facture, code ASC";
      if (null === $this->_ref_actes_tarmed = $this->loadBackRefs("actes_tarmed", $order, null, null, null, null, null, $where)) {
        return $this->_ref_actes_ccam;
      }

      $executants = CStoredObject::massLoadFwdRef($this->_ref_actes_tarmed, "executant_id");
      CStoredObject::massLoadFwdRef($executants, "function_id");
      $this->_codes_tarmed = array();
      foreach ($this->_ref_actes_tarmed as $_acte_tarmed) {
        /** @var CActeTarmed $_acte_tarmed */
        $this->_codes_tarmed[] = $_acte_tarmed->makeFullCode();
        $_acte_tarmed->loadRefExecutant();
        $_acte_tarmed->loadRefTarmed();
        if ($show_alerte) {
          $_acte_tarmed->loadAlertes();
        }
      }
      $this->_tokens_tarmed = implode("|", $this->_codes_tarmed);
    }
    return $this->_ref_actes_tarmed;
  }

  /**
   * Charge les actes Caisse codés
   *
   * @param int $num_facture numéro de la facture concernée
   *
   * @return array
   */
  function loadRefsActesCaisse($num_facture = null) {
    $this->_ref_actes_caisse = array();

    if (CModule::getActive("tarmed") && CAppUI::gconf("tarmed CCodeTarmed use_cotation_tarmed")) {
      //Classement des actes par ordre chonologique et par code
      $where = array();
      if ($num_facture) {
        $where["acte_caisse.num_facture"] = " = '$num_facture'";
      }
      //Classement des actes par ordre chonologique, numéro de facture et par code
      $order                   = "acte_caisse.num_facture, caisse_maladie_id, code ASC";
      if (null === $this->_ref_actes_caisse = $this->loadBackRefs("actes_caisse", $order, null, null, null, null, null, $where)) {
        return $this->_ref_actes_ccam;
      }

      $this->_codes_caisse = array();
      $executants = CStoredObject::massLoadFwdRef($this->_ref_actes_caisse, "executant_id");
      CStoredObject::massLoadFwdRef($executants, "function_id");
      CStoredObject::massLoadFwdRef($this->_ref_actes_caisse, "caisse_maladie_id");
      foreach ($this->_ref_actes_caisse as $_acte_caisse) {
        /** @var CActeCaisse $_acte_caisse */
        $this->_codes_caisse[] = $_acte_caisse->makeFullCode();
        $_acte_caisse->loadRefExecutant();
        $_acte_caisse->loadRefPrestationCaisse();
        $_acte_caisse->loadRefCaisseMaladie();
      }
      $this->_tokens_caisse = implode("|", $this->_codes_caisse);
    }
    return $this->_ref_actes_caisse;
  }

  /**
   * Load the LPP acts
   *
   * @return CActeLPP[]
   */
  public function loadRefsActesLPP() {
    $this->_ref_actes_lpp = array();
    $this->_codes_lpp     = array();

    if (CModule::getActive('lpp') && CAppUI::gconf('lpp General cotation_lpp')) {
      $this->_ref_actes_lpp = $this->loadBackRefs('actes_lpp');

      foreach ($this->_ref_actes_lpp as $_acte) {
        $this->_codes_lpp[] = $_acte->makeFullCode();

        $_acte->loadRefExecutant();
      }
    }

    $this->_tokens_lpp = implode('|', $this->_codes_lpp);

    return $this->_ref_actes_lpp;
  }

  /**
   * Charge les codes CCAM en tant qu'objets externes
   *
   * @return void
   */
  function loadExtCodesCCAM() {
    $this->_ext_codes_ccam       = array();
    $this->_ext_codes_ccam_princ = array();
    $dateActe                    = CMbDT::format($this->_datetime, "%Y-%m-%d");
    if ($this->_codes_ccam !== null) {
      foreach ($this->_codes_ccam as $code) {
        $code = CDatedCodeCCAM::get($code, $dateActe);
        if ($this->_ref_praticien && $this->_ref_patient) {
          $code->getPrice($this->_ref_praticien, $this->_ref_patient, $this->_datetime);
        }
        /* On supprime l'activité 1 du code si celui fait partie de la liste */
        if (in_array($code->code, self::$hidden_activity_1)) {
          unset($code->activites[1]);
        }
        $this->_ext_codes_ccam[] = $code;
        if ($code->type != 2) {
          $this->_ext_codes_ccam_princ[] = $code;
        }
      }
      if (CAppUI::gconf('dPccam codage display_order') == 'price') {
        CMbArray::ksortByProp($this->_ext_codes_ccam, "type", "_sorted_tarif");
      }
      else {
        CMbArray::ksortByProp($this->_ext_codes_ccam, 'code');
      }
    }
  }

  /**
   * Charge les actes frais divers
   *
   * @param int $num_facture numéro de la facture concernée
   *
   * @return CFraisDivers[]
   */
  function loadRefsFraisDivers($num_facture = 1) {
    if (!CAppUI::gconf("dPccam frais_divers use_frais_divers_CConsultation")) {
      return;
    }
    $this->_ref_frais_divers = $this->loadBackRefs("frais_divers");

    if (is_array($this->_ref_frais_divers)) {
      foreach ($this->_ref_frais_divers as $_frais) {
        if ($num_facture && $_frais->num_facture != $num_facture) {
          unset($this->_ref_frais_divers[$_frais->_id]);
        }
        else {
          $_frais->loadRefType();
        }
      }
    }

    return $this->_ref_frais_divers;
  }

  /**
   * Vérification du codage des actes ccam
   *
   * @return array
   */
  function getMaxCodagesActes() {
    if (!$this->_id || $this->codes_ccam === null) {
      return null;
    }

    $oldObject = new static;
    $oldObject->load($this->_id);
    $oldObject->codes_ccam = $this->codes_ccam;
    $oldObject->updateFormFields();
    $oldObject->updateCCAMFormField();

    $oldObject->loadRefsActesCCAM();

    // Creation du tableau minimal de codes ccam
    $codes_ccam_minimal = array();
    foreach ($oldObject->_ref_actes_ccam as $acte) {
      if (!array_key_exists($acte->code_acte, $codes_ccam_minimal)) {
        $codes_ccam_minimal[$acte->code_acte] = ['count' => 1];
        $codes_ccam_minimal[$acte->code_acte]["activity_{$acte->code_activite}_{$acte->code_phase}"] = 1;
      }
      else {
        $activity = "activity_{$acte->code_activite}_{$acte->code_phase}";
        if (!array_key_exists($activity, $codes_ccam_minimal[$acte->code_acte])) {
          $codes_ccam_minimal[$acte->code_acte][$activity] = 1;
        }
        else {
          $codes_ccam_minimal[$acte->code_acte][$activity]++;
        }

        if ($codes_ccam_minimal[$acte->code_acte][$activity] > $codes_ccam_minimal[$acte->code_acte]['count']) {
          $codes_ccam_minimal[$acte->code_acte]['count']++;
        }
      }
    }

    // Transformation du tableau de codes ccam
    $codes_ccam = array();
    foreach ($oldObject->_codes_ccam as $code) {
      $count = 1;
      if (strlen($code) > 7) {
        if (strpos($code, '*') !== false) {
          list($count, $code) = explode('*', $code);
        }
        // si le code est de la forme code-activite-phase
        $detailCode = explode("-", $code);
        $code       = $detailCode[0];
      }

      if (!array_key_exists($code, $codes_ccam)) {
        $codes_ccam[$code] = $count;
      }
      else {
        $codes_ccam[$code]+= $count;
      }
    }

    // Test entre les deux tableaux
    foreach ($codes_ccam_minimal as $_code => $_data) {
      if (!array_key_exists($_code, $codes_ccam) || $codes_ccam[$_code] < $_data['count']) {
        return "Impossible de supprimer le code";
      }
    }

    return null;
  }

  /**
   * Vérification du code ccam
   *
   * @return string|null
   */
  function checkCodeCcam() {
    $this->updateCCAMFormField();

    foreach ($this->_codes_ccam as $_code_ccam) {
      if (!preg_match("/^[A-Z]{4}[0-9]{3}(-[0-9](-[0-9])?)?$/i", $_code_ccam)) {
        return "Le code CCAM '$_code_ccam' n'est pas valide";
      }
    }

    return null;
  }

  /**
   * @see parent::check()
   */
  function check() {
    $this->loadOldObject();

    if ($msg = $this->checkCodeCcam()) {
      return $msg;
    }

    if (!$this->_forwardRefMerging && !$this->_merging && CAppUI::gconf("dPccam codage use_getMaxCodagesActes")) {
      if ($this->_old && $this->codes_ccam != $this->_old->codes_ccam) {
        if ($msg = $this->getMaxCodagesActes()) {
          return $msg;
        }
      }
    }

    return parent::check();
  }

  /**
   * Test de la cloture
   *
   * @return null
   */
  function testCloture() {
    $actes_ccam = $this->loadRefsActesCCAM();

    $count_activite_1 = 0;
    $count_activite_4 = 0;

    foreach ($actes_ccam as $_acte_ccam) {
      if ($_acte_ccam->code_activite == 1) {
        $count_activite_1++;
      }
      if ($_acte_ccam->code_activite == 4) {
        $count_activite_4++;
      }
    }

    return ($count_activite_1 == 0 || $this->cloture_activite_1) &&
      ($count_activite_4 == 0 || $this->cloture_activite_4);
  }

  /**
   * Vérification du modificateur
   *
   * @param int    $code  code de l'acte
   * @param string $heure heure d'exécution
   *
   * @return array|void
   */
  function checkModificateur($code, $heure) {
    $keys = array("A", "E", "P", "S", "U", "7", "J");

    if (!in_array($code, $keys)) {
      return null;
    }

    $patient = $this->loadRefPatient();
    $this->loadRefPraticien();
    $discipline = $this->_ref_praticien->loadRefDiscipline();
    // Il faut une date complête pour la comparaison
    $date_ref = CMbDT::date();
    $date     = "$date_ref $heure";

    switch ($code) {
      case "A":
        return ($patient->_annees < 4 || $patient->_annees >= 80);
        break;
      case "E":
        return $patient->_annees < 5;
        break;
      case "P":
        return in_array($discipline->text, array("MEDECINE GENERALE", "PEDIATRIE")) &&
          (($date > "$date_ref 20:00:00" && $date <= "$date_ref 23:59:59") ||
            ($date > "$date_ref 06:00:00" && $date < "$date_ref 08:00:00"));
        break;
      case "S":
        return in_array($discipline->text, array("MEDECINE GENERALE", "PEDIATRIE")) &&
          ($date >= "$date_ref 00:00:01" && $date < "$date_ref 06:00:00");
        break;
      case "U":
        $date_tomorrow = CMbDT::date("+1 day", $date_ref) . " 08:00:00";

        return !in_array($discipline->text, array("MEDECINE GENERALE", "PEDIATRIE")) &&
          ($date > "$date_ref 20:00:00" && $date < $date_tomorrow);
        break;
      case "7":
        return CAppUI::pref('precode_modificateur_7');
        break;
      case "J":
        return CAppUI::pref('precode_modificateur_J') && $this->_class == 'COperation';
    }

    return null;
  }

  /**
   * Bind the tarif to the codable
   *
   * @return null|string
   */
  function bindTarif() {
    if ($this->_class != "COperation") {
      $this->completeField("praticien_id");
    }
    $this->completeField('exec_tarif');

    $this->_bind_tarif = false;
    $this->loadRefPraticien();

    // Chargement du tarif
    $tarif = new CTarif();
    $tarif->load($this->_tarif_id);

    if ($this->_class != "CConsultation") {
      $this->tarif = $this->tarif ? "composite" : $tarif->description;
    }

    // Mise à jour de codes CCAM prévus, sans information serialisée complémentaire
    foreach ($tarif->_codes_ccam as $_code_ccam) {
      $this->_codes_ccam[] = substr($_code_ccam, 0, 7);
    }
    $this->codes_ccam = $this->updateCCAMPlainField();

    if (!$this->exec_tarif) {
      $date = CMbDT::dateTime();
      if (CAppUI::pref('use_acte_date_now') && (($this->_class == 'COperation' && CMbDT::date(null, $date) == $this->date)
          || ($this->_class == 'CSejour' && $date >= $this->_entree && $date <= $this->_sortie) || $this->_class == 'CConsultation')
      ) {
        $this->exec_tarif = $date;
      }
      else {
        $this->exec_tarif = $this->_acte_execution ? $this->_acte_execution : $this->getActeExecution();
      }
    }
    $_acte_execution = $this->exec_tarif;

    if ($msg = $this->store()) {
      return $msg;
    }

    /* The acte_execution field is reset after the store */
    $this->_acte_execution = $_acte_execution;

    // Precodage des actes NGAP avec information sérialisée complète
    $this->_tokens_ngap = $tarif->codes_ngap;
    if ($msg = $this->precodeActe("_tokens_ngap", "CActeNGAP", $this->getExecutantId())) {
      return $msg;
    }

    $this->codes_ccam = $tarif->codes_ccam;
    // Precodage des actes CCAM avec information sérialisée complète
    if ($msg = $this->precodeCCAM($this->getExecutantId())) {
      return $msg;
    }
    $this->codes_ccam = $this->updateCCAMPlainField();

    if (CModule::getActive('lpp') && CAppUI::gconf('lpp General cotation_lpp')) {
      /* Precodage des actes LPP avec information sérialisée complète */
      $this->_tokens_lpp = $tarif->codes_lpp;
      if ($msg = $this->precodeActe('_tokens_lpp', 'CActeLPP', $this->getExecutantId())) {
        return $msg;
      }
    }

    if (CModule::getActive("tarmed")) {
      $this->_tokens_tarmed = $tarif->codes_tarmed;
      if ($msg = $this->precodeActe("_tokens_tarmed", "CActeTarmed", $this->getExecutantId())) {
        return $msg;
      }
      $this->_tokens_caisse = $tarif->codes_caisse;
      if ($msg = $this->precodeActe("_tokens_caisse", "CActeCaisse", $this->getExecutantId())) {
        return $msg;
      }
    }

    return null;
  }

  /**
   * Charge les actes CCAM codables en fonction des code CCAM fournis
   *
   * @param integer $praticien_id L'id du praticien auquel seront liés les actes
   *
   * @return void
   */
    function loadPossibleActes($praticien_id = 0) {
    $this->preparePossibleActes();
    $depassement_affecte        = false;
    $depassement_anesth_affecte = false;

    $this->guessActesAssociation();

    // Check if depassement is already set
    $this->loadRefsActesCCAM();
    foreach ($this->_ref_actes_ccam as $_acte) {
      if ($_acte->code_activite == 1 && $_acte->montant_depassement) {
        $depassement_affecte = true;
      }
      if ($_acte->code_activite == 4 && $_acte->montant_depassement) {
        $depassement_anesth_affecte = true;
      }
    }

    // existing acts may only be affected once to possible acts
    $used_actes = array();

    if ($praticien_id) {
      $praticien    = CMediusers::get($praticien_id);
      $executant_id = $praticien_id;
    }
    else {
      $praticien    = $this->loadRefPraticien();
      $executant_id = 0;
    }
    $praticien->loadRefDiscipline();
    $this->loadRefPatient()->evalAge();

    if (is_array($this->_ext_codes_ccam) && !count($this->_ext_codes_ccam)) {
      $this->loadExtCodesCCAM();
    }

    $execution_naissance = "";
    if (CModule::getActive("maternite") && $this instanceof COperation) {
      $this->loadRefSejour()->loadRefsNaissances();
      $naissance           = reset($this->_ref_sejour->_ref_naissances);
      $execution_naissance = $naissance ? $naissance->date_time : null;
    }

    $use_acte_date_now = CAppUI::pref("use_acte_date_now");
    $default_qualif_depense = CAppUI::pref("default_qualif_depense");
    $user = CMediusers::get();
    $user->isProfessionnelDeSante();
    $user_executant = CAppUI::pref("user_executant");
    $exceptions = explode('|', CAppUI::gconf('dPccam codage display_act_anesth_exceptions'));
    $display_act_anesth = CAppUI::gconf('dPccam codage display_act_anesth');

    $cache_possible_acte = array();
    foreach ($this->_ext_codes_ccam as $code_ccam) {
      foreach ($code_ccam->activites as $activite) {
        foreach ($activite->phases as $phase) {

          if (isset($cache_possible_acte[$code_ccam->code][$activite->numero][$phase->phase])) {
            $possible_acte = $cache_possible_acte[$code_ccam->code][$activite->numero][$phase->phase];
          }
          else {
            $possible_acte                      = new CActeCCAM();
            $possible_acte->montant_depassement = "";
            $possible_acte->code_acte           = $code_ccam->code;
            $possible_acte->code_activite       = $activite->numero;

            $possible_acte->_anesth = ($activite->numero == 4);

            $possible_acte->code_phase = $phase->phase;

            if ($this->_class == 'CSejour' && $use_acte_date_now) {
              $possible_acte->execution = CMbDT::format($this->_acte_execution, '%Y-%m-%d ').CMbDT::time();
            }
            else {
              $possible_acte->execution = $use_acte_date_now ? "now" : CMbDT::format($this->_acte_execution, '%Y-%m-%d %H:%M:00');
            }

            // Exécution d'actes d'activité 1 à l'heure de naissance
            if ($execution_naissance && $possible_acte->code_activite == 1) {
              $possible_acte->execution = $execution_naissance;
            }

            // Affectation du dépassement au premier acte de chirugie
            if (!$depassement_affecte and $possible_acte->code_activite == 1) {
              $possible_acte->montant_depassement = $this->_acte_depassement;
              $depassement_affecte                = true;
            }

            // Affectation du dépassement au premier acte d'anesthésie
            if (!$depassement_anesth_affecte and $possible_acte->code_activite == 4) {
              $possible_acte->montant_depassement = $this->_acte_depassement_anesth;
              $depassement_anesth_affecte         = true;
            }

            if ($possible_acte->montant_depassement && $default_qualif_depense != '') {
              $possible_acte->motif_depassement = $default_qualif_depense;
            }

            if (!$praticien_id) {
              if ($user_executant && $user->_is_professionnel_sante && $possible_acte->code_activite != '4') {
                $executant_id = $user->_id;
              }
              else {
                $executant_id = $this->getExecutantId($possible_acte->code_activite);
              }
            }
            $possible_acte->executant_id = $executant_id;
            $possible_acte->object_class = $this->_class;
            $possible_acte->object_id    = $this->_id;

            if (($this->_class == 'CConsultation' && $this->concerne_ALD) || ($this->_class == 'CSejour' && $this->ald)) {
              $possible_acte->ald = '1';
            }

            if ($possible_acte->code_activite == 4) {
              $possible_acte->extension_documentaire = $this->getExtensionDocumentaire($possible_acte->executant_id);

              /* Dans le cas des actes d'activité 4, la date d'execution est la même que l'activité 1 si celle est codée */
              $acte_chir = $possible_acte->loadActeActiviteAssociee();
              if ($acte_chir->_id) {
                $possible_acte->execution = $acte_chir->execution;
                if ($acte_chir->code_extension) {
                  $possible_acte->code_extension = $acte_chir->code_extension;
                }
              }
            }

            /* Gestion du champ remboursé */
            if ($code_ccam->remboursement == 1) {
              /* Cas ou l'acte est remboursable */
              $possible_acte->rembourse = '1';
            }
            elseif ($code_ccam->remboursement == 2) {
              /* Cas ou l'acte est non remboursable */
              $possible_acte->rembourse = '0';
            }
            else {
              $possible_acte->rembourse = null;
            }

            $possible_acte->updateFormFields();
            $possible_acte->loadRefExecutant();
            $possible_acte->loadRefCodeCCAM();
            $possible_acte->loadRefCodageCCAM(false);
            $possible_acte->getAnesthAssocie();
            $cache_possible_acte[$code_ccam->code][$activite->numero][$phase->phase] = $possible_acte;
          }

          // Affect a loaded acte if exists
          foreach ($this->_ref_actes_ccam as $_acte) {
            if (
              $_acte->code_acte == $possible_acte->code_acte
              && $_acte->code_activite == $possible_acte->code_activite
              && $_acte->code_phase == $possible_acte->code_phase
            ) {
              if (!isset($used_actes[$_acte->acte_id])) {
                $possible_acte               = $_acte;
                $used_actes[$_acte->acte_id] = true;
                break;
              }
            }
          }

          if ($possible_acte->_id) {
            $possible_acte->getTarif();
          }
          else {
            $possible_acte->getTarifSansAssociationNiCharge();

            $possible_acte->_display = true;
            /* If the function parameter praticien_id is set, we check if the code is allowed for the user */
            if ($praticien_id && !$code_ccam->isCodeAllowedForUSer($praticien)) {
              $possible_acte->_display = false;
            }
            /* Cache les actes d'activité 4 dont l'activité 1 n'est pas codée si la config display_act_anesth est à 0 */
            elseif (($this->_class == 'COperation' || $this->_class == 'CSejour') && count($code_ccam->activites) > 1
              && $possible_acte->code_activite == '4' && !$display_act_anesth
            ) {
              $display_acte = false;
              if (is_array($exceptions) && !empty($exceptions) && in_array($possible_acte->code_acte, $exceptions)) {
                $display_acte = true;
              }
              else {
                foreach ($this->_ref_actes_ccam as $_acte) {
                  if ($possible_acte->code_acte == $_acte->code_acte && $_acte->code_activite == '1') {
                    $display_acte = true;
                    break;
                  }
                }
              }

              $possible_acte->_display = $display_acte;
            }
          }

          // Keep references !
          $phase->_connected_acte = $possible_acte;
          $listModificateurs      = $phase->_connected_acte->modificateurs;
          if (!$possible_acte->_id) {
            $possible_acte->facturable = '1';
            $possible_acte->checkFacturable();
            if (!self::$possible_actes_lite) {
              CCodageCCAM::precodeModifiers($phase->_modificateurs, $possible_acte, $this);
              $possible_acte->getMontantModificateurs($phase->_modificateurs);
            }
          }
          else {
            if (property_exists($phase, '_modificateurs') && is_array($phase->_modificateurs)) {
              // Récupération des modificateurs codés
              foreach ($phase->_modificateurs as $modificateur) {
                /* Dans le cas des modificateurs doubles, les 2 lettres peuvent être séparées (IJKO dans le cas de IO par exemple) */
                if ($modificateur->_double == "2") {
                  $position = strpos($listModificateurs, $modificateur->code[0]) !== false
                    && strpos($listModificateurs, $modificateur->code[1]) !== false;
                }
                else {
                  $position = strpos($listModificateurs, $modificateur->code);
                }

                $modificateur->_checked = null;
                if ($position !== false) {
                  if ($modificateur->_double == "1") {
                    $modificateur->_checked = $modificateur->code;
                  }
                  elseif ($modificateur->_double == "2") {
                    $modificateur->_checked = $modificateur->code . $modificateur->_double;
                  }
                }
              }
            }
            if (!self::$possible_actes_lite) {
              /* Vérification et précodage des modificateurs */
              CCodageCCAM::precodeModifiers($phase->_modificateurs, $possible_acte, $this);
              $possible_acte->getMontantModificateurs($phase->_modificateurs);
            }
          }
        }
      }
    }
  }

  /**
   * Ajout des actes non ccam d'un tarif dans une intervention ou consultation
   *
   * @param string $token      les tokens
   * @param string $acte_class la classe des actes pris en compte
   * @param string $chir       l'executant de l'acte
   *
   * @return string $msg
   */
  function precodeActe($token, $acte_class, $chir) {
    $execution = $this->_acte_execution;

    $listCodes = explode("|", $this->$token);
    foreach ($listCodes as $code) {
      if ($code) {
        $acte                    = new $acte_class;
        $acte->_preserve_montant = true;
        $acte->setFullCode($code);

        $acte->object_id    = $this->_id;
        $acte->object_class = $this->_class;
        $acte->executant_id = $chir;
        $acte->execution    = $this->_acte_execution;
        if ($acte_class == "CActeNGAP") {
          $acte->check();

          if (CAppUI::gconf('dPccam ngap prefill_prescriptor')
              && (($this->_class == 'CConsultation' && $this->sejour_id) || $this->_class == 'CSejour')
          ) {
            $acte->getForbiddenComplements();
            $sejour                = $this->loadRefSejour();
            $acte->prescripteur_id = $sejour->praticien_id;

            $date_execution = new CMbDay($acte->execution);
            $time_execution = CMbDT::time($acte->execution);

            if ($date_execution->ferie && !in_array("F", $acte->_forbidden_complements)) {
              $acte->complement = "F";
            }
            elseif ((($time_execution >= "20:00:00" && $time_execution <= "00:00:00")
                || ($time_execution >= "06:00:00" && $time_execution <= "08:00:00"))
              && !in_array("N", $acte->_forbidden_complements)
            ) {
              $acte->complement = "N";
            }
          }
        } elseif ($acte_class === 'CActeLPP') {
          /** @var CActeLPP $acte */
          $acte->date = CMbDT::date($acte->execution);
        }
        if (!$acte->countMatchingList()) {
          if ($msg = $acte->store()) {
            return $msg;
          }
        }
      }
    }

    return null;
  }

  /**
   * Ajout des actes ccam d'un tarif dans une intervention ou consultation
   *
   * @param string $chir l'executant de l'acte
   *
   * @return string $msg
   */
  function precodeCCAM($chir) {
    $execution = $this->_acte_execution;

    // Explode des codes_ccam du tarif
    $codes_ccam = explode("|", $this->codes_ccam);
    foreach ($codes_ccam as $_code) {
      $acte                = new CActeCCAM();
      $acte->_adapt_object = true;

      $acte->_preserve_montant = true;
      $acte->facturable   = 1;
      $acte->setFullCode($_code);

      // si le code ccam est composé de 3 elements, on le precode
      if ($acte->code_activite != "" && $acte->code_phase != "") {
        // Permet de sauvegarder le montant de base de l'acte CCAM
        $acte->_calcul_montant_base = 1;

        // Mise a jour de codes_ccam suivant les _tokens_ccam du tarif
        $acte->object_id    = $this->_id;
        $acte->object_class = $this->_class;
        $acte->executant_id = $chir;
        $acte->execution    = $this->_acte_execution;
        if ($msg = $acte->store()) {
          return $msg;
        }

        $this->_acte_execution = $execution;
      }
    }

    return null;
  }

  /**
   * @return CStoredObject|null
   * @throws Exception
   */
  function loadRefConsultRelated() {
    return $this->_ref_consult_related = $this->loadFwdRef("consult_related_id", true);
  }

  /**
   * Method to get text fields from Codable
   *
   * @return array
   */
  function getTextcontent() {
    $fields = array();
    foreach ($this->_specs as $_name => $_spec) {
      if ($_spec instanceof CTextSpec || $_spec instanceof CStrSpec) {
        $fields[] = $_name;
      }
    }

    return $fields;
  }
}
