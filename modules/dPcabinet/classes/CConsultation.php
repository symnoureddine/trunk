<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet;

use DateTime;
use Exception;
use Ox\AppFine\Client\CAppFineClient;
use Ox\AppFine\Client\CAppFineClientFolderLiaison;
use Ox\AppFine\Client\CAppFineClientObjectReceived;
use Ox\AppFine\Client\CAppFineClientOrderItem;
use Ox\AppFine\Client\CAppFineClientRelaunchFolder;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\Handlers\Events\ObjectHandlerEvent;
use Ox\Core\Module\CModule;
use Ox\Import\Framework\ImportableInterface;
use Ox\Import\Framework\Matcher\MatcherVisitorInterface;
use Ox\Import\Framework\Persister\PersisterVisitorInterface;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\Rgpd\CRGPDManager;
use Ox\Mediboard\Admin\Rgpd\IRGPDEvent;
use Ox\Mediboard\Ameli\CAvisArretTravail;
use Ox\Mediboard\Brancardage\CBrancardage;
use Ox\Mediboard\Ccam\CCodageCCAM;
use Ox\Mediboard\Cim10\CCodeCIM10;
use Ox\Mediboard\Doctolib\CDoctolib;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Facturation\CFacturable;
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Fse\CFSE;
use Ox\Mediboard\Fse\CFseFactory;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Maternite\CSuiviGrossesse;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Notifications\CNotification;
use Ox\Mediboard\Notifications\CNotificationEvent;
use Ox\Mediboard\OxPyxvital\CPyxvitalCPS;
use Ox\Mediboard\OxPyxvital\CPyxvitalCV;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSE;
use Ox\Mediboard\OxPyxvital\CSesamVitaleRuleSet;
use Ox\Mediboard\Patients\CConstantesMedicales;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\IGroupRelated;
use Ox\Mediboard\Patients\IPatientRelated;
use Ox\Mediboard\PlanningOp\CChargePriceIndicator;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanSoins\CAdministration;
use Ox\Mediboard\Prescription\CElementPrescription;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Prescription\CPrescriptionLineElement;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\Search\IIndexableObject;
use Ox\Mediboard\Soins\CSejourTask;
use Ox\Mediboard\System\CPreferences;
use Ox\Mediboard\System\Forms\CExObject;
use Ox\Mediboard\Transport\CTransport;
use Ox\Mediboard\Web100T\CWeb100TSejour;

/**
 * Consultation d'un patient par un praticien, éventuellement pendant un séjour
 * Un des évenements fondamentaux du dossier patient avec l'intervention
 */
class CConsultation extends CFacturable implements IPatientRelated, IIndexableObject, IGroupRelated, IRGPDEvent, ImportableInterface {

    /** @var string  */
    public const RESOURCE_NAME = 'consultation';

    /** @var string  */
    public const RELATION_PATIENT = 'patient';
    /** @var string  */
    public const RELATION_PLAGE_CONSULT = 'plageConsult';
    /** @var string  */
    public const RELATION_CATEGORIE = 'categorie';

    /** @var string */
    public const FIELDSET_AUTHOR = 'author';
    /** @var string */
    public const FIELDSET_STATUS = 'status';
    /** @var string */
    public const FIELDSET_EXAMEN = 'examen';

  const DEMANDE = 8;
  const PLANIFIE = 16;
  const PATIENT_ARRIVE = 32;
  const EN_COURS = 48;
  const TERMINE = 64;

  // DB Table key
  public $consultation_id;

  // DB References
  public $owner_id;
  public $plageconsult_id;
  public $patient_id;
  public $sejour_id;
  public $categorie_id;
  public $grossesse_id;
  public $element_prescription_id;

  // DB fields
  public $creation_date;
  public $type;
  public $heure;
  public $duree;
  public $secteur1;
  public $secteur2;
  public $secteur3; // Assujetti à la TVA
  public $du_tva;
  public $taux_tva;
  public $chrono;
  public $annule;
  public $motif_annulation;
  public $suspendu;

  public $motif;
  public $rques;
  public $examen;
  public $histoire_maladie;
  public $brancardage;
  public $projet_soins;
  public $conclusion;
  public $resultats;

  public $traitement;
  public $premiere;
  public $derniere;
  public $adresse; // Le patient a-t'il été adressé ?
  public $adresse_par_prat_id;

  public $arrivee;
  public $valide; // Cotation validée
  public $si_desistement;
  public $demande_nominativement; // Demandé nominativement le praticien par le patient

  public $total_assure;
  public $total_amc;
  public $total_amo;

  public $du_patient; // somme que le patient doit régler
  public $du_tiers;
  public $type_assurance;
  public $date_at;
  public $fin_at;
  public $pec_at;
  public $num_at;
  public $cle_at;
  public $reprise_at;
  public $at_sans_arret;
  public $org_at;
  public $feuille_at;
  public $arret_maladie;
  public $concerne_ALD;
  public $visite_domicile;
  public $docs_necessaires;
  public $groupee;
  public $no_patient;
  public $reunion_id;
  public $next_meeting; // Le patient doit être vu lors d'une prochaine réunion ?
  public $teleconsultation;
  public $soins_infirmiers;

  // Used when object related to external entity
  /** @var dateTime Date de création de la consultation si antérieure */
  public $date_creation_anterieure;

  /** @var string Agent extérieur associé à la consultation */
  public $agent;

  // Derived fields
  public $_etat;
  public $_hour;
  public $_min;
  public $_check_adresse;
  public $_somme;
  public $_types_examen;
  public $_precode_acte;
  public $_exam_fields;
  public $_function_secondary_id;
  public $_semaine_grossesse;
  public $_type;  // Type de la consultation
  public $_duree;
  public $_force_create_sejour;
  public $_rques_consult;
  public $_examen_consult;
  public $_line_element_id;
  public $_etat_dhe_anesth;
  public $_color_planning;
  public $_list_etat_dents;
  public $_active_grossesse;
  public $_type_suivi;
  public $_cancel_sejour;
  public $_covid_diag;

  // seances
  public $_consult_sejour_nb;
  public $_consult_sejour_out_of_nb;

  // References
  /** @var CMediusers */
  public $_ref_owner;
  /** @var CMediusers */
  public $_ref_chir;
  /** @var CPlageconsult */
  public $_ref_plageconsult;
  /** @var CMedecin */
  public $_ref_adresse_par_prat;
  /** @var CGroups */
  public $_ref_group;
  /** @var CConsultAnesth */
  public $_ref_consult_anesth;
  /** @var CExamAudio */
  public $_ref_examaudio;
  /** @var CExamNyha */
  public $_ref_examnyha;
  /** @var CExamPossum */
  public $_ref_exampossum;
  /** @var CGrossesse */
  public $_ref_grossesse;
  /** @var CPrescription */
  public $_ref_prescription;
  /** @var CConsultationCategorie */
  public $_ref_categorie;
  /** @var CSejourTask */
  public $_ref_task;
  /** @var  CSuiviGrossesse */
  public $_ref_suivi_grossesse;
  /** @var CElementPrescription */
  public $_ref_element_prescription;
  /** @var CBrancardage */
  public $_ref_brancardage;
  /** @var CReunion */
  public $_ref_reunion;
  /** @var CAccidentTravail */
  public $_ref_accident_travail;
  /** @var CRoom */
  public $_ref_room;

  // Collections
  /** @var CConsultAnesth[] */
  public $_refs_dossiers_anesth = [];
  /** @var  CExamComp[] */
  public $_ref_examcomp = [];
  /** @var  CInfoChecklistItem[] */
  public $_refs_info_check_items = [];
  /** @var  CInfoChecklist[] */
  public $_refs_info_checklist = [];
  /** @var  CInfoChecklistItem */
  public $_ref_info_checklist_item;
  /** @var CAppFineClientFolderLiaison */
  public $_ref_appfine_client_folder;
  /** @var CTransport[] */
  public $_refs_transports = [];
  /** @var CAvisArretTravail[] */
  public $_refs_avis_arrets_travail = [];
  /** @var CAppFineClientOrderItem */
  public $_ref_orders_item;
  /** @var CAppFineClientObjectReceived[] */
  public $_ref_objects_received = [];
  /** @var CAppFineClientRelaunchFolder[] */
  public $_refs_appfine_client_folders_relaunch;
  /** @var CReservation[] */
  public $_ref_reserved_ressources = [];

  // Counts
  public $_count_fiches_examen;
  public $_count_matching_sejours;
  public $_count_prescriptions;

  // AppFine
  public $_count_order_sent;

  // FSE
  public $_bind_fse;
  public $_ids_fse;
  public $_ext_fses;
  /** @var  CFSE */
  public $_current_fse;
  public $_fse_intermax;
  public $_category_facturation;

  // Distant fields
  public $_date;
  public $_datetime;
  public $_date_fin;
  public $_is_anesth;
  public $_is_dentiste;
  public $_forfait_se;
  public $_forfait_sd;
  public $_facturable;
  public $_uf_soins_id;
  public $_uf_medicale_id;
  public $_charge_id;
  public $_unique_lit_id;
  public $_service_id;
  public $_mode_entree;
  public $_mode_entree_id;
  public $_rappel; // For meetings
  /** @var  CConstantesMedicales[] */
  public $_list_constantes_medicales = [];
  // Semaine d'aménorrhée
  public $_sa;
  public $_ja;

  // Filter Fields
  public $_date_min;
  public $_date_max;
  public $_prat_id;
  public $_etat_reglement_patient;
  public $_etat_reglement_tiers;
  public $_etat_accident_travail;
  public $_type_affichage;
  public $_all_group_money;
  public $_all_group_compta;
  public $_function_compta;
  public $_telephone;
  public $_coordonnees;
  public $_plages_vides;
  public $_empty_places;
  public $_non_pourvues;
  public $_print_ipp;
  public $_date_souscription_optam;

  // Behaviour fields
  public $_no_synchro_eai = false;
  public $_adjust_sejour;
  public $_operation_id;
  public $_dossier_anesth_completed_id;
  public $_docitems_from_dossier_anesth;
  public $_locks;
  public $_handler_external_booking;
  public $_list_forms = [];
  public $_skip_count = false;
  public $_sync_consults_from_sejour = false;     // used to allow CSejour::store to avoid consultation's sejour patient check
  public $_sync_sejour = true;
  public $_create_sejour_activite_mixte = false;
  public $_sync_parcours_soins = true;
  public $_transfert_rpu;

  // Field used in purgeEtablissement
  public $_check_prat_change = true;

  public $_is_importing = false;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();

    $spec->table       = 'consultation';
    $spec->key         = 'consultation_id';
    $spec->measureable = true;

    $spec->events = array(
      'prise_rdv'                    => array(
        'reference1' => array('CSejour', 'sejour_id'),
        'reference2' => array('CPatient', 'patient_id'),
      ),
      'prise_rdv_auto'               => array(
        'auto'       => true,
        'reference1' => array('CSejour', 'sejour_id'),
        'reference2' => array('CPatient', 'patient_id'),
      ),
      'examen'                       => array(
        'reference1' => array('CSejour', 'sejour_id'),
        'reference2' => array('CPatient', 'patient_id'),
      ),
      'tab_examen'                   => array(
        'reference1'  => array('CSejour', 'sejour_id'),
        'reference2'  => array('CPatient', 'patient_id'),
        'tab'         => true,
        'tab_actions' => array(),
      ),
      'tab_dossier_soins_obs_entree' => array(
        'reference1'  => array('CSejour', 'sejour_id'),
        'reference2'  => array('CPatient', 'patient_id'),
        'tab'         => true,
        'tab_actions' => array(
          array(
            'title'    => 'CConsultation-new_obs_entree', // Button title
            'class'    => 'change', // Button class
            'callback' => 'createObsEntree', // Method name, that will be called on $this, with the "formTabAction_" prefix
          ),
        ),
      ),
    );

    static $appFine = null;
    if ($appFine === null) {
      $appFine = CModule::getActive("appFineClient") !== null;
    }

    if ($appFine) {
      $spec->events["appFine"] = array(
        "reference1" => array("CMediusers", "praticien_id"),
        "reference2" => array("CPatient", "patient_id"),
      );
    }

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {

    $props = parent::getProps();

    $props["owner_id"]                = "ref class|CMediusers show|0 back|consultations fieldset|author";
    $props["plageconsult_id"]         = "ref notNull class|CPlageconsult seekable show|1 back|consultations fieldset|default";
    $props["patient_id"]              = "ref class|CPatient purgeable seekable show|1 back|consultations fieldset|default";
    $props["sejour_id"]               = "ref class|CSejour back|consultations fieldset|default";
    $props["categorie_id"]            = "ref class|CConsultationCategorie show|1 nullify back|consultations fieldset|extra";
    $props["grossesse_id"]            = "ref class|CGrossesse show|0 unlink back|consultations";
    $props["element_prescription_id"] = "ref class|CElementPrescription back|consultations";
    $props["consult_related_id"]      = "ref class|CConsultation show|0 back|consults_liees";
    $props["creation_date"]           = "dateTime";
    $props["motif"]                   = "text helped seekable markdown|true fieldset|examen";
    $props["type"]                    = "enum list|classique|entree|chimio default|classique";
    $props["heure"]                   = "time notNull show|0 fieldset|default";
    $props["duree"]                   = "num min|1 max|255 notNull default|1 show|0 fieldset|default";
    $props["secteur1"]                = "currency min|0 show|0";
    $props["secteur2"]                = "currency show|0";
    $props["secteur3"]                = "currency show|0";
    $props["taux_tva"]                = "float";
    $props["du_tva"]                  = "currency show|0";
    $props["chrono"]                  = "enum notNull list|8|16|32|48|64 show|0 fieldset|status";
    $props["annule"]                  = "bool show|0 default|0 notNull fieldset|status";
    $props["motif_annulation"]        = "enum list|not_arrived|by_patient|other fieldset|status";
    $props["_etat"]                   = "str";
    $props["suspendu"]                = "bool show|0 default|0 notNull";

    $props["rques"]            = "text helped seekable markdown|true fieldset|examen";
    $props["examen"]           = "text helped seekable markdown|true fieldset|examen";
    $props["traitement"]       = "text helped seekable markdown|true";
    $props["histoire_maladie"] = "text helped seekable markdown|true fieldset|examen";
    $props["brancardage"]      = "text helped seekable markdown|true";
    $props["projet_soins"]     = "text helped seekable markdown|true";
    $props["conclusion"]       = "text helped seekable markdown|true fieldset|examen";
    $props["resultats"]        = "text helped seekable markdown|true";
    $props["soins_infirmiers"] = "text helped seekable markdown|true";

    $props["facture"] = "bool default|0 show|0";

    $props["premiere"]            = "bool show|0";
    $props["derniere"]            = "bool show|0";
    $props["adresse"]             = "bool show|0";
    $props["adresse_par_prat_id"] = "ref class|CMedecin nullify back|consultations_adresses";
    $props["arrivee"]             = "dateTime show|0 fieldset|examen";
    $props["concerne_ALD"]        = "bool";
    $props["visite_domicile"]     = "bool default|0";

    $props["du_patient"] = "currency show|0";
    $props["du_tiers"]   = "currency show|0";

    $props["type_assurance"] = "enum list|classique|at|maternite|smg";
    $props["date_at"]        = "date";
    $props["fin_at"]         = "dateTime";
    $props["num_at"]         = "num length|8";
    $props["cle_at"]         = "num length|1";
    $props['feuille_at']     = 'bool default|0';
    $props['org_at']         = 'numchar length|9';

    $props["pec_at"]        = "enum list|soins|arret";
    $props["reprise_at"]    = "dateTime";
    $props["at_sans_arret"] = "bool default|0";
    $props["arret_maladie"] = "bool default|0";

    $props["total_amo"]    = "currency show|0";
    $props["total_amc"]    = "currency show|0";
    $props["total_assure"] = "currency show|0";

    $props["valide"]                 = "bool show|0 fieldset|extra";
    $props["si_desistement"]         = "bool notNull default|0";
    $props["demande_nominativement"] = "bool notNull default|0";
    $props["docs_necessaires"]       = "text helped show|0";
    $props["groupee"]                = "bool default|0";
    $props["no_patient"]             = "bool default|0";

    $props['date_creation_anterieure'] = 'dateTime';
    $props['agent']                    = 'str';

    $props["reunion_id"]       = "ref class|CReunion back|consultation cascade";
    $props["next_meeting"]     = "bool default|0";
    $props["teleconsultation"] = "bool default|0";

    $props["_etat_reglement_patient"]  = "enum list|reglee|non_reglee";
    $props["_etat_reglement_tiers"]    = "enum list|reglee|non_reglee";
    $props["_etat_accident_travail"]   = "enum list|yes|no";
    $props["_forfait_se"]              = "bool default|0";
    $props["_forfait_sd"]              = "bool default|0";
    $props["_facturable"]              = "bool default|1";
    $props["_uf_soins_id"]             = "ref class|CUniteFonctionnelle seekable";
    $props["_uf_medicale_id"]          = "ref class|CUniteFonctionnelle seekable";
    $props["_charge_id"]               = "ref class|CChargePriceIndicator seekable";
    $props['_date_souscription_optam'] = 'date';

    $props["_date"]             = "date";
    $props["_datetime"]         = "dateTime notNull show|1";
    $props["_date_min"]         = "date";
    $props["_date_max"]         = "date moreEquals|_date_min";
    $props["_type_affichage"]   = "enum list|complete|totaux";
    $props["_all_group_compta"] = "bool default|1";
    $props["_all_group_money"]  = "bool default|1";
    $props['_function_compta']  = 'bool default|0';
    $props["_telephone"]        = "bool default|0";
    $props["_coordonnees"]      = "bool default|0";
    $props["_plages_vides"]     = "bool default|1";
    $props["_non_pourvues"]     = "bool default|1";
    $props["_print_ipp"]        = "bool default|" . CAppUI::gconf("dPcabinet CConsultation show_IPP_print_consult");
    $props["_sa"]               = "num";
    $props["_ja"]               = "num";
    $props["_active_grossesse"] = "bool";

    $props["_check_adresse"] = "";
    $props["_somme"]         = "currency";
    $props["_type"]          = "enum list|urg|anesth";

    $props["_prat_id"]               = "ref class|CMediusers notNull";
    $props["_praticien_id"]          = "ref class|CMediusers show|1";
    $props["_function_secondary_id"] = "ref class|CFunctions";
    $props["_operation_id"]          = "ref class|COperation";

    $props["_rappel"]        = "bool default|0";
    $props["_cancel_sejour"] = "bool default|0";

    return $props;
  }

  /**
   * Calcule l'état visible d'une consultation
   *
   * @return string
   */
  function getEtat() {
    $etat                       = array();
    $etat[self::PLANIFIE]       = CAppUI::tr('common-action-Plan-court');
    $etat[self::PATIENT_ARRIVE] = CMbDT::format($this->arrivee, "%Hh%M");
    $etat[self::EN_COURS]       = CAppUI::tr('common-In progress');
    $etat[self::TERMINE]        = CAppUI::tr('common-Completed-court');

    if ($this->chrono) {
      $this->_etat = $etat[$this->chrono];
    }

    if ($this->annule) {
      $this->_etat = CAppUI::tr('common-Canceled-court');
    }

    return $this->_etat;
  }

  /**
   * @see parent::getTemplateClasses()
   */
  function getTemplateClasses() {
    $this->loadRefsFwd();

    $tab = array();

    // Stockage des objects liés à l'opération
    $tab['CConsultation'] = $this->_id;
    $tab['CPatient']      = $this->_ref_patient->_id;

    $tab['CConsultAnesth'] = 0;
    $tab['COperation']     = 0;
    $tab['CSejour']        = 0;

    return $tab;
  }

  /**
   * Calcul de la TVA assujetti au secteur 3
   *
   * @return int
   */
  function calculTVA() {
    return $this->du_tva = round($this->secteur3 * $this->taux_tva / 100, 2);
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->calculTVA();
    $this->_somme = (float)$this->secteur1 + (float)$this->secteur2 + $this->secteur3 + $this->du_tva;

    $this->du_patient = round($this->du_patient, 2);
    $this->du_tiers   = round($this->du_tiers, 2);

    $this->_hour          = intval(substr($this->heure, 0, 2));
    $this->_min           = intval(substr($this->heure, 3, 2));
    $this->_check_adresse = $this->adresse;

    $this->_view = CAppUI::tr('CConsultation-Consultation %s', $this->getEtat());
    $this->loadRefPlageConsult(true);
    $this->_shortview = CAppUI::tr('CConsultation-Consultation of %s - %s-court', $this->_ref_plageconsult->_ref_chir->_view, CMbDT::format($this->_ref_plageconsult->date, CAppUI::conf("date")));

    // si _coded vaut 1 alors, impossible de modifier la cotation
    $this->_coded = $this->valide;

    $this->_exam_fields = $this->getExamFields();
  }

  /**
   * @see parent::updatePlainFields()
   */
  function updatePlainFields() {
    if (($this->_hour !== null) && ($this->_min !== null)) {
      $this->heure = sprintf("%02d:%02d:00", $this->_hour, $this->_min);
    }

    // Liaison FSE prioritaire sur l'état
    if ($this->_bind_fse) {
      $this->valide = 0;
    }

    // Cas du paiement d'un séjour
    if ($this->sejour_id !== null && $this->sejour_id && $this->secteur1 !== null && $this->secteur2 !== null) {
      $urg              = $this->sejour_id && $this->_ref_sejour->_ref_rpu && $this->_ref_sejour->_ref_rpu->_id ? true : false;
      $total            = round($this->secteur1 + $this->secteur2 + $this->secteur3 + $this->du_tva, 2);
      $this->du_tiers   = $urg ? 0 : $total;
      $this->du_patient = $urg ? $total : 0;
    }
  }

  /**
   * @see parent::check()
   */
  function check() {
    // Data checking
    $msg = null;
    if (!$this->_id) {
      if (!$this->plageconsult_id) {
        $msg .= CAppUI::tr('CConsultation-msg-Invalid consultation range');
      }

      return $msg . parent::check();
    }

    $this->loadOldObject();
    $this->loadRefFacture()->loadRefsReglements();

    $this->completeField("sejour_id", "plageconsult_id", "heure", "valide");

    $this->loadRefPlageConsult();
    if ($this->_check_bounds) {
      if ($this->sejour_id && !$this->_forwardRefMerging) {
        $sejour = $this->loadRefSejour();

        if (!$this->fieldModified("annule", "1") && $sejour->type != "consult" &&
          ($this->_date < CMbDT::date($sejour->entree) || CMbDT::date($this->_date) > $sejour->sortie)
        ) {
          $msg .= CAppUI::tr('CConsultation-msg-Consultation outside of the stay');

          return $msg . parent::check();
        }
      }
    }

    if (($this->fieldModified("heure") || !$this->_id) && $this->heure && ($this->heure < $this->_ref_plageconsult->debut || $this->heure > $this->_ref_plageconsult->fin)) {
      $msg .= CAppUI::tr('CConsultation-msg-The consultation time is outside the consultation range');
    }

    /** @var self $old */
    $old = $this->_old;
    // Dévalidation avec règlement déjà effectué
    if (!$this->_is_importing && $this->fieldModified("valide", "0")) {
      // Bien tester sur _old car valide = 0 s'accompagne systématiquement d'un facture_id = 0
      if (count($old->loadRefFacture()->loadRefsReglements())) {
        $msg .= CAppUI::tr('CConsultation-msg-You can no longer cancel the tariff, invoice payments have already been made');
      }
    }

    if (!($this->_merging || $this->_mergeDeletion || $this->_forwardRefMerging || $this->_transfert_rpu || $this->_is_importing) && $old->valide === "1" && $this->valide === "1") {
      // Modification du tarif déjà validé
      if (
        $this->fieldModified("secteur1") ||
        $this->fieldModified("secteur2") ||
        $this->fieldModified("total_assure") ||
        $this->fieldModified("total_amc") ||
        $this->fieldModified("total_amo") ||
        $this->fieldModified("du_patient") ||
        $this->fieldModified("du_tiers")
      ) {
        $msg .= CAppUI::tr('CConsultation-msg-You can no longer modify the tariff, it is already validated');
      }
    }

    if ($this->valide && $this->sejour_id && $this->fieldModified("sejour_id") && !$old->sejour_id) {
      $msg .= CAppUI::tr('CConsultation-msg-no_associate_sejour_with_consult_valid');
    }

    return $msg . parent::check();
  }

  /**
   * @see parent::loadView()
   */
  function loadView() {
    parent::loadView();
    $this->loadRefPatient()->loadRefPhotoIdentite();
    $this->loadRefsFichesExamen();
    $this->loadRefsActesNGAP();
    $this->loadRefCategorie();
    $this->loadRefPlageConsult(1);
    $this->_ref_chir->loadRefFunction();
    $this->loadRefBrancardage();
    $this->loadRefSejour();
    $this->_ref_categorie->getSessionOrder($this->_ref_patient->_id);

    $group_id = $this->loadRefGroup()->_id;
    // Compteur appFine des demandes
    if (CModule::getActive("appFineClient")) {
      CAppFineClient::loadIdex($this, $group_id);
      CAppFineClient::loadIdex($this->_ref_patient, $group_id);
      CAppFineClient::countOrders($this);
      $this->_ref_patient->loadRefStatusPatientUser();
      $this->loadRefsFoldersRelaunchByType();
    }

    // Iconographie de la consultation sur les systèmes tiers
    $this->loadExternalIdentifiers($group_id);

    if (CModule::getActive("transport")) {
      $this->loadRefsTransports();
    }
  }

  /**
   * Charge le créateur de la consultation
   *
   * @return CMediusers
   */
  function loadRefOwner() {
    return $this->_ref_owner = $this->loadFwdRef("owner_id", true);
  }

  /**
   * @see parent::deleteActes()
   */
  function deleteActes() {
    if ($msg = parent::deleteActes()) {
      return $msg;
    }

    $this->secteur1 = "";
    $this->secteur2 = "";
    // $this->valide = 0;  Ne devrait pas être nécessaire
    $this->total_assure = 0.0;
    $this->total_amc    = 0.0;
    $this->total_amo    = 0.0;
    $this->du_patient   = 0.0;
    $this->du_tiers     = 0.0;

    return $this->store();
  }

  /**
   * @see parent::bindTarif()
   */
  function bindTarif() {
    $this->_bind_tarif = false;

    // Chargement du tarif
    $tarif = new CTarif();
    $tarif->load($this->_tarif_id);

    // Cas de la cotation normale
    $this->secteur1 += $tarif->secteur1;
    $this->secteur2 += $tarif->secteur2;
    $this->secteur3 += $tarif->secteur3;
    $this->taux_tva = $tarif->taux_tva;

    if (!$this->tarif) {
      $this->tarif = $tarif->description;
    }
    // Mise à jour de codes CCAM prévus, sans information serialisée complémentaire
    foreach ($tarif->_codes_ccam as $_code_ccam) {
      $this->_codes_ccam[] = substr($_code_ccam, 0, 7);
    }
    $this->codes_ccam = $this->updateCCAMPlainField();
    if (!$this->exec_tarif) {
      $this->exec_tarif = CAppUI::pref("use_acte_date_now") ?  CMbDT::dateTime() : $this->_acte_execution;
    } elseif (CAppUI::pref("use_acte_date_now")) {
      $this->exec_tarif =  CMbDT::dateTime();
    }

    if ($msg = $this->store()) {
      return $msg;
    }

    $chir_id = $this->getExecutantId();

    $this->_acte_execution = $this->exec_tarif;

    // Precodage des actes NGAP avec information sérialisée complète
    $this->_tokens_ngap = $tarif->codes_ngap;
    if ($msg = $this->precodeActe("_tokens_ngap", "CActeNGAP", $chir_id)) {
      return $msg;
    }

    $this->codes_ccam = $tarif->codes_ccam;
    // Precodage des actes CCAM avec information sérialisée complète
    if ($msg = $this->precodeActeCCAM()) {
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
      if ($this->_tarif_user_id && $this->_tarif_user_id != $tarif->chir_id) {
        $user_id = $this->_tarif_user_id;
      }
      else {
        $user_id = $this->getExecutantId();
      }

      $this->_tokens_tarmed = $tarif->codes_tarmed;
      if ($msg = $this->precodeActe("_tokens_tarmed", "CActeTarmed", $user_id)) {
        return $msg;
      }
      $this->_tokens_caisse = $tarif->codes_caisse;
      if ($msg = $this->precodeActe("_tokens_caisse", "CActeCaisse", $user_id)) {
        return $msg;
      }
    }

    $this->loadRefsActes();

    if ($this->concerne_ALD) {
      foreach ($this->_ref_actes_ngap as $_acte_ngap) {
        $_acte_ngap->ald = 1;
        $_acte_ngap->store();
      }

      foreach ($this->_ref_actes_ccam as $_acte_ccam) {
        $_acte_ccam->ald = 1;
        $_acte_ccam->store();
      }
    }

    $this->calculTVA();
    if (is_array($this->_ref_actes) && count($this->_ref_actes)) {
        $this->doUpdateMontants();
    }

    $this->du_patient = $this->secteur1 + $this->secteur2 + $this->secteur3 + $this->du_tva;

    return null;
  }


  function loadPosition() {
    if (!$this->sejour_id) {
      return;
    }

    $ds   = $this->getDS();
    $sql  = "SELECT type FROM sejour WHERE sejour_id = '$this->sejour_id'";
    $type = $ds->loadResult($sql);

    // only for seances
    if ($type != "seances") {
      return;
    }

    $sql       = "SELECT consultation.plageconsult_id, date, heure, consultation_id
    FROM plageconsult, consultation
    WHERE consultation.plageconsult_id = plageconsult.plageconsult_id
      AND sejour_id = '$this->sejour_id'
      AND annule = '0'
    ORDER BY date, heure";
    $list      = $ds->loadList($sql);
    $seance_nb = 1;
    foreach ($list as $_seance) {
      if ($_seance["heure"] == $this->heure && $_seance["plageconsult_id"] == $this->plageconsult_id) {
        $this->_consult_sejour_nb = $seance_nb;
        break;
      }
      $seance_nb++;
    }
    $this->_consult_sejour_out_of_nb = count($list);
  }

  /**
   * Précode les actes CCAM prévus de la consultation
   *
   * @return string Store-like message
   */
  function precodeActeCCAM() {
    $this->loadRefPlageConsult();
    $this->precodeCCAM($this->_ref_chir->_id);
  }

  /**
   * @see parent::doUpdateMontants()
   */
  function doUpdateMontants() {
    // Initialisation des montants
    $secteur1_CCAM_NGAP     = 0;
    $secteur1_TARMED_CAISSE = 0;
    $secteur2_CCAM_NGAP     = 0;
    $secteur2_TARMED_CAISSE = 0;

    $this->secteur1 = 0;
    $this->secteur2 = 0;
    $this->secteur3 = 0;
    $this->loadRefsActes();
    foreach ($this->_ref_actes as $_acte) {
      switch ($_acte->_class) {
        case "CActeTarmed":
        case "CActeCaisse":
          $secteur1_TARMED_CAISSE += $_acte->montant_base * $_acte->quantite;
          $secteur2_TARMED_CAISSE += $_acte->montant_depassement;
          break;
        case "CFraisDivers":
          $this->secteur3 += $_acte->montant_base + $_acte->montant_depassement;
          break;
        case "CActeNGAP":
          $secteur1_CCAM_NGAP += $_acte->montant_base;
          $secteur2_CCAM_NGAP += $_acte->montant_depassement;
          break;
        case "CActeCCAM":
          $secteur1_CCAM_NGAP += round($_acte->getTarif(), 2);
          $secteur2_CCAM_NGAP += $_acte->montant_depassement;
          break;
        case "CActeLPP":
          $this->secteur1 += round($_acte->montant_final, 2);
          break;
        default:
          break;
      }
    }

    // Remplissage des montant de la consultation
    $this->secteur1 += $secteur1_CCAM_NGAP + round($secteur1_TARMED_CAISSE, 2);
    $this->secteur2 += $secteur2_CCAM_NGAP + round($secteur2_TARMED_CAISSE, 2);

    if ($secteur1_CCAM_NGAP == 0 && $secteur2_CCAM_NGAP == 0) {
      $this->du_patient = $this->secteur1 + $this->secteur2 + $this->secteur3 + $this->du_tva;
    }

    // Cotation manuelle
    $this->completeField("tarif");
    if (!$this->tarif && $this->_count_actes) {
      $this->tarif = "Codage manuel";
    }
    elseif (!$this->_count_actes && $this->tarif == "Codage manuel") {
      $this->tarif = "";
    }

    return $this->store();
  }

  /**
   * @see  parent::store()
   * @todo Refactoring complet de la fonction store de la consultation
   *
   *   ANALYSE DU CODE
   *  1. Gestion du désistement
   *  2. Premier if : creation d'une consultation à laquelle on doit attacher
   *     un séjour (conf active): comportement DEPART / ARRIVEE
   *  3. Mise en cache du forfait FSE et facturable : uniquement dans le cas d'un séjour
   *  4. On load le séjour de la consultation
   *  5. On initialise le _adjust_sejour à false
   *  6. Dans le cas ou on a un séjour
   *   6.1. S'il est de type consultation, on ajuste le séjour en fonction du comportement DEPART / ARRIVEE
   *   6.2. Si la plage de consultation a été modifiée, adjust_sejour passe à true et on ajuste le séjour
   *        en fonction du comportement DEPART / ARRIVEE (en passant par l'adjustSejour() )
   *   6.3. Si on a un id (à virer) et que le chrono est modifié en PATIENT_ARRIVE,
   *        si on gère les admissions auto (conf) on met une entrée réelle au séjour
   *  7. Si le patient est modifié, qu'on est pas en train de merger et qu'on a un séjour,
   *     on empeche le store
   *  8. On appelle le parent::store()
   *  9. On passe le forfait SE et facturable au séjour
   * 10. On propage la modification du patient de la consultation au séjour
   * 11. Si on a ajusté le séjour et qu'on est dans un séjour de type conclut et que le séjour
   *     n'a plus de consultations, on essaie de le supprimer, sinon on l'annule
   * 12. Gestion du tarif et précodage des actes (bindTarif)
   * 13. Bind FSE
   * ACTIONS
   * - Faire une fonction comportement_DEPART_ARRIVEE()
   * - Merger le 2, le 6.1 et le 6.2 (et le passer en 2 si possible)
   * - Faire une fonction pour le 6.3, le 7, le 10, le 11
   * - Améliorer les fonctions 12 et 13 en incluant le test du behaviour fields
   *
   * COMPORTEMENT DEPART ARRIVEE
   * modif de la date d'une consultation ayant un séjour sur le modèle DEPART / ARRIVEE:
   * 1. Pour le DEPART :
   * -> on décroche la consultation de son ancien séjour
   * -> on ne touche pas à l'ancien séjour si :
   * - il est de type autre que consultation
   * - il a une entrée réelle
   * - il a d'autres consultations
   * -> sinon on l'annule
   *
   *   2. Pour l'ARRIVEE
   * -> si on a un séjour qui englobe : on la colle dedans
   * -> sinon on crée un séjour de consultation
   *
   *   TESTS A EFFECTUER
   *  0. Création d'un pause
   *  0.1. Déplacement d'une pause
   *  1. Création d'une consultation simple C1 (Séjour S1)
   *  2. Création d'une deuxième consultation le même jour / même patient C2 (Séjour S1)
   *  3. Création d'une troisième consultation le même jour / même patient C3 (Séjour S1)
   *  4. Déplacement de la consultation C1 un autre jour (Séjour S2)
   *  5. Changement du nom du patient C2 (pas de modification car une autre consultation)
   *  6. Déplacement de C3 au même jour (Toujours séjour S1)
   *  7. Annulation de C1 (Suppression ou annulation de S1)
   *  8. Déplacement de C2 et C3 à un autre jour (séjour S3 créé, séjour S1 supprimé ou annulé)
   *  9. Arrivée du patient pour C2 (S3 a une entrée réelle)
   * 10. Déplacement de C3 dans un autre jour (S4)
   * 11. Déplacement de C2 dans un autre jour (S5 et S3 reste tel quel)
   */
  function store() {
    $this->completeField('owner_id', 'creation_date', 'sejour_id', 'heure', 'plageconsult_id', 'grossesse_id', 'si_desistement', 'annule');

    $this->loadRefPraticien()->loadRefFunction();

    if (!$this->_id || !$this->owner_id || !$this->creation_date) {
      if (!$this->_id) {
        $this->creation_date = "current";
        $this->owner_id      = CMediusers::get()->_id;
      }
      else {
        $first_log           = $this->loadFirstLog();
        $this->creation_date = $first_log->date;
        $this->owner_id      = $first_log->user_id;
      }
    }

    if (!$this->_id && !$this->sejour_id && !CMediusers::get()->isAdmin()) {
      $prefs = CPreferences::getAllPrefs($this->_ref_praticien->_id);
      if ($prefs["allowed_new_consultation"] == 0) {
        return CAppUI::tr(
          'CConsultation-msg-The creation or modification of consultation is impossible for the practitioner %s',
          $this->_ref_praticien
        );
      }
    }

    if ($this->si_desistement === null) {
      $this->si_desistement = 0;
    }

    $this->annule = $this->annule === null || $this->annule === '' ? 0 : $this->annule;

    // must be BEFORE loadRefSejour()
    $facturable     = $this->_facturable;
    $forfait_se     = $this->_forfait_se;
    $forfait_sd     = $this->_forfait_sd;
    $uf_soins_id    = $this->_uf_soins_id;
    $uf_medicale_id = $this->_uf_medicale_id;
    $charge_id      = $this->_charge_id;
    $unique_lit_id  = $this->_unique_lit_id;
    $service_id     = $this->_service_id;
    $mode_entree    = $this->_mode_entree;
    $mode_entree_id = $this->_mode_entree_id;

    $this->_adjust_sejour = false;

    $function = new CFunctions();

    if ($this->_function_secondary_id) {
      $function->load($this->_function_secondary_id);
    }
    else {
      $function = $this->_ref_chir->_ref_function;
    }

    // Consultation dans un séjour
    $sejour = $this->loadRefSejour();

    $same_year_charge_id        = CAppUI::gconf("dPcabinet CConsultation same_year_charge_id");
    $use_charge_price_indicator = CAppUI::gconf("dPplanningOp CSejour use_charge_price_indicator");

    $create_sejour_consult = false;
    if (
        $this->patient_id && !$this->sejour_id && (!$this->_id || $this->_force_create_sejour)
        && ($function->create_sejour_consult || $this->_create_sejour_activite_mixte)
    ) {
        $create_sejour_consult = true;
    }

    // On détecte également un changement du mode de traitement si config activée afin de créer un nouveau séjour
    if ($same_year_charge_id && $use_charge_price_indicator === "obl" && $sejour->_id && $charge_id && $sejour->charge_id != $charge_id) {
      $create_sejour_consult = true;
      $sejour                = new CSejour();
      $this->_ref_sejour     = $sejour;
    }

    if ($this->patient_id &&
      (!$this->_id && !$this->sejour_id && CAppUI::gconf("dPcabinet CConsultation attach_consult_sejour", $function->group_id))
      || $this->_force_create_sejour
      || $create_sejour_consult
    ) {
      // Recherche séjour englobant
      if (in_array($facturable, array("", null))) {
        $facturable = 1;
      }

      $datetime                      = $this->_datetime;
      $minutes_before_consult_sejour = CAppUI::gconf("dPcabinet CConsultation minutes_before_consult_sejour");
      $where                         = array();
      $where['annule']               = " = '0'";
      $where['type']                 = " != 'seances'";
      $where['patient_id']           = " = '$this->patient_id'";
      if (!CAppUI::gconf("dPcabinet CConsultation search_sejour_all_groups")) {
        $where['group_id'] = " = '$function->group_id'";
      }
      $where['facturable'] = " = '$facturable'";

      if ($same_year_charge_id && !$this->grossesse_id) {
        // Avec le même mode traitement
        if ($charge_id) {
          $where["sejour.charge_id"] = "= '$charge_id'";
        }
        // Même année
        $where[] = "DATE_FORMAT(sejour.entree, '%Y') = '" . CMbDT::transform(null, $this->_datetime, "%Y") . "'";
      }
      else {
        $datetime_before = CMbDT::dateTime("+$minutes_before_consult_sejour minute", "$this->_date $this->heure");
        $where[]         = "`sejour`.`entree` <= '$datetime_before' AND `sejour`.`sortie` >= '$datetime'";
      }

      if (!$this->_force_create_sejour) {
        $sejour->loadObject($where);
      }
      else {
        $sejour->_id = "";
      }

      // Si pas de séjour et config (ou que le cabinet l'y autorise) alors le créer en type consultation
      if (!$sejour->_id
        && ((CAppUI::gconf("dPcabinet CConsultation create_consult_sejour")
            && $this->_ref_praticien->activite === "salarie")
          || $create_sejour_consult)
      ) {
        $sejour->patient_id     = $this->patient_id;
        $sejour->praticien_id   = $this->_ref_chir->_id;
        $sejour->group_id       = $function->group_id;
        $sejour->type           = "consult";
        $sejour->facturable     = $facturable;
        $sejour->uf_soins_id    = $uf_soins_id;
        $sejour->uf_medicale_id = $uf_medicale_id;
        $sejour->charge_id      = $charge_id;
        $sejour->_unique_lit_id = $unique_lit_id;
        $sejour->service_id     = $service_id;
        $sejour->mode_entree    = $mode_entree;
        $sejour->mode_entree_id = $mode_entree_id;
        $sejour->grossesse_id   = $this->grossesse_id;
        $datetime               = ($this->_date && $this->heure) ? "$this->_date $this->heure" : null;
        if ($this->chrono == self::PLANIFIE) {
          $sejour->entree_prevue = $datetime;
        }
        else {
          $sejour->entree_reelle = $datetime;
        }
        $duree_sejour          = CAppUI::gconf("dPcabinet CConsultation duree_sejour_creation_rdv");
        $sejour->sortie_prevue = ($duree_sejour) ? CMbDT::dateTime("+$duree_sejour hours", $datetime) : "$this->_date 23:59:59";
        if ($msg = $sejour->store()) {
          return $msg;
        }
      }
      $this->sejour_id = $sejour->_id;
    }

    if ($this->sejour_id && $this->_sync_sejour) {
      $this->loadRefPlageConsult();

      // Si le séjour est de type consult
      if ($this->_ref_sejour->type == 'consult') {
        $this->_ref_sejour->loadRefsConsultations();
        $this->_ref_sejour->_hour_entree_prevue = null;
        $this->_ref_sejour->_min_entree_prevue  = null;
        $this->_ref_sejour->_hour_sortie_prevue = null;
        $this->_ref_sejour->_min_sortie_prevue  = null;

        $date_consult = CMbDT::date($this->_datetime);

        // On déplace l'entrée et la sortie du séjour
        $entree       = $this->_datetime;
        $duree_sejour = CAppUI::gconf("dPcabinet CConsultation duree_sejour_creation_rdv");
        $sortie       = ($duree_sejour) ? CMbDT::dateTime("+$duree_sejour hours", $entree) : $date_consult . " 23:59:59";

        // Si on a une entrée réelle et que la date de la consultation est avant l'entrée réelle, on sort du store
        if ($this->_ref_sejour->entree_reelle && $date_consult < CMbDT::date($this->_ref_sejour->entree_reelle)) {
          return CAppUI::tr("CConsultation-denyDayChange");
        }

        // Si on a une sortie réelle et que la date de la consultation est après la sortie réelle, on sort du store
        if ($this->_ref_sejour->sortie_reelle && $date_consult > CMbDT::date($this->_ref_sejour->sortie_reelle)) {
          return CAppUI::tr("CConsultation-denyDayChange-exit");
        }

        // S'il n'y a qu'une seule consultation dans le séjour, et que le praticien de la consultation est modifié
        // (changement de plage), alors on modifie également le praticien du séjour
        if ($this->_id && $this->fieldModified("plageconsult_id")
          && count($this->_ref_sejour->_ref_consultations) == 1
          && !$this->_ref_sejour->entree_reelle
        ) {
          $this->_ref_sejour->praticien_id = $this->_ref_plageconsult->chir_id;
        }

        // S'il y a d'autres consultations dans le séjour, on étire l'entrée et la sortie
        // en parcourant la liste des consultations
        foreach ($this->_ref_sejour->_ref_consultations as $_consultation) {
          if ($_consultation->_id != $this->_id) {
            $_consultation->loadRefPlageConsult();
            if ($_consultation->_datetime < $entree) {
              $entree = $_consultation->_datetime;
            }

            if ($_consultation->_datetime > $sortie) {
              $sortie = CMbDT::date($_consultation->_datetime) . " 23:59:59";
            }
          }
        }

        $this->_ref_sejour->entree_prevue = $entree;
        $this->_ref_sejour->sortie_prevue = $sortie;
        $this->_ref_sejour->updateFormFields();
        $this->_ref_sejour->_check_bounds = 0;
      }
      if (!$this->_ref_sejour->uf_soins_id) {
        $this->_ref_sejour->uf_soins_id = $uf_soins_id;
      }
      if (!$this->_ref_sejour->uf_medicale_id) {
        $this->_ref_sejour->uf_medicale_id = $uf_medicale_id;
      }
      if (!$this->_ref_sejour->charge_id) {
        $charge_price = new CChargePriceIndicator();
        $charge_price->load($charge_id);
        if ($charge_price->group_id == $this->_ref_sejour->group_id) {
          $this->_ref_sejour->charge_id = $charge_id;
        }
      }
      if (in_array($this->_ref_sejour->type, CSejour::getTypesSejoursUrgence($this->_ref_sejour->praticien_id)) && $unique_lit_id) {
        $sejour = new CSejour();
        $sejour->load($this->sejour_id);
        $affectation                = new CAffectation();
        $affectation->sejour_id     = $this->sejour_id;
        $affectation->lit_id        = $unique_lit_id;
        $affectation->service_id    = $service_id;
        $affectation->entree        = CMbDT::dateTime();
        $affectation->_mutation_urg = true;
        $sejour->forceAffectation($affectation);
      }

      if ($this->_cancel_sejour && $this->annule && !$this->_ref_sejour->annule) {
        $this->_ref_sejour->annule = 1;
      }
      $this->_ref_sejour->store();

      // Changement de journée pour la consult
      if ($this->fieldModified("plageconsult_id")) {
        $this->_adjust_sejour = true;

        // Pas le permettre si admission est déjà faite
        $max_hours = CAppUI::gconf("dPcabinet CConsultation hours_after_changing_prat");
        if ($this->_check_prat_change && $this->_ref_sejour->entree_reelle
          && CMbDT::dateTime("+ $max_hours HOUR", $this->_ref_sejour->entree_reelle) < CMbDT::dateTime()
        ) {
          return CAppUI::tr("CConsultation-denyPratChange", $max_hours);
        }

        $sejour = $this->_ref_sejour;
        $this->loadRefPlageConsult();
        $dateTimePlage = $this->_datetime;
        if (!$this->sejour_id) {
          $where               = array();
          $where['patient_id'] = " = '$this->patient_id'";
          $where[]             = "`sejour`.`entree` <= '$dateTimePlage' AND `sejour`.`sortie` >= '$dateTimePlage'";

          $sejour = new CSejour();
          $sejour->loadObject($where);
        }

        $this->adjustSejour($sejour, $dateTimePlage);
      }

      if ($this->_id && $this->fieldModified("chrono", self::PATIENT_ARRIVE)) {
        $this->completeField("plageconsult_id");
        $this->loadRefPlageConsult();
        $this->_ref_chir->loadRefFunction();
        $function = $this->_ref_chir->_ref_function;
        if ($function->admission_auto) {
          $sejour = new CSejour();
          $sejour->load($this->sejour_id);
          $sejour->entree_reelle = $this->arrivee;
          if ($msg = $sejour->store()) {
            return $msg;
          }
        }
      }
    }

    $patient_modified = $this->fieldModified("patient_id");

    // Si le patient est modifié et qu'il y a plus d'une consult dans le sejour, on empeche le store
    if (!$this->_forwardRefMerging && $this->sejour_id && $patient_modified && !$this->_skip_count && !$this->_sync_consults_from_sejour) {
      $this->loadRefSejour();
      $consultations = $this->_ref_sejour->countBackRefs("consultations");
      if ($consultations > 1) {
        return CAppUI::tr('CConsultation-msg-You can not change the patient from a consultation if it is contained in a stay. Dissociate the consultation or change the patient s stay.');
      }
    }

    // Synchronisation AT
    $this->getType();

    if (in_array($this->_type, CSejour::getTypesSejoursUrgence($this->_ref_sejour->praticien_id)) && $this->fieldModified("date_at")) {
      $rpu = $this->_ref_sejour->_ref_rpu;
      if (!$rpu->_date_at) {
        $rpu->_date_at = true;
        $rpu->date_at  = $this->date_at;
        if ($msg = $rpu->store()) {
          return $msg;
        }
      }
    }

    //Une consultation d'urgence ne doit pas être terminé tant qu'une inscription est présente
    $group = CGroups::loadCurrent();
    if (in_array($this->_type, CSejour::getTypesSejoursUrgence($this->_ref_sejour->praticien_id)) && $this->fieldModified("chrono", self::TERMINE)
      && !CAppUI::gconf("dPurgences CConsultation close_urg_with_inscription")) {
      $prescription = $this->_ref_sejour->loadRefPrescriptionSejour();
      $prescription->loadRefsLinesInscriptions();
      if ($prescription->_count_inscriptions) {
        return CAppUI::tr("CConsultation.no_termine.alert_inscriptions");
      }
    }

    // Update de reprise at
    // Par défaut, j+1 par rapport à fin at
    if ($this->fieldModified("fin_at") && $this->fin_at) {
      $this->reprise_at = CMbDT::dateTime("+1 DAY", $this->fin_at);
    }

    //Lors de la validation de la consultation
    // Enregistrement de la facture
    if ($this->fieldModified("valide", "1")) {
      //Si le DH est modifié, ceui ci se répercute sur le premier acte coté
      if ($this->fieldModified("secteur2") && (count($this->_tokens_ngap)
          || count($this->_tokens_ccam)) && count($this->loadRefsActes())
      ) {
        if (count($this->_ref_actes) === 1) {
          $acte                      = reset($this->_ref_actes);
          $acte->_check_coded        = false;
          $acte->montant_depassement += ($this->secteur2 - $this->_old->secteur2);
          if ($msg = $acte->store()) {
            return $msg;
          }
        }
        /* Si il y a plus d'un acte, on vérifie le total des dépassement d'honoraires */
        else {
          $total_dh = 0;
          foreach ($this->_ref_actes as $_act) {
            $total_dh += $_act->montant_depassement;
          }

          /* Si le secteur 2 est différent du total des dépassement des actes, on met le dépassement sur le 1er acte */
          if ($total_dh != $this->secteur2) {
            $_act                      = reset($this->_ref_actes);
            $_act->_check_coded        = false;
            $_act->montant_depassement = $this->secteur2;
            if ($msg = $_act->store()) {
              return $msg;
            }

            while ($_act = next($this->_ref_actes)) {
              if ($_act->montant_depassement) {
                $_act->_check_coded        = false;
                $_act->montant_depassement = 0;
                if ($msg = $_act->store()) {
                  return $msg;
                }
              }
            }
          }
        }
      }

      if ($msg = CFacture::save($this)) {
        echo $msg;
      }
    }

    //Lors de dévalidation de la consultation
    if (!$this->_is_importing && $this->fieldModified("valide", "0")) {
      $reglements = $this->loadRefFacture()->loadRefsReglements();
      if (!count($reglements)) {
        /* Annulation de l'ensemble des factures de la consultation
         * Il peut y en avoir plusieurs d'actives en même temps (ex des factures n°x de frais divers) */
        foreach ($this->_ref_factures as $_facture) {
          $_facture->cancelFacture($this);
        }
      }
      else {
        return CAppUI::tr('CConsultation-msg-You can not reopen a consultation with payments');
      }
    }

    if ($this->fieldModified("annule", "1")) {
      $this->loadRefConsultAnesth();
      foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
        if ($_dossier_anesth->operation_id) {
          $_dossier_anesth->operation_id = '';
          if ($msg = $_dossier_anesth->store()) {
            return $msg;
          }
        }
      }
    }

    if ($this->fieldModified("annule", "0") || ($this->annule == 0 && $this->motif_annulation)) {
      $this->motif_annulation = "";
    }

    /* Propagation of the field concern_ALD */
    if ($this->fieldModified('concerne_ALD')) {
      /* To the CSejour */
      if ($this->sejour_id) {
        $this->loadRefSejour();
        $this->_ref_sejour->ald = $this->concerne_ALD;
        $this->_ref_sejour->store();
      }

      $this->loadRefsActes();

      /* To the CActeNGAP */
      foreach ($this->_ref_actes_ngap as $_acte_ngap) {
        $_acte_ngap->_check_coded = false;
        $_acte_ngap->ald          = $this->concerne_ALD;
        $msg                      = $_acte_ngap->store();

        if ($msg) {
          return $msg;
        }
      }

      /* To the CActeCCAM */
      foreach ($this->_ref_actes_ccam as $_acte_ccam) {
        $_acte_ccam->_check_coded = false;
        $_acte_ccam->ald          = $this->concerne_ALD;
        $msg                      = $_acte_ccam->store();

        if ($msg) {
          return $msg;
        }
      }

      /* To the CActeLPP */
      foreach ($this->_ref_actes_lpp as $_acte_lpp) {
        $_acte_lpp->_check_coded = false;
        $_acte_lpp->concerne_ald = $this->concerne_ALD;
        $msg                     = $_acte_lpp->store();

        if ($msg) {
          return $msg;
        }
      }
    }

    // Enregistrer un groupe séance
    if (!$this->_id) {
      $categorie = $this->loadRefCategorie();

      if ($categorie->_id && $categorie->seance) {
        $groupe_seance              = new CGroupeSeance();
        $groupe_seance->patient_id  = $this->patient_id;
        $groupe_seance->function_id = $this->_ref_praticien->_ref_function->_id;
        $groupe_seance->category_id = $this->categorie_id;

        $groupe_seance->store();
      }
    }

    /* Vidage du champ adresse_par_prat_id si le champ adresse est mis à 0 */
    if ($this->fieldModified('adresse') && $this->adresse == '0') {
      $this->completeField('adresse_par_prat_id');
      if ($this->adresse_par_prat_id) {
        $this->adresse_par_prat_id = '';
      }
    }

    /* Modifie le parcours de soins de la feuille de soins associée si celle ci existe */
    if ($this->_sync_parcours_soins && CModule::getActive('oxPyxvital')
      && ($this->fieldModified('adresse') || $this->fieldModified('adresse_par_prat_id'))
    ) {
      $fses = CPyxvitalFSE::loadForConsult($this);

      foreach ($fses as $fse) {
        if ($fse->_id && $fse->state == 'creating') {
          $rules                          = new CSesamVitaleRuleSet(new CPyxvitalCPS(), new CPyxvitalCV(), $fse);
          $fse->_parcours_de_soins        = $rules->isPdSneeded();
          $fse->_synchronize_consultation = false;

          if ($fse->_parcours_de_soins && $this->adresse && $this->adresse_par_prat_id) {
            $patient = $this->loadRefPatient();
            /** @var CMedecin $medecin */
            $medecin = CMbObject::loadFromGuid("CMedecin-$this->adresse_par_prat_id");

            if ($medecin->_id == $patient->medecin_traitant) {
              $fse->mt_code_pds = '11';
              $fse->medecin_id  = $medecin->_id;
              $fse->mt_nom      = $medecin->nom;
              $fse->mt_prenom   = $medecin->prenom;
              $fse->mt_top_mt   = '1';
            }
            else {
              $fse->mt_code_pds = '12';
              $fse->medecin_id  = $medecin->_id;
              $fse->mt_nom      = $medecin->nom;
              $fse->mt_prenom   = $medecin->prenom;
              $fse->mt_top_mt   = $patient->medecin_traitant ? '1' : '0';
            }
          }
          elseif ($fse->_parcours_de_soins) {
            $fse->mt_code_pds = '';
            $fse->medecin_id  = '';
            $fse->mt_nom      = '';
            $fse->mt_prenom   = '';
            $fse->mt_top_mt   = '';
          }

          $fse->store();
        }
      }
    }

    // Standard store
    if ($msg = parent::store()) {
      return $msg;
    }

    if (CAppUI::pref("create_dossier_anesth")) {
      $this->createConsultAnesth();
    }

    $this->completeField("_line_element_id");

    // Création d'une tâche si la prise de rdv est issue du plan de soin
    if ($this->_line_element_id) {
      $task                               = new CSejourTask();
      $task->consult_id                   = $this->_id;
      $task->sejour_id                    = $this->sejour_id;
      $task->prescription_line_element_id = $this->_line_element_id;
      $task->description                  = CAppUI::tr('CConsultation-Consultation scheduled for %s', $this->_ref_plageconsult->getFormattedValue("date"));

      $line_element = new CPrescriptionLineElement();
      $line_element->load($this->_line_element_id);
      $this->motif = ($this->motif ? "$this->motif\n" : "") . $line_element->_view;
      $this->rques = ($this->rques ? "$this->rques\n" : "") .
        CAppUI::tr('CConsultation-Prescription of hospitalization, prescribed by Dr %s', $line_element->_ref_praticien->_view);

      $line_element->loadRefsPrises();
      $first_prise = reset($line_element->_ref_prises);

      $key_tab  = "aucune_prise";
      $prise_id = null;

      if ($first_prise) {
        $key_tab = ($first_prise->moment_unitaire_id
          || $first_prise->heure_prise
          || $first_prise->condition
          || $first_prise->datetime) ? $line_element->_chapitre : null;

        if (!$key_tab) {
          $prise_id = $first_prise->_id;
        }
      }

      // Planification manuelle à l'heure de la consultation
      if (CPrescription::isPlanSoinsActive()) {
        $administration                      = new CAdministration();
        $administration->administrateur_id   = CAppUI::$user->_id;
        $administration->dateTime            = $this->_datetime;
        $administration->quantite            = $administration->planification = 1;
        $administration->_unite_prescription = $key_tab;
        $administration->prise_id            = $prise_id;
        $administration->setObject($line_element);

        if ($msg = $administration->store()) {
          return $msg;
        }
      }

      $this->element_prescription_id = $line_element->element_prescription_id;

      if ($msg = $task->store()) {
        return $msg;
      }

      if ($msg = parent::store()) {
        return $msg;
      }
    }

    // On note le résultat de la tâche si la consultation est terminée
    if ($this->chrono == CConsultation::TERMINE) {
      /** @var $task CSejourTask */
      $task = $this->loadRefTask();
      if ($task->_id) {
        $task->resultat = CAppUI::tr('CConsultation-Consultation completed');
        $task->realise  = 1;
        if ($msg = $task->store()) {
          return $msg;
        }
      }
    }

    // Forfait SE et facturable. A laisser apres le store()
    if ($this->sejour_id && CAppUI::gconf("dPcabinet CConsultation attach_consult_sejour")) {
      if ($forfait_se !== null || $facturable !== null || $forfait_sd !== null) {
        $this->_ref_sejour->forfait_se = $forfait_se;
        $this->_ref_sejour->forfait_sd = $forfait_sd;
        $this->_ref_sejour->facturable = $facturable;
        if ($msg = $this->_ref_sejour->store()) {
          return $msg;
        }
        $this->_forfait_se     = null;
        $this->_forfait_sd     = null;
        $this->_facturable     = null;
        $this->_uf_soins_id    = null;
        $this->_uf_medicale_id = null;
        $this->_charge_id      = null;
      }
    }

    if ($this->_adjust_sejour && ($this->_ref_sejour->type === "consult") && $sejour->_id) {
      $consultations = $this->_ref_sejour->countBackRefs("consultations");
      if ($consultations < 1) {
        if ($msg = $this->_ref_sejour->delete()) {
          $this->_ref_sejour->annule = 1;
          if ($msg = $this->_ref_sejour->store()) {
            return $msg;
          }
        }
      }
    }

    // Gestion du tarif et precodage des actes
    if ($this->_bind_tarif && $this->_id) {
      if ($msg = $this->bindTarif()) {
        return $msg;
      }
    }

    // Bind FSE
    if ($this->_bind_fse && $this->_id) {
      if (CModule::getActive("fse")) {
        $fse = CFseFactory::createFSE();
        if ($fse) {
          $fse->bindFSE($this);
        }
      }
    }

    // If it's actually a meeting, store the motive and notes in the meeting object
    if ($this->reunion_id) {
      $rappel             = $this->_rappel; // Ref Reunion Updates _rappel so we loose the form value
      $meeting            = $this->loadRefReunion();
      $meeting->remarques = $this->rques;
      $meeting->motif     = $this->motif;
      $meeting->rappel    = $rappel;
      $meeting->store($this);
    }

    if ($this->grossesse_id && $this->_type_suivi) {
      $this->getSuiviGrossesse($this->_type_suivi);
    }

    return null;
  }

  function delete() {
    $this->completeField("patient_id", "plageconsult_id", "groupee");

    if ($msg = parent::delete()) {
      return $msg;
    }

    if (!$this->groupee || !$this->patient_id) {
      return null;
    }

    $this->loadRefPlageConsult();

    $consult = new self();
    $where   = array(
      "date"       => "= '$this->_date'",
      "patient_id" => "= '$this->patient_id'",
    );
    $ljoin   = array(
      "plageconsult" => "plageconsult.plageconsult_id = consultation.plageconsult_id",
    );

    if ($consult->countList($where, null, $ljoin)) {
      return null;
    }

    $reservation             = new CReservation();
    $reservation->date       = $this->_date;
    $reservation->patient_id = $this->patient_id;

    foreach ($reservation->loadMatchingList() as $_reservation) {
      if ($msg = $_reservation->delete()) {
        return $msg;
      }
    }

    return null;
  }

  /**
   * Charge la catégorie de la consultation
   *
   * @param bool $cache Utilise le cache
   *
   * @return CConsultationCategorie
   */
  function loadRefCategorie($cache = true) {
    return $this->_ref_categorie = $this->loadFwdRef("categorie_id", $cache);
  }

  /**
   * Charge la réunion de la consultation
   *
   * @return CReunion
   */
  function loadRefReunion() {
    $this->_ref_reunion = $this->loadFwdRef("reunion_id");
    $this->_rappel      = $this->_ref_reunion->rappel;

    return $this->_ref_reunion;
  }


  /**
   * Charge la tâche de séjour possiblement associée
   *
   * @return CSejourTask
   */
  function loadRefTask() {
    return $this->_ref_task = $this->loadUniqueBackRef("task");
  }

    /**
     * Charge la salle
     *
     * @return CRoom
     */
    function loadRefRoom() {
        return $this->_ref_room = $this->loadUniqueBackRef("rooms");
    }

  /**
   * Charge l'accident de travail de la consultation
   *
   * @return CAccidentTravail
   */
  function loadRefAccidentTravail() {
    return $this->_ref_accident_travail = $this->loadUniqueBackRef("accident_travail");
  }

  /**
   * Charge le suivi de grossesse possiblement associé
   *
   * @return CSuiviGrossesse
   */
  function loadRefSuiviGrossesse() {
    return $this->_ref_suivi_grossesse = $this->loadUniqueBackRef("suivi_grossesse");
  }

  /**
   * Force la création d'un suivi grossesse si demande de chargement
   *
   * @param string $type_suivi Type de suivi
   *
   * @return CSuiviGrossesse
   */
  function getSuiviGrossesse($type_suivi = "urg") {
    $suivi = $this->loadRefSuiviGrossesse();
    if ($this->_id && !$suivi->_id) {
      $suivi                  = new CSuiviGrossesse();
      $suivi->consultation_id = $this->_id;
      $suivi->type_suivi      = $type_suivi;
      $suivi->store();
    }

    return $this->_ref_suivi_grossesse = $suivi;
  }

  /**
   * Charge l'élément de prescription possiblement associé
   *
   * @return CElementPrescription
   */
  function loadRefElementPrescription() {
    return $this->_ref_element_prescription = $this->loadFwdRef("element_prescription_id", true);
  }

  /**
   * @see parent::loadComplete()
   */
  function loadComplete() {
    parent::loadComplete();

    if (!$this->_ref_patient) {
      $this->loadRefPatient();
    }
    $this->_ref_patient->loadRefLatestConstantes();

    if (!$this->_ref_actes_ccam) {
      $this->loadRefsActesCCAM();
    }
    foreach ($this->_ref_actes_ccam as $_acte) {
      $_acte->loadRefExecutant();
    }

    $this->loadRefConsultAnesth();
    foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
      $_dossier_anesth->loadRefOperation();
    }
  }

  /**
   * Charge le patient
   *
   * @param bool $cache Use cache
   *
   * @return CPatient
   */
  function loadRefPatient($cache = true) {
    return $this->_ref_patient = $this->loadFwdRef("patient_id", $cache);
  }

  /**
   * Chargement du sejour et du RPU dans le cas d'une urgence
   *
   * @param bool $cache Use cache
   *
   * @return CSejour
   */
  function loadRefSejour($cache = true) {
    /** @var CSejour $sejour */
    $sejour = $this->loadFwdRef("sejour_id", $cache);
    $sejour->loadRefRPU();

    if (CAppUI::gconf("dPcabinet CConsultation attach_consult_sejour")) {
      $this->_forfait_se     = $sejour->forfait_se;
      $this->_forfait_sd     = $sejour->forfait_sd;
      $this->_facturable     = $sejour->facturable;
      $this->_uf_soins_id    = $sejour->uf_soins_id;
      $this->_uf_medicale_id = $sejour->uf_medicale_id;
      $this->_charge_id      = $sejour->charge_id;
    }

    return $this->_ref_sejour = $sejour;
  }

  /**
   * Charge la grossesse associée au séjour
   *
   * @return CGrossesse
   */
  function loadRefGrossesse() {
    return $this->_ref_grossesse = $this->loadFwdRef("grossesse_id", true);
  }

  /**
   * Calcul de la date en semaines d'aménorrhée
   *
   * @return int
   */
  function getSA() {
    $this->loadRefGrossesse();
    $this->loadRefPlageConsult();
    $sa_comp   = $this->_ref_grossesse->getAgeGestationnel($this->_date);
    $this->_ja = $sa_comp["JA"];

    return $this->_sa = $sa_comp["SA"];
  }

  /**
   * Charge l'établissement indirectement associée à la consultation
   *
   * @return CGroups
   * @todo Prendre en compte le cas de la consultation liée à un séjour dans un établissement
   */
  function loadRefGroup() {
    return $this->_ref_group = $this->loadRefPraticien()->loadRefFunction()->loadRefGroup();
  }

  /**
   * @see parent::getActeExecution()
   */
  function getActeExecution() {
    $this->loadRefPlageConsult();

    return $this->_acte_execution;
  }

  /**
   * @see parent::getExecutantId()
   */
  function getExecutantId($code_activite = null) {
    $user = CMediusers::get();
    if (!($user->isProfessionnelDeSante() && CAppUI::pref("user_executant"))) {
      $this->loadRefPlageConsult();

      $user = $this->_ref_chir;
    }

    if ($user->loadRefRemplacant($this->_acte_execution)) {
      $user = $user->_ref_remplacant;
    }

    return $user->_id;
  }

  /**
   * Charge les éléments de codage CCAM
   *
   * @return CCodageCCAM[]
   * @throws Exception
   */
  function loadRefsCodagesCCAM() {
    parent::loadRefsCodagesCCAM();

    /* Si l'enveloppe de  codage du praticien n'existe pas, elle est créée automatiquement */
    $chir = $this->loadRefPraticien();

    if ($chir->loadRefRemplacant($this->_acte_execution)) {
      $chir = $chir->_ref_remplacant;
    }
    if (!array_key_exists($chir->_id, $this->_ref_codages_ccam)) {
      $_codage                             = CCodageCCAM::get($this, $chir->_id, 1);
      $this->_ref_codages_ccam[$chir->_id] = array($_codage);
    }

    return $this->_ref_codages_ccam;
  }

  /**
   * Charge la plage de consultation englobante
   *
   * @param boolean $cache [optional] Use cache
   *
   * @return CPlageconsult
   */
  function loadRefPlageConsult($cache = true) {
    $this->completeField("plageconsult_id");
    /** @var CPlageConsult $plage */
    $plage = $this->loadFwdRef("plageconsult_id", $cache);

    $this->_duree = CMbDT::minutesRelative("00:00:00", $plage->freq) * $this->duree;

    $plage->_ref_chir       = $plage->loadFwdRef("chir_id", $cache);
    $plage->_ref_remplacant = $plage->loadFwdRef("remplacant_id", $cache);

    // Distant fields
    /** @var CMediusers $chir */
    $chir = $plage->_ref_remplacant->_id ?
      $plage->_ref_remplacant :
      $plage->_ref_chir;

    $this->_date           = $plage->date;
    $this->_datetime       = CMbDT::addDateTime($this->heure, $this->_date);
    $this->_date_fin       = CMbDT::dateTime(
        "+" . CMbDT::minutesRelative("00:00:00", $plage->freq) * $this->duree . " " . CAppUI::tr('common-minute|pl'),
        $this->_datetime
    );
    if (!$this->_acte_execution) {
        $this->_acte_execution = $this->_datetime;
    }
    $this->_is_anesth      = $chir->isAnesth();
    $this->_is_dentiste    = $chir->isDentiste();
    $this->_praticien_id   = $chir->_id;

    $this->_ref_chir = $chir;

    return $this->_ref_plageconsult = $plage;
  }

  /**
   * @see parent::loadRefPraticien()
   */
  function loadRefPraticien($cache = true) {
    if ($this->_ref_praticien && $this->_ref_praticien->_id) {
      return $this->_ref_praticien;
    }
    $this->loadRefPlageConsult($cache);
    $this->_ref_executant = $this->_ref_plageconsult->_ref_chir;

    return $this->_ref_praticien = $this->_ref_chir;
  }

  /**
   * Détermine le type de la consultation
   *
   * @return string Un des types possibles urg, anesth
   */
  function getType() {
    $praticien = $this->loadRefPraticien();
    $sejour    = $this->_ref_sejour;

    if (!$sejour) {
      $sejour = $this->loadRefSejour();
    }

    if (!$sejour->_ref_rpu) {
      $sejour->loadRefRPU();
    }

    // Consultations d'urgences
    if ($praticien->isUrgentiste() && $sejour->_ref_rpu && $sejour->_ref_rpu->_id) {
      $this->_type = CAppUI::gconf("dPurgences CRPU type_sejour") === "urg_consult" ? "consult" : "urg";
    }

    // Consultation préanesthésique
    if ($this->countBackRefs("consult_anesth")) {
      $this->_type = "anesth";
    }
  }

  /**
   * @see parent::preparePossibleActes()
   */
  function preparePossibleActes() {
    $this->loadRefPlageConsult();
  }

  /**
   * @see parent::loadRefsFwd()
   */
  function loadRefsFwd($cache = true) {
    $this->loadRefPatient($cache);
    $this->_ref_patient->loadRefLatestConstantes();
    $this->loadRefPlageConsult($cache);
    $this->_view = CAppUI::tr('CConsultation-Consultation of %s - %s-court', $this->_ref_patient->_view, $this->_ref_plageconsult->_ref_chir->_view);
    $this->_view .= " (" . CMbDT::format($this->_ref_plageconsult->date, CAppUI::conf("date")) . ")";
    $this->loadExtCodesCCAM();
  }

  /**
   * @inheritdoc
   */
  function loadRefsDocs($where = array()) {
    parent::loadRefsDocs($where);

    if (!$this->_docitems_from_dossier_anesth) {
      // On ajoute les documents des dossiers d'anesthésie
      if (!$this->_refs_dossiers_anesth) {
        $this->loadRefConsultAnesth();
      }

      foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
        $_dossier_anesth->_docitems_from_consult = true;
        $_dossier_anesth->loadRefsDocs($where);
        $this->_ref_documents = CMbArray::mergeKeys($this->_ref_documents, $_dossier_anesth->_ref_documents);
      }
    }

    return count($this->_ref_documents);
  }

  /**
   * @inheritdoc
   */
  function loadRefsFiles($where = array()) {
    parent::loadRefsFiles($where);

    if (!$this->_docitems_from_dossier_anesth) {
      // On ajoute les fichiers des dossiers d'anesthésie
      if (!$this->_refs_dossiers_anesth) {
        $this->loadRefConsultAnesth();
      }

      foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
        $_dossier_anesth->_docitems_from_consult = true;
        $_dossier_anesth->loadRefsFiles($where);
        $this->_ref_files = CMbArray::mergeKeys($this->_ref_files, $_dossier_anesth->_ref_files);
      }
    }

    // Récupérer les bons de transport
    if (CModule::getActive("transport")) {
      foreach ($this->loadRefsTransports() as $_transport) {
        $_transport->loadRefsFiles($where);
        $this->_ref_files = CMbArray::mergeKeys($this->_ref_files, $_transport->_ref_files);
      }
    }

    return count($this->_ref_files);
  }

  /**
   * @see parent::countDocItems()
   */
  function countDocItems($permType = null) {
    if (!$this->_nb_files_docs) {
      parent::countDocItems($permType);
    }

    if ($this->_nb_files_docs) {
      $this->getEtat();
      $this->_etat .= " ($this->_nb_files_docs)";
    }

    return $this->_nb_files_docs;
  }

  /**
   * @see parent::countDocs()
   */
  function countDocs() {
    $nbDocs = parent::countDocs();

    if (!$this->_docitems_from_dossier_anesth) {
      // Ajout des documents des dossiers d'anesthésie
      if (!$this->_refs_dossiers_anesth) {
        $this->loadRefConsultAnesth();
      }

      foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
        $_dossier_anesth->_docitems_from_consult = true;
        $nbDocs                                  += $_dossier_anesth->countDocs();
      }
    }

    return $this->_nb_docs = $nbDocs;
  }

  /**
   * @see parent::countFiles()
   */
  function countFiles($where = array()) {
    $nbFiles = parent::countFiles($where);

    if (!$this->_docitems_from_dossier_anesth) {
      // Ajout des fichiers des dossiers d'anesthésie
      if (!$this->_refs_dossiers_anesth) {
        $this->loadRefConsultAnesth();
      }

      foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
        $_dossier_anesth->_docitems_from_consult = true;
        $nbFiles                                 += $_dossier_anesth->countFiles();
      }
    }

    return $this->_nb_files = $nbFiles;
  }

  /**
   * Charge un dossier d'anesthésie classique
   *
   * @param string $dossier_anesth_id Identifiant de dossier à charger explicitement
   *
   * @return CConsultAnesth
   */
  function loadRefConsultAnesth($dossier_anesth_id = null) {
    $dossiers = $this->loadRefsDossiersAnesth();

    // Cas du choix initial du dossier à utiliser
    if ($dossier_anesth_id !== null && isset($dossiers[$dossier_anesth_id])) {
      return $this->_ref_consult_anesth = $dossiers[$dossier_anesth_id];
    }

    // On retourne le premier ou un dossier vide
    return $this->_ref_consult_anesth = count($dossiers) ? reset($dossiers) : new CConsultAnesth();
  }

  /**
   * Charge tous les dossiers d'anesthésie
   *
   * @return CConsultAnesth[]
   */
  function loadRefsDossiersAnesth() {
    $this->_refs_dossiers_anesth = $this->loadBackRefs("consult_anesth");

    foreach ($this->_refs_dossiers_anesth as $_dossier_anesth) {
      $_dossier_anesth->_ref_consultation = $this;
      $_dossier_anesth->loadRefChir();
    }

    return $this->_refs_dossiers_anesth;
  }

  /**
   * Charge l'audiogramme
   *
   * @return CExamAudio
   */
  function loadRefsExamAudio() {
    return $this->_ref_examaudio = $this->loadUniqueBackRef("examaudio");
  }

  /**
   * Charge l'audiogramme
   *
   * @return CExamAudio
   */
  function loadRefsExamNyha() {
    $this->_ref_examnyha = $this->loadUniqueBackRef("examnyha");
  }

  /**
   * Charge le score possum
   *
   * @return CExamPossum
   */
  function loadRefsExamPossum() {
    $this->_ref_exampossum = $this->loadUniqueBackRef("exampossum");
  }

  /**
   * Charge toutes les fiches d'examens associées
   *
   * @return int Nombre de fiche
   */
  function loadRefsFichesExamen() {
    $this->loadRefsExamAudio();
    $this->loadRefsExamNyha();
    $this->loadRefsExamPossum();
    $this->_count_fiches_examen = 0;
    $this->_count_fiches_examen += $this->_ref_examaudio->_id ? 1 : 0;
    $this->_count_fiches_examen += $this->_ref_examnyha->_id ? 1 : 0;
    $this->_count_fiches_examen += $this->_ref_exampossum->_id ? 1 : 0;

    return $this->_count_fiches_examen;
  }

  /**
   * Chargement des prescriptions liées à la consultation
   *
   * @return CPrescription[] Les prescription, classées par type, pas par identifiant
   */
  function loadRefsPrescriptions() {
    $prescriptions = $this->loadBackRefs("prescriptions");

    // Cas du module non installé
    if (!is_array($prescriptions)) {
      return $this->_ref_prescriptions = null;
    }

    $this->_count_prescriptions = count($prescriptions);

    foreach ($prescriptions as $_prescription) {
      $this->_ref_prescriptions[$_prescription->type] = $_prescription;
    }

    return $this->_ref_prescriptions;
  }

  /**
   * @see parent::loadRefsBack()
   * @deprecated
   */
  function loadRefsBack() {
    // Backward references
    $this->loadRefsDocItems();
    $this->countDocItems();
    $this->loadRefConsultAnesth();

    $this->loadRefsExamsComp();

    $this->loadRefsFichesExamen();
    $this->loadRefsActesCCAM();
    $this->loadRefsActesNGAP();
    $this->loadRefFacture()->loadRefsReglements();
  }

  /**
   * Charge les examens complémentaires à réaliser
   *
   * @return CExamComp[]
   */
  function loadRefsExamsComp() {
    $order = "examen";
    /** @var CExamComp $examcomps */
    $examcomps = $this->loadBackRefs("examcomp", $order);

    foreach ($examcomps as $_exam) {
      $this->_types_examen[$_exam->realisation][$_exam->_id] = $_exam;
    }

    return $this->_ref_examcomp = $examcomps;
  }

  /**
   * Champs d'examen à afficher
   *
   * @return string[] Noms interne des champs
   */
  function getExamFields() {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $fields = array(
      "motif",
      "rques",
    );

    if (CAppUI::gconf("dPcabinet CConsultation show_histoire_maladie")) {
      $fields[] = "histoire_maladie";
    }
    if (CAppUI::gconf("dPcabinet CConsultation show_examen")) {
      $fields[] = "examen";
    }
    if (CAppUI::pref("view_traitement")) {
      $fields[] = "traitement";
    }
    if (CAppUI::gconf("dPcabinet CConsultation show_projet_soins")) {
      $fields[] = "projet_soins";
    }
    if (CAppUI::gconf("dPcabinet CConsultation show_conclusion")) {
      $fields[] = "conclusion";
    }
    // Consultation d'urgence
    $praticien = $this->loadRefPraticien();
    if (CAppUI::gconf('dPurgences CRPU resultats_rpu_field_view') && $praticien->isUrgentiste()) {
      $fields[] = "resultats";
    }

    return $cache->put($fields);
  }

  /**
   * @see parent::getPerm()
   */
  function getPerm($permType) {
    $this->loadRefPlageConsult();

    return $this->_ref_chir->getPerm($permType) && parent::getPerm($permType);
  }

  /**
   * @see parent::fillTemplate()
   */
  function fillTemplate(&$template) {
    $this->updateFormFields();
    $this->loadRefsFwd();
    $this->_ref_plageconsult->loadRefsFwd();
    $this->_ref_plageconsult->_ref_chir->fillTemplate($template);
    $this->_ref_patient->fillTemplate($template);
    $this->fillLimitedTemplate($template);
    if (CModule::getActive('dPprescription')) {
      // Chargement du fillTemplate de la prescription
      $this->loadRefsPrescriptions();
      $prescription       = isset($this->_ref_prescriptions["externe"]) ?
        $this->_ref_prescriptions["externe"] :
        new CPrescription();
      $prescription->type = "externe";
      $prescription->fillLimitedTemplate($template);
    }

    $sejour = $this->loadRefSejour();

    $sejour->fillLimitedTemplate($template);
    $rpu = $sejour->loadRefRPU();
    if ($rpu && $rpu->_id) {
      $rpu->fillLimitedTemplate($template);
    }

    if (!$this->countBackRefs("consult_anesth") && CModule::getActive("dPprescription")) {
      $sejour->loadRefsPrescriptions();
      $prescription       = isset($sejour->_ref_prescriptions["pre_admission"]) ?
        $sejour->_ref_prescriptions["pre_admission"] :
        new CPrescription();
      $prescription->type = "pre_admission";
      $prescription->fillLimitedTemplate($template);
      $prescription       = isset($sejour->_ref_prescriptions["sejour"]) ?
        $sejour->_ref_prescriptions["sejour"] :
        new CPrescription();
      $prescription->type = "sejour";
      $prescription->fillLimitedTemplate($template);
      $prescription       = isset($sejour->_ref_prescriptions["sortie"]) ?
        $sejour->_ref_prescriptions["sortie"] :
        new CPrescription();
      $prescription->type = "sortie";
      $prescription->fillLimitedTemplate($template);
    }

    $facture = $this->loadRefFacture();
    $facture->fillLimitedTemplate($template);
  }

  /**
   * @see parent::fillLimitedTemplate()
   */
  function fillLimitedTemplate(&$template) {
    $this->updateFormFields();
    $this->loadRefsFwd();

    $this->notify(ObjectHandlerEvent::BEFORE_FILL_LIMITED_TEMPLATE(), $template);

    $consultation_section = CAppUI::tr('CConsultation');      //todo: traductions sur tous les champs

    $template->addDateProperty("Consultation - date", $this->_ref_plageconsult->date);
    $template->addLongDateProperty("Consultation - date longue", $this->_ref_plageconsult->date);
    $template->addTimeProperty("Consultation - heure", $this->heure);
    $locExamFields = array(
      "motif"            => "motif",
      "rques"            => "remarques",
      "examen"           => "examen",
      "traitement"       => "traitement",
      "histoire_maladie" => "histoire maladie",
      "projet_soins"     => "projet_soins",
      "conclusion"       => strtolower(CAppUI::tr("CConsultation-conclusion")),
      "resultats"        => "resultats",
    );

    foreach ($this->_exam_fields as $field) {
      $loc_field = $locExamFields[$field];

      if ($this->_specs[$field]->markdown) {
        $template->addMarkdown("Consultation - $loc_field", $this->$field);
      }
      else {
        $template->addProperty("Consultation - $loc_field", $this->$field);
      }
    }

    if (!in_array("traitement", $this->_exam_fields)) {
      if ($this->_specs["traitement"]->markdown) {
        $template->addMarkdown("Consultation - traitement", $this->traitement);
      }
      else {
        $template->addProperty("Consultation - traitement", $this->traitement);
      }
    }

    $medecin = new CMedecin();
    $medecin->load($this->adresse_par_prat_id);
    $nom = "{$medecin->nom} {$medecin->prenom}";
    $template->addProperty("Consultation - adressé par", $nom);
    $template->addProperty("Consultation - adressé par - adresse", "{$medecin->adresse}\n{$medecin->cp} {$medecin->ville}");

    $template->addProperty("Consultation - Accident du travail", $this->getFormattedValue("date_at"));
    $libelle_at = $this->date_at ? "Accident du travail du " . $this->getFormattedValue("date_at") : "";
    $template->addProperty("Consultation - Libellé accident du travail", $libelle_at);

    $this->loadRefsFiles();
    $list = CMbArray::pluck($this->_ref_files, "file_name");
    $template->addListProperty("Consultation - Liste des fichiers", $list);

    // Avis arrêt de travail
    if (CModule::getActive("ameli")) {
      /** @var CAvisArretTravail $last_avis_travail */
      $this->loadRefsAvisArretsTravail();
      $last_avis_travail = new CAvisArretTravail();
      if ($this->_refs_avis_arrets_travail && is_array($this->_refs_avis_arrets_travail)) {
        $last_avis_travail = end($this->_refs_avis_arrets_travail);
      }

      $template->addProperty("Consultation - Début arrêt de travail", CMbDT::dateToLocale($last_avis_travail->debut));
      $template->addProperty("Consultation - Type arrêt de travail", $last_avis_travail->getFormattedValue("type"));
      $template->addProperty("Consultation - Fin arrêt de travail", CMbDT::dateToLocale($last_avis_travail->fin));
      $template->addProperty("Consultation - Accident de travail causé par un tiers", $last_avis_travail->getFormattedValue("accident_tiers"));
      $template->addProperty("Consultation - Motif arrêt maladie", $last_avis_travail->libelle_motif);
    }
    else {
      $template->addProperty("Consultation - Fin arrêt de travail", CMbDT::dateToLocale(CMbDT::date($this->fin_at)));
      $template->addProperty("Consultation - Prise en charge arrêt de travail", $this->getFormattedValue("pec_at"));
      $template->addProperty("Consultation - Reprise de travail", CMbDT::dateToLocale(CMbDT::date($this->reprise_at)));
      $template->addProperty("Consultation - Accident de travail sans arrêt de travail", $this->getFormattedValue("at_sans_arret"));
      $template->addProperty("Consultation - Arrêt maladie", $this->getFormattedValue("arret_maladie"));
    }

    $template->addProperty("Consultation - Documents nécessaires", nl2br($this->docs_necessaires), array(), false);

    $facture = $this->loadRefFacture();
    $template->addProperty("Consultation - Numéro de facture", $facture ? $facture->_view : "");

    $this->loadRefsExamsComp();
    $exam = new CExamComp();

    foreach ($exam->_specs["realisation"]->_locales as $key => $locale) {
      $exams = isset($this->_types_examen[$key]) ? $this->_types_examen[$key] : array();
      foreach ($exams as $_exam) {
        if ($_exam->fait) {
          $_exam->_view .= " (Fait)";
        }
      }
      $template->addListProperty("Consultation - Examens complémentaires - $locale", $exams);
    }

    if (CModule::getActive("forms")) {
      CExObject::addFormsToTemplate($template, $this, "Consultation");
    }

    if (CModule::getActive("oxCabinet")) {
      $this->loadRefsActesCCAM();
      $this->loadRefsActesNGAP();

      $actes = array_merge($this->_ref_actes_ccam, $this->_ref_actes_ngap);

      foreach ($actes as $_acte) {
        $_acte->loadRefPrescription()->loadRefsLinesElement(null, 'soin');
      }

      $smarty = new CSmartyDP("modules/dPcabinet");
      $smarty->assign("consult", $this);
      $smarty->assign("actes", $actes);

      $content_actes = $smarty->fetch("inc_actes_motifs.tpl");
      $content_actes = preg_replace("/\r\n/", "", $content_actes);
      $content_actes = preg_replace("/\n/", "", $content_actes);

      $template->addProperty("Consultation - Actes et motifs", $content_actes, null, false);
    }

    // Séjour et/ou intervention créés depuis la consultation
    $this->loadBackRefs("sejours_lies");
    $sejour_relie = reset($this->_back["sejours_lies"]);
    $this->loadBackRefs("intervs_liees");
    $interv_reliee = reset($this->_back["intervs_liees"]);

    if ($interv_reliee) {
      $sejour_relie = $interv_reliee->loadRefSejour();
    }
    else {
      if (!$sejour_relie) {
        $sejour_relie = new CSejour();
      }
      if (!$interv_reliee) {
        $interv_reliee = new COperation();
      }
    }

    $interv_reliee->loadRefChir();
    $interv_reliee->loadRefPlageOp();
    $interv_reliee->loadRefSalle();
    $sejour_relie->loadRefPraticien();

    // Intervention reliée
    $template->addProperty("Consultation - Opération reliée - Chirurgien", $interv_reliee->_ref_chir->_view);
    $template->addProperty("Consultation - Opération reliée - Libellé", $interv_reliee->libelle);
    $template->addProperty("Consultation - Opération reliée - Salle", $interv_reliee->_ref_salle->nom);
    $template->addDateProperty("Consultation - Opération reliée - Date", $interv_reliee->_datetime_best);

    // Séjour relié
    $template->addDateProperty("Consultation - Séjour relié - Date entrée", $sejour_relie->entree);
    $template->addLongDateProperty("Consultation - Séjour relié - Date entrée (longue)", $sejour_relie->entree);
    $template->addTimeProperty("Consultation - Séjour relié - Heure entrée", $sejour_relie->entree);
    $template->addDateProperty("Consultation - Séjour relié - Date sortie", $sejour_relie->sortie);
    $template->addLongDateProperty("Consultation - Séjour relié - Date sortie (longue)", $sejour_relie->sortie);
    $template->addTimeProperty("Consultation - Séjour relié - Heure sortie", $sejour_relie->sortie);

    $template->addDateProperty("Consultation - Séjour relié - Date entrée réelle", $sejour_relie->entree_reelle);
    $template->addTimeProperty("Consultation - Séjour relié - Heure entrée réelle", $sejour_relie->entree_reelle);
    $template->addDateProperty("Consultation - Séjour relié - Date sortie réelle", $sejour_relie->sortie_reelle);
    $template->addTimeProperty("Consultation - Séjour relié - Heure sortie réelle", $sejour_relie->sortie_reelle);
    $template->addProperty("Consultation - Séjour relié - Praticien", "Dr " . $sejour_relie->_ref_praticien->_view);
    $template->addProperty("Consultation - Séjour relié - Libelle", $sejour_relie->getFormattedValue("libelle"));

    if ($suivi_grossesse = $this->loadRefSuiviGrossesse()) {
      $suivi_grossesse_section = CAppUI::tr("CSuiviGrossesse");

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-type_suivi"), $suivi_grossesse->getFormattedValue("type_suivi"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-evenements_anterieurs"), $suivi_grossesse->getFormattedValue("evenements_anterieurs"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-metrorragies"), $suivi_grossesse->getFormattedValue("metrorragies"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-leucorrhees"), $suivi_grossesse->getFormattedValue("leucorrhees"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-contractions_anormales"), $suivi_grossesse->getFormattedValue("contractions_anormales"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-mouvements_foetaux"), $suivi_grossesse->getFormattedValue("mouvements_foetaux"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-troubles_digestifs"), $suivi_grossesse->getFormattedValue("troubles_digestifs"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-troubles_urinaires"), $suivi_grossesse->getFormattedValue("troubles_urinaires"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-autres_anomalies")." (".CAppUI::tr("CSuiviGrossesse-functionnal_signs").")", $suivi_grossesse->getFormattedValue("autres_anomalies"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-hypertension"), $suivi_grossesse->getFormattedValue("hypertension"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-mouvements_actifs"), $suivi_grossesse->getFormattedValue("mouvements_actifs"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-auscultation_cardio_pulm"), $suivi_grossesse->getFormattedValue("auscultation_cardio_pulm"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-examen_seins"), $suivi_grossesse->getFormattedValue("examen_seins"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-circulation_veineuse"), $suivi_grossesse->getFormattedValue("circulation_veineuse"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-oedeme_membres_inf"), $suivi_grossesse->getFormattedValue("oedeme_membres_inf"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-rques_examen_general"), $suivi_grossesse->getFormattedValue("rques_examen_general"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-bruit_du_coeur"), $suivi_grossesse->getFormattedValue("bruit_du_coeur"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-col_normal"), $suivi_grossesse->getFormattedValue("col_normal"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-longueur_col"), $suivi_grossesse->getFormattedValue("longueur_col"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-position_col"), $suivi_grossesse->getFormattedValue("position_col"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-dilatation_col"), $suivi_grossesse->getFormattedValue("dilatation_col"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-dilatation_col_num"), $suivi_grossesse->getFormattedValue("dilatation_col_num"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-consistance_col"), $suivi_grossesse->getFormattedValue("consistance_col"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-col_commentaire"), $suivi_grossesse->getFormattedValue("col_commentaire"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-presentation_position"), $suivi_grossesse->getFormattedValue("presentation_position"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-presentation_etat"), $suivi_grossesse->getFormattedValue("presentation_etat"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-segment_inferieur"), $suivi_grossesse->getFormattedValue("segment_inferieur"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-membranes"), $suivi_grossesse->getFormattedValue("membranes"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-bassin"), $suivi_grossesse->getFormattedValue("bassin"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-examen_genital"), $suivi_grossesse->getFormattedValue("examen_genital"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-rques_exam_gyneco_obst"), $suivi_grossesse->getFormattedValue("rques_exam_gyneco_obst"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-hauteur_uterine"), $suivi_grossesse->getFormattedValue("hauteur_uterine"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-frottis"), $suivi_grossesse->getFormattedValue("frottis"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-echographie"), $suivi_grossesse->getFormattedValue("echographie"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-prelevement_bacterio"), $suivi_grossesse->getFormattedValue("prelevement_bacterio"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-autre_exam_comp")." (".CAppUI::tr("CSuiviGrossesse-exam_comp").")", $suivi_grossesse->getFormattedValue("autre_exam_comp"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-glycosurie"), $suivi_grossesse->getFormattedValue("glycosurie"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-leucocyturie"), $suivi_grossesse->getFormattedValue("leucocyturie"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-albuminurie"), $suivi_grossesse->getFormattedValue("albuminurie"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-nitrites"), $suivi_grossesse->getFormattedValue("nitrites"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-jours_arret_travail"), $suivi_grossesse->getFormattedValue("jours_arret_travail"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CSuiviGrossesse-conclusion"), $suivi_grossesse->getFormattedValue("conclusion"));
      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CConsultation-motif"), $this->getFormattedValue("motif"));

      $template->addProperty("$consultation_section - $suivi_grossesse_section - ".CAppUI::tr("CConsultation-rques"), $this->getFormattedValue("rques"));
    }

    $template->addProperty("Consultation - Identifiant de la consultation", $this->_id);

    // Constantes
    CConstantesMedicales::fillLiteLimitedTemplate($this, $template, "Consultation");

    $constantes_first = CConstantesMedicales::getFirstFor($this->_ref_patient, null, null, $this, false);
    $first_constantes = reset($constantes_first);
    CConstantesMedicales::fillLiteLimitedTemplate2($first_constantes, $template, true, "Consultation");

    $constantes_last   = CConstantesMedicales::getLatestFor($this->_ref_patient, null, null, $this, false);
    $latest_constantes = reset($constantes_last);
    CConstantesMedicales::fillLiteLimitedTemplate2($latest_constantes, $template, false, "Consultation");

    $this->notify(ObjectHandlerEvent::AFTER_FILL_LIMITED_TEMPLATE(), $template);
  }

  /**
   * @see parent::canDeleteEx()
   */
  function canDeleteEx() {
    if (!$this->_mergeDeletion) {
      // Date dépassée
      $this->loadRefPlageConsult();
      if ($this->_date < CMbDT::date() && !$this->_ref_module->_can->admin) {
        return CAppUI::tr('CConsultation-msg-Unable to delete past consultation');
      }
    }

    return parent::canDeleteEx();
  }

  /**
   * Ajustement du séjour à l'enregistrement
   *
   * @param CSejour $sejour        Séjour englobant
   * @param string  $dateTimePlage Date et heure de la plage à créer
   *
   * @return string|null Store-like message
   */
  private function adjustSejour(CSejour $sejour, $dateTimePlage) {
    if ($sejour->_id == $this->_ref_sejour->_id) {
      return null;
    }

    // Journée dans lequel on déplace à déjà un séjour
    if ($sejour->_id) {
      // Affecte à la consultation le nouveau séjour
      $this->sejour_id = $sejour->_id;

      return null;
    }

    // Journée qui n'a pas de séjour en cible
    $count_consultations = $this->_ref_sejour->countBackRefs("consultations");

    // On déplace les dates du séjour
    if (($count_consultations == 1) && ($this->_ref_sejour->type === "consult")) {
      $this->_ref_sejour->entree_prevue       = $dateTimePlage;
      $this->_ref_sejour->sortie_prevue       = CMbDT::date($dateTimePlage) . " 23:59:59";
      $this->_ref_sejour->_hour_entree_prevue = null;
      $this->_ref_sejour->_hour_sortie_prevue = null;
      if ($msg = $this->_ref_sejour->store()) {
        return $msg;
      }

      return null;
    }

    // On créé le séjour de consultation
    $sejour->patient_id    = $this->patient_id;
    $sejour->praticien_id  = $this->_ref_chir->_id;
    $sejour->group_id      = CGroups::loadCurrent()->_id;
    $sejour->type          = "consult";
    $sejour->entree_prevue = $dateTimePlage;
    $sejour->sortie_prevue = CMbDT::date($dateTimePlage) . " 23:59:59";

    if ($msg = $sejour->store()) {
      return $msg;
    }

    $this->sejour_id = $sejour->_id;

    return null;
  }

  /**
   * @see parent::completeLabelFields()
   */
  function completeLabelFields(&$fields, $params) {
    $this->loadRefPatient()->completeLabelFields($fields, $params);
  }

  /**
   * @see parent::canEdit()
   */
  function canEdit() {
    if (!$this->sejour_id || CCanDo::admin() || !CAppUI::gconf("dPcabinet CConsultation consult_readonly")) {
      return parent::canEdit();
    }

    // Si sortie réelle, mode lecture seule
    $sejour = $this->loadRefSejour(1);
    if ($sejour->sortie_reelle) {
      return $this->_canEdit = 0;
    }

    // Modification possible seulement pour les utilisateurs de la même fonction
    $praticien = $this->loadRefPraticien();

    return $this->_canEdit = CAppUI::$user->function_id == $praticien->function_id;
  }

  /**
   * @see parent::canRead()
   */
  function canRead() {
    if (!$this->sejour_id || CCanDo::admin()) {
      return parent::canRead();
    }

    // Tout utilisateur peut consulter une consultation de séjour en lecture seule
    return $this->_canRead = 1;
  }

  /**
   * Crée une consultation à une horaire arbitraire et créé les plages correspondantes au besoin
   *
   * @param string  $datetime            Date et heure
   * @param int     $praticien_id        Praticien
   * @param int     $patient_id          Patient
   * @param integer $duree               Durée de la consultation
   * @param integer $chrono              Etat de la consultation
   * @param integer $matching            Matching
   * @param integer $periode             Période de la plage
   * @param integer $agenda_praticien_id Identifiant de l'agenda du praticien
   *
   * @return null|string Store-like message
   * @throws Exception
   */
  function createByDatetime(
    $datetime, $praticien_id, $patient_id, $duree = 1, $chrono = self::PLANIFIE, $matching = 1, $periode = null,
    $agenda_praticien_id = null
  ) {

    $minutes_interval = CValue::first(CAppUI::gconf("dPcabinet CPlageconsult minutes_interval"), "15");
    $periode          = ($periode) ?: ("00:" . ($minutes_interval ?: "05") . ":00");

    $day_now   = CMbDT::format($datetime, "%Y-%m-%d");
    $time_now  = CMbDT::format($datetime, "%H:%M:00");
    $hour_now  = CMbDT::format($datetime, "%H:00:00");
    $hour_next = CMbDT::time("+1 HOUR", $hour_now);

    $plage       = new CPlageconsult();
    $plageBefore = new CPlageconsult();
    $plageAfter  = new CPlageconsult();

    // Cas ou une plage correspond
    $where            = array();
    $where["chir_id"] = "= '$praticien_id'";
    $where["date"]    = "= '$day_now'";
    $where["debut"]   = "<= '$time_now'";
    $where["fin"]     = "> '$time_now'";
    if ($agenda_praticien_id) {
      $where["agenda_praticien_id"] = "= '$agenda_praticien_id'";
    }
    $plage->loadObject($where);

    if (!$plage->plageconsult_id) {
      // Cas ou on a des plage en collision
      $where            = array();
      $where["chir_id"] = "= '$praticien_id'";
      $where["date"]    = "= '$day_now'";
      $where["debut"]   = "<= '$hour_now'";
      $where["fin"]     = ">= '$hour_now'";
      if ($agenda_praticien_id) {
        $where["agenda_praticien_id"] = "= '$agenda_praticien_id'";
      }
      $plageBefore->loadObject($where);
      $where["debut"] = "<= '$hour_next'";
      $where["fin"]   = ">= '$hour_next'";
      $plageAfter->loadObject($where);

      if ($plageBefore->_id) {
        $plageBefore->fin = $plageAfter->_id ?
          $plageAfter->debut :
          max($plageBefore->fin, $hour_next);
        $plage            = $plageBefore;
      }
      elseif ($plageAfter->_id) {
        $plageAfter->debut = min($plageAfter->debut, $hour_now);
        $plage             = $plageAfter;
      }
      else {
        $plage->chir_id = $praticien_id;
        $plage->date    = $day_now;
        $plage->freq    = $periode;
        $plage->debut   = $hour_now;
        $plage->fin     = $hour_next;
      }
      if ($agenda_praticien_id) {
        $plage->agenda_praticien_id = $agenda_praticien_id;
      }

      $plage->updateFormFields();

      if ($msg = $plage->store()) {
        return $msg;
      }
    }

    $this->plageconsult_id = $plage->_id;
    $this->patient_id      = $patient_id;

    // Chargement de la consult avec la plageconsult && le patient
    if ($matching) {
      $this->loadMatchingObjectEsc();
    }

    if (!$this->_id) {
      $this->heure   = $time_now;
      $this->arrivee = "$day_now $time_now";
      $this->duree   = $duree;
      $this->chrono  = $chrono;
    }

    return $this->store();
  }

  /**
   * Crée la dossier d'anesthésie associée à la consultation
   *
   * @return null|string Store-like message
   */
  function createConsultAnesth() {
    $this->loadRefPlageConsult();

    if (!$this->_is_anesth || !$this->patient_id || !$this->_id || $this->type == "entree") {
      return null;
    }

    // Création de la consultation préanesthésique
    $this->_count["consult_anesth"] = null;
    $consultAnesth                  = $this->loadRefConsultAnesth();
    $operation                      = new COperation();
    if (!$consultAnesth->_id || $this->_operation_id) {
      if (!$consultAnesth->_id) {
        $consultAnesth->consultation_id = $this->_id;
        $consultAnesth->sejour_id       = $this->sejour_id;
      }
      if ($this->_operation_id) {
        // Association à l'intervention
        $consultAnesth->operation_id = $this->_operation_id;
        $operation                   = $consultAnesth->loadRefOperation();
      }
      if ($msg = $consultAnesth->store()) {
        return $msg;
      }
    }

    // Remplissage du motif préanesthésique si creation et champ motif vide
    if ($operation->_id) {
      $format_motif = CAppUI::gconf('dPcabinet CConsultAnesth format_auto_motif');
      $format_rques = CAppUI::gconf('dPcabinet CConsultAnesth format_auto_rques');

      if (($format_motif && !$this->motif) || ($format_rques && !$this->rques)) {
        $operation = $consultAnesth->_ref_operation;
        $operation->loadRefPlageOp();
        $sejour = $operation->loadRefSejour();
        $chir   = $operation->loadRefChir();
        $chir->updateFormFields();

        $items = array(
          '%N' => $chir->_user_last_name,
          '%P' => $chir->_user_first_name,
          '%S' => $chir->_shortview,
          '%L' => $operation->libelle,
          '%i' => CMbDT::format($operation->_datetime_best, CAppUI::conf('time')),
          '%I' => CMbDT::format($operation->_datetime_best, CAppUI::conf('date')),
          '%E' => CMbDT::format($sejour->entree_prevue, CAppUI::conf('date')),
          '%e' => CMbDT::format($sejour->entree_prevue, CAppUI::conf('time')),
          '%T' => strtoupper(substr($sejour->type, 0, 1)),
        );

        if ($format_motif && !$this->motif) {
          $this->motif = str_replace(array_keys($items), $items, $format_motif);
        }

        if ($format_rques && !$this->rques) {
          $this->rques = str_replace(array_keys($items), $items, $format_rques);
        }

        if ($msg = parent::store()) {
          return $msg;
        }
      }
    }

    return null;
  }

  /**
   * Change le praticien de la consult
   *
   * @param int $change_prat_id ID du nouveau chirurgien de la consultation
   * @param integer $agenda_praticien_id Identifiant de l'agenda du praticien
   *
   * @return string|void|null
   */
  function changePraticien($change_prat_id, $agenda_praticien_id = null) {
    $this->loadRefPlageConsult();
    $_datetime = $this->_datetime;

    $day_now     = CMbDT::format($_datetime, "%Y-%m-%d");
    $time_now    = CMbDT::format($_datetime, "%H:%M:00");
    $hour_now    = CMbDT::format($_datetime, "%H:00:00");
    $hour_next   = CMbDT::time("+1 HOUR", $hour_now);
    $plage       = new CPlageconsult();
    $plageBefore = new CPlageconsult();
    $plageAfter  = new CPlageconsult();

    // Cas ou une plage correspond
    $where            = array();
    $where["chir_id"] = "= '$change_prat_id'";
    $where["date"]    = "= '$day_now'";
    $where["debut"]   = "<= '$time_now'";
    $where["fin"]     = "> '$time_now'";
    if ($agenda_praticien_id) {
      $where["agenda_praticien_id"] = "= '$agenda_praticien_id'";
    }
    $plage->loadObject($where);

    if (!$plage->_id) {
      // Cas ou on a des plage en collision
      $where            = array();
      $where["chir_id"] = "= '$change_prat_id'";
      $where["date"]    = "= '$day_now'";
      $where["debut"]   = "<= '$hour_now'";
      $where["fin"]     = ">= '$hour_now'";
      $plageBefore->loadObject($where);
      $where["debut"] = "<= '$hour_next'";
      $where["fin"]   = ">= '$hour_next'";
      $plageAfter->loadObject($where);
      if ($plageBefore->_id) {
        if ($plageAfter->_id) {
          $plageBefore->fin = $plageAfter->debut;
        }
        else {
          $plageBefore->fin = max($plageBefore->fin, $hour_next);
        }
        $plage =& $plageBefore;
      }
      elseif ($plageAfter->_id) {
        $plageAfter->debut = min($plageAfter->debut, $hour_now);
        $plage             =& $plageAfter;
      }
      else {
        $plage->chir_id = $change_prat_id;
        $plage->date    = $day_now;
        $plage->freq    = "00:" . CPlageconsult::$minutes_interval . ":00";
        $plage->debut   = $hour_now;
        $plage->fin     = $hour_next;
        $plage->libelle = "automatique";
      }

      if ($agenda_praticien_id) {
        $plage->agenda_praticien_id = $agenda_praticien_id;
      }

      $plage->updateFormFields();
      if ($msg = $plage->store()) {
        return $msg;
      }
    }

    $this->plageconsult_id = $plage->_id;
  }

  /**
   * Change la date de la consult
   *
   * @param string  $datetime            Date et heure
   * @param integer $duree               Durée de la consultation
   * @param integer $chrono              Etat de la consultation
   * @param integer $periode             Période de la plage
   * @param integer $agenda_praticien_id Identifiant de l'agenda du praticien
   *
   * @return null|string Store-like message
   * @throws Exception
   */
  function changeDateTime($datetime, $duree = 1, $chrono = self::PLANIFIE, $periode = null, $agenda_praticien_id = null) {
    $minutes_interval = CValue::first(CAppUI::gconf("dPcabinet CPlageconsult minutes_interval"), "15");
    $periode          = ($periode) ?: ("00:" . ($minutes_interval ?: "05") . ":00");

    $day_now   = CMbDT::format($datetime, "%Y-%m-%d");
    $time_now  = CMbDT::format($datetime, "%H:%M:00");
    $hour_now  = CMbDT::format($datetime, "%H:00:00");
    $hour_next = CMbDT::time("+1 HOUR", $hour_now);

    $plage       = new CPlageconsult();
    $plageBefore = new CPlageconsult();
    $plageAfter  = new CPlageconsult();

    $chir_id = $this->_praticien_id;

    // Cas ou une plage correspond
    $where            = array();
    $where["chir_id"] = "= '$chir_id'";
    $where["date"]    = "= '$day_now'";
    $where["debut"]   = "<= '$time_now'";
    $where["fin"]     = "> '$time_now'";
    if ($agenda_praticien_id) {
      $where["agenda_praticien_id"] = "= '$agenda_praticien_id'";
    }
    $plage->loadObject($where);

    if (!$plage->plageconsult_id) {
      // Cas ou on a des plage en collision
      $where            = array();
      $where["chir_id"] = "= '$chir_id'";
      $where["date"]    = "= '$day_now'";
      $where["debut"]   = "<= '$hour_now'";
      $where["fin"]     = ">= '$hour_now'";
      if ($agenda_praticien_id) {
        $where["agenda_praticien_id"] = "= '$agenda_praticien_id'";
      }
      $plageBefore->loadObject($where);
      $where["debut"] = "<= '$hour_next'";
      $where["fin"]   = ">= '$hour_next'";
      $plageAfter->loadObject($where);

      if ($plageBefore->_id) {
        $plageBefore->fin = $plageAfter->_id ?
          $plageAfter->debut :
          max($plageBefore->fin, $hour_next);
        $plage            = $plageBefore;
      }
      elseif ($plageAfter->_id) {
        $plageAfter->debut = min($plageAfter->debut, $hour_now);
        $plage             = $plageAfter;
      }
      else {
        $plage->chir_id = $chir_id;
        $plage->date    = $day_now;
        $plage->freq    = $periode;
        $plage->debut   = $hour_now;
        $plage->fin     = $hour_next;
      }
      if ($agenda_praticien_id) {
        $plage->agenda_praticien_id = $agenda_praticien_id;
      }

      $plage->updateFormFields();

      if ($msg = $plage->store()) {
        return $msg;
      }
    }

    $this->plageconsult_id = $plage->_id;

    $this->arrivee = "$day_now $time_now";
    $this->duree   = $duree;
    $this->chrono  = $chrono;
    $this->heure   = $time_now;

    // Obligé de mettre à null pour passer le updatePlainField
    $this->_hour = null;

    $this->_datetime = "$this->heure $this->_date";
  }

  /**
   * Charge les praticiens susceptibles d'être concernés par les consultation
   * en fonction de les préférences utilisateurs
   *
   * @param int    $permType    Type de permission
   * @param string $function_id Fonction spécifique
   * @param string $name        Nom spécifique
   * @param bool   $secondary   Chercher parmi les fonctions secondaires
   * @param bool   $actif       Seulement les actifs
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   *
   * @return CMediusers[]
   */
  static function loadPraticiens($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true) {
    $user = new CMediusers();

    return $user->loadProfessionnelDeSanteByPref($permType, $function_id, $name, $secondary, $actif, $use_group);
  }

  /**
   * Charge les praticiens à la compta desquels l'utilisateur courant a accès
   *
   * @param string $prat_id    Si définit, retourne un tableau avec seulement ce praticien
   * @param bool   $actif_only Uniquement les utilisateurs actifs
   *
   * @return CMediusers[]
   * @todo Définir verbalement la stratégie
   */
  static function loadPraticiensCompta($prat_id = null, $actif_only = true) {
    // Cas du praticien unique
    if ($prat_id) {
      $prat = CMediusers::get($prat_id);
      $prat->loadRefFunction();
      $users = array($prat->_id => $prat);
      $prat->loadRefsSecondaryUsers();
      foreach ($prat->_ref_secondary_users as $_user) {
        $_user->loadRefFunction();
        $users[$_user->_id] = $_user;
      }

      return $users;
    }

    // Cas standard
    $user              = CMediusers::get();
    $is_admin          = in_array(CUser::$types[$user->_user_type], array("Administrator"));
    $is_admin_secr_dir = $is_admin || in_array(CUser::$types[$user->_user_type], array("Secrétaire", "Directeur"));

    // Récupération des fonctions de l'utilisateur
    $function  = $user->loadRefFunction();
    $functions = $user->loadRefsSecondaryFunctions();
    foreach ($functions as $_function) {
      if (!$_function->compta_partagee) {
        unset($functions[$_function->_id]);
      }
    }
    $functions = CMbArray::mergeKeys($functions, array($function->_id => $function));

    $praticiens = array();
    // Liste des praticiens du cabinet
    if ($is_admin_secr_dir || $function->compta_partagee) {
      if ($is_admin && (CAppUI::gconf("dPcabinet Comptabilite show_compta_tiers") || $user->_user_username == "admin")) {
        $functions = array(new CFunctions());
      }

      foreach ($functions as $_function) {
        $praticiens = CMbArray::mergeKeys($praticiens, CConsultation::loadPraticiens(PERM_EDIT, $_function->_id, null, null, $actif_only));
      }
      if (!$is_admin) {
        // On ajoute les praticiens qui ont délégués leurs compta
        $where   = array();
        $where[] = "users_mediboard.compta_deleguee <> '0' || users_mediboard.user_id " .
          CSQLDataSource::prepareIn(array_keys($praticiens));
        // Filters on users values
        if ($actif_only) {
          $where["users_mediboard.actif"] = "= '1'";
        }
        $where["functions_mediboard.group_id"] = "= '" . CGroups::loadCurrent()->_id . "'";

        $ljoin["users"]               = "users.user_id = users_mediboard.user_id";
        $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";

        $order = "users.user_last_name, users.user_first_name";

        $mediuser = new CMediusers();
        /** @var CMediusers[] $mediusers */
        $mediusers = $mediuser->loadListWithPerms(PERM_EDIT, $where, $order, null, null, $ljoin);

        // Associate already loaded function
        foreach ($mediusers as $_mediuser) {
          $_mediuser->loadRefFunction();
        }
        $praticiens = CMbArray::mergeKeys($praticiens, $mediusers);
      }
    }
    if ($user->isProfessionnelDeSante() && $user->compta_deleguee != "1") {
      $praticiens = CMbArray::mergeKeys($praticiens, array($user->_id => $user));
    }

    return $praticiens;
  }

  /**
   * Construit le tag d'une consultation en fonction des variables de configuration
   *
   * @param string $group_id Permet de charger l'id externe d'uns consultation pour un établissement donné si non null
   *
   * @return string|null Nul si indisponible
   */
  static function getTagConsultation($group_id = null) {
    // Pas de tag consultation
    if (null == $tag_consultation = CAppUI::gconf("dPcabinet CConsultation tag")) {
      return null;
    }

    // Permettre des id externes en fonction de l'établissement
    $group = CGroups::loadCurrent();
    if (!$group_id) {
      $group_id = $group->_id;
    }

    return str_replace('$g', $group_id, $tag_consultation);
  }

  /**
   * @see parent::getDynamicTag
   */
  function getDynamicTag() {
    return $this->gconf("tag");
  }

  /**
   * @inheritdoc
   */
  function loadRelPatient() {
    return $this->loadRefPatient();
  }

  /**
   * @inheritdoc
   */
  function loadRelGroup() {
    return $this->loadRefGroup();
  }

  /**
   * Charge le dossier d'anesthésie de la plage d'op la plus ancienne
   *
   * @return CConsultAnesth
   */
  function loadRefFirstDossierAnesth() {
    // Chargement des plages de chaques dossiers
    foreach ($this->_refs_dossiers_anesth as $_dossier) {
      $_dossier->loadRefOperation()->loadRefPlageOp();
    }
    $plages = CMbArray::pluck($this->_refs_dossiers_anesth, "_ref_operation", "_ref_plageop", "date");
    array_multisort($plages, SORT_ASC, $this->_refs_dossiers_anesth);

    return $this->_ref_consult_anesth = reset($this->_refs_dossiers_anesth);
  }

  /**
   * Get the patient_id of CMbobject
   *
   * @return CPatient
   */
  function getIndexablePatient() {
    return $this->loadRelPatient();
  }

  /**
   * Loads the related fields for indexing datum (patient_id et date)
   *
   * @return array
   */
  function getIndexableData() {
    $this->getIndexablePraticien();
    $array["id"]          = $this->_id;
    $array["author_id"]   = $this->_praticien_id;
    $array["prat_id"]     = $this->_ref_praticien->_id;
    $array["title"]       = $this->type;
    $array["body"]        = $this->getIndexableBody("");
    $array["date"]        = str_replace("-", "/", $this->loadRefPlageConsult()->date);
    $array["function_id"] = $this->_ref_praticien->function_id;
    $array["group_id"]    = $this->_ref_praticien->loadRefFunction()->group_id;
    $array["patient_id"]  = $this->getIndexablePatient()->_id;
    $sejour               = $this->loadRefSejour();
    if ($sejour && $sejour->_id) {
      $array["object_ref_id"]    = $this->_ref_sejour->_id;
      $array["object_ref_class"] = $this->_ref_sejour->_class;
    }
    else {
      $array["object_ref_id"]    = $this->_id;
      $array["object_ref_class"] = $this->_class;
    }


    return $array;
  }

  /**
   * count the number of consultations asking to be
   *
   * @param array()   $chir_ids list of chir ids
   * @param string $day date targeted, default = today
   *
   * @return int number of result
   */
  static function countDesistementsForDay($chir_ids, $day = null) {
    $date         = CMbDT::date($day);
    $consultation = new self();
    $ds           = $consultation->getDS();
    $where        = array(
      "plageconsult.date"           => " > '$date'",
      "consultation.si_desistement" => "= '1'",
      "consultation.annule"         => "= '0'",
    );
    $where[]      = "plageconsult.chir_id " . $ds->prepareIn($chir_ids) . " OR plageconsult.remplacant_id " . $ds->prepareIn($chir_ids);
    $ljoin        = array(
      "plageconsult" => "plageconsult.plageconsult_id = consultation.plageconsult_id",
    );

    return $consultation->countList($where, null, $ljoin);
  }


  /**
   * load "adresse par prat"
   *
   * @return CMedecin|null
   */
  function loadRefAdresseParPraticien() {
    return $this->_ref_adresse_par_prat = $this->loadFwdRef("adresse_par_prat_id", true);
  }

  /**
   * Redesign the content of the body you will index
   *
   * @param string $content The content you want to redesign
   *
   * @return string
   */
  function getIndexableBody($content) {
    $fields = $this->getTextcontent();
    foreach ($fields as $_field) {
      $content .= " " . $this->$_field;
    }

    return $content;
  }

  /**
   * Get the praticien_id of CMbobject
   *
   * @return CMediusers
   */
  function getIndexablePraticien() {
    return $this->loadRefPraticien();
  }

  /**
   * @param CConsultation   $consult
   * @param CDossierMedical $dossier_medical
   * @param CConsultAnesth  $consultAnesth
   * @param CSejour         $sejour
   * @param array           $list_etat_dents
   *
   * @return array
   */
  static function makeTabsCount($consult, $dossier_medical, $consultAnesth, $sejour) {
    $tabs_count = array(
      "AntTrait"            => 0,
      "Constantes"          => 0,
      "prescription_sejour" => 0,
      "facteursRisque"      => 0,
      "Examens"             => 0,
      "Exams"               => 0,
      "ExamsComp"           => 0,
      "Intub"               => 0,
      "InfoAnesth"          => 0,
      "dossier_suivi"       => 0,
      "Actes"               => 0,
      "fdrConsult"          => 0,
      "reglement"           => 0,
    );

    if (CModule::getActive("dPprescription")) {
      CPrescription::$_load_lite = true;
    }
    foreach ($tabs_count as $_tab => $_count) {
      $count = 0;
      switch ($_tab) {
        case "AntTrait":
          $prescription = $dossier_medical->loadRefPrescription();
          $count_meds   = 0;
          if (CModule::getActive("dPprescription") && CPrescription::isMPMActive()) {
            $count_meds = $prescription->countBackRefs("prescription_line_medicament");
          }
          $count_cim = is_array($dossier_medical->_ext_codes_cim) ? count($dossier_medical->_ext_codes_cim) : 0;

          $dossier_medical->countTraitements();
          $dossier_medical->countAntecedents();
          $tabs_count[$_tab] =
            $dossier_medical->_count_antecedents
            + $dossier_medical->_count_cancelled_traitements
            + $dossier_medical->_count_traitements
            + $dossier_medical->_count_cancelled_antecedents
            + $count_meds
            + $count_cim;
          break;
        case "Constantes":
          if ($sejour->_ref_rpu && $sejour->_ref_rpu->_id) {
            $tabs_count[$_tab] = $sejour->countBackRefs("contextes_constante");
          }
          else {
            $tabs_count[$_tab] = $consult->countBackRefs("contextes_constante");
          }
          break;
        case "prescription_sejour":
          $_sejour = $sejour;
          if ($consultAnesth->_id && $consultAnesth->operation_id) {
            $_sejour = $consultAnesth->loadRefOperation()->loadRefSejour();
          }

          if ($_sejour->_id) {
            $_sejour->loadRefsPrescriptions();
            foreach ($_sejour->_ref_prescriptions as $key => $_prescription) {
              if (!$_prescription->_id) {
                unset($_sejour->_ref_prescriptions[$key]);
                continue;
              }

              $_sejour->_ref_prescriptions[$_prescription->_id] = $_prescription;
              unset($_sejour->_ref_prescriptions[$key]);
            }

            if (count($_sejour->_ref_prescriptions)) {
              $prescription = new CPrescription();
              $prescription->massCountMedsElements($_sejour->_ref_prescriptions);
              foreach ($_sejour->_ref_prescriptions as $_prescription) {
                $count += array_sum($_prescription->_counts_by_chapitre);
              }
            }
          }

          $tabs_count[$_tab] = $count;
          break;
        case "facteursRisque":
          if (!$consultAnesth) {
            break;
          }
          if ($dossier_medical->_id) {
            $fields = array(
              "risque_antibioprophylaxie", "risque_MCJ_chirurgie", "risque_MCJ_patient",
              "risque_prophylaxie", "risque_thrombo_chirurgie", "risque_thrombo_patient",
            );

            foreach ($fields as $_field) {
              if ($dossier_medical->$_field != "NR") {
                $count++;
              }
            }

            if ($dossier_medical->facteurs_risque) {
              $count++;
            }
          }
          $tabs_count[$_tab] = $count;
          break;
        case "Examens":
          if ($consultAnesth->_id) {
            break;
          }
          $fields = array("motif", "rques", "examen", "histoire_maladie", "conclusion");
          foreach ($fields as $_field) {
            if ($consult->$_field) {
              $count++;
            }
          }
          $count             += $consult->countBackRefs("examaudio");
          $count             += $consult->countBackRefs("examnyha");
          $count             += $consult->countBackRefs("exampossum");
          $tabs_count[$_tab] = $count;
          break;
        case "Exams":
          if (!$consultAnesth->_id) {
            break;
          }
          $fields = array("examenCardio", "examenPulmo", "examenDigest", "examenAutre");
          foreach ($fields as $_field) {
            if ($consultAnesth->$_field) {
              $count++;
            }
          }
          if ($consult->examen != "") {
            $count++;
          }
          $count             += $consult->countBackRefs("examaudio");
          $count             += $consult->countBackRefs("examnyha");
          $count             += $consult->countBackRefs("exampossum");
          $tabs_count[$_tab] = $count;
          break;
        case "ExamsComp":
          if (!$consultAnesth->_id) {
            break;
          }
          $count += $consult->countBackRefs("examcomp");
          if ($consultAnesth->result_ecg) {
            $count++;
          }
          if ($consultAnesth->result_rp) {
            $count++;
          }
          $tabs_count[$_tab] = $count;
          break;
        case "Intub":
          if (!$consultAnesth->_id) {
            break;
          }
          $fields = array(
            "mallampati", "bouche", "distThyro", "mob_cervicale", "etatBucco", "conclusion",
            "plus_de_55_ans", "edentation", "barbe", "imc_sup_26", "ronflements", "piercing",
          );
          foreach ($fields as $_field) {
            if ($consultAnesth->$_field) {
              $count++;
            }
          }
          $consult->loadListEtatsDents();
          $count             += count(array_filter($consult->_list_etat_dents));
          $tabs_count[$_tab] = $count;
          break;
        case "InfoAnesth":
          if (!$consultAnesth->_id) {
            break;
          }
          $op = $consultAnesth->loadRefOperation();

          $fields_anesth = array("prepa_preop", "premedication", "apfel_femme", "apfel_non_fumeur", "apfel_atcd_nvp", "apfel_morphine");
          $fields_op     = array("passage_uscpo", "type_anesth", "ASA", "position_id");

          foreach ($fields_anesth as $_field) {
            if ($consultAnesth->$_field) {
              $count++;
            }
          }
          if ($op->_id) {
            foreach ($fields_op as $_field) {
              if ($op->$_field) {
                $count++;
              }
            }
          }

          if ($consult->rques) {
            $count++;
          }

          $count += $consultAnesth->countBackRefs("techniques");

          $tabs_count[$_tab] = $count;
          break;
        case "dossier_suivi":
          break;
        case "Actes":
          $consult->countActes();
          $tabs_count[$_tab] = $consult->_count_actes;

          if ($sejour->_id) {
            if ($sejour->DP) {
              $tabs_count[$_tab]++;
            }
            if ($_sejour->DR) {
              $tabs_count[$_tab]++;
            }
            $sejour->loadDiagnosticsAssocies();
            $tabs_count[$_tab] += count($sejour->_diagnostics_associes);
          }
          break;
        case "fdrConsult":
          $consult->_docitems_from_dossier_anesth = false;
          $consult->countDocs();
          $consult->countFiles();
          $consult->loadRefsPrescriptions();
          $tabs_count[$_tab] = $consult->_nb_docs + $consult->_nb_files;
          if (isset($consult->_ref_prescriptions["externe"])) {
            $tabs_count[$_tab]++;
          }
          if ($sejour->_id) {
            $sejour->countDocs();
            $sejour->countFiles();
            $tabs_count[$_tab] += $sejour->_nb_docs + $sejour->_nb_files;
          }
          break;
        case "reglement":
          $consult->loadRefFacture()->loadRefsReglements();
          $tabs_count[$_tab] = count($consult->_ref_facture->_ref_reglements);
      }
    }
    if (CModule::getActive("dPprescription")) {
      CPrescription::$_load_lite = false;
    }

    return $tabs_count;
  }

  function loadRefBrancardage() {
    if (!CModule::getActive("brancardage") || !$this->sejour_id || !CAppUI::gconf("brancardage General use_brancardage")) {
      return null;
    }

    $brancardage               = new CBrancardage();
    $brancardage->sejour_id    = $this->sejour_id;
    $brancardage->object_id    = $this->_id;
    $brancardage->object_class = $this->_class;
    $brancardage->loadMatchingObject();
    if ($brancardage->_id) {
      $brancardage->loadRefItems();

      return $this->_ref_brancardage = $brancardage;
    }

    $this->loadRefPlageConsult();
    $ljoin                                       = array();
    $ljoin["brancardage_item"]                   = "brancardage_item.brancardage_id = brancardage.brancardage_id";
    $where                                       = array();
    $where["brancardage.sejour_id"]              = " = '$this->sejour_id'";
    $where["brancardage.date"]                   = " = '$this->_date'";
    $where["brancardage_item.destination_class"] = " = 'CService'";

    $brancardage = new CBrancardage();
    $brancardage->loadObject($where, "brancardage_id DESC", null, $ljoin);
    $brancardage->loadRefItems();

    if (!$brancardage->_id) {
      $brancardage->date = $this->_date;
    }

    return $this->_ref_brancardage = $brancardage;
  }


  /**
   * @inheritdoc
   */
  function loadAllDocs($params = array()) {
    $this->mapDocs($this, $params);
  }

  /**
   * Gets icon for current patient event
   *
   * @return array
   */
  function getEventIcon() {
    $icon = array(
      'icon'  => 'fa fa-stethoscope me-event-icon',
      'color' => 'steelblue',
      'title' => CAppUI::tr($this->_class),
    );

    if ($this->grossesse_id) {
      $icon['color'] = 'palevioletred';
      $icon['title'] = CAppUI::tr('CConsultation-title-Consultation with pregnancy');
    }

    if (in_array($this->_type, CSejour::getTypesSejoursUrgence($this->loadRefSejour()->praticien_id))) {
      $icon['color'] = 'firebrick';
      $icon['title'] = CAppUI::tr('CConsultation-title-Emergency consultation');
    }

    return $icon;
  }

  /**
   * Chargement de constantes médicales
   *
   * @param array $where Clauses where
   *
   * @return CConstantesMedicales[]
   */
  function loadListConstantesMedicales($where = array()) {
    if ($this->_list_constantes_medicales) {
      return $this->_list_constantes_medicales;
    }

    $constantes = new CConstantesMedicales();

    $where["patient_id"]    = "= '$this->patient_id'";
    $where["context_class"] = "= '$this->_class'";
    $where["context_id"]    = "= '$this->_id'";

    return $this->_list_constantes_medicales = $constantes->loadList($where, "datetime ASC");
  }

  /**
   * Analyse si la consult d'anesth contient une DHE associée, s'il en existe une ou pas
   *
   * @param string $date date de la consultation
   *
   * @return void
   */
  function checkDHE($date = null) {
    if (!$date) {
      $date = $this->loadRefPlageConsult()->date;
    }
    foreach ($this->loadRefsDossiersAnesth() as $_consult_anesth) {
      $_consult_anesth->_etat_dhe_anesth = null;
      $operation                         = $_consult_anesth->loadRefOperation();
      if ($operation->_id && $operation->_ref_sejour->_id) {
        $_consult_anesth->_etat_dhe_anesth = "associe";
        $this->_etat_dhe_anesth            = "associe";
      }
      else {
        $next = $this->_ref_patient->getNextSejourAndOperation($date);
        if ($next["CSejour"]->_id) {
          $_consult_anesth->_etat_dhe_anesth = "dhe_exist";
          if ($this->_etat_dhe_anesth != "associe") {
            $this->_etat_dhe_anesth = "dhe_exist";
          }
        }
        else {
          $_consult_anesth->_etat_dhe_anesth = "non_associe";
          if (!$this->_etat_dhe_anesth) {
            $this->_etat_dhe_anesth = "non_associe";
          }
        }
      }
    }
  }

  /**
   * Choix de la couleur de la consultation du nouveau planning
   *
   * @return string
   */
  function colorPlanning() {
    $color = CAppUI::isMediboardExtDark() ? "#f16860" : "#fee";
    if (!$this->patient_id) {
      if ($this->groupee && $this->no_patient) {
        $color = CAppUI::isMediboardExtDark() ? "#eb742f" : "#e5b774";
      }
      else {
        $color = CAppUI::isMediboardExtDark() ? "#726f73" : "#a7a3a3";
      }
    }
    elseif ($this->premiere) {
      $color = CAppUI::isMediboardExtDark() ? "#f16860" : "#faa";
    }
    elseif ($this->derniere) {
      $color = CAppUI::isMediboardExtDark() ? "#a88cdc" : "#faf";
    }
    elseif ($this->sejour_id) {
      $color = CAppUI::isMediboardExtDark() ? "#81a03e" : "#CFFFAD";
    }

    return $this->_color_planning = $color;
  }

  /**
   * Ordonne l'état des dents
   *
   * @return array
   */
  function loadListEtatsDents() {
    $list_etat_dents = array();
    $dossier_medical = $this->_ref_patient->loadRefDossierMedical();
    if ($dossier_medical->_id) {
      $etat_dents = $dossier_medical->loadRefsEtatsDents();
      foreach ($etat_dents as $etat) {
        $list_etat_dents[$etat->dent] = $etat->etat;
      }
    }

    return $this->_list_etat_dents = $list_etat_dents;
  }

  /**
   * Ajout du cartouche SMS s'il est necessaire dans le planning
   *
   * @return string
   */
  function smsPlaning() {
    $title = "";
    if ($this->_ref_notification && $this->_ref_notification->_channel) {
      $notification = $this->_ref_notification;
      $title        = "<span class=\"texticon texticon-gray\"";
      if ($notification->_id) {
        $notification->loadRefMessage();
        if (in_array($notification->_message->status, ["transmitted", "delivered"])) {
          $title = "<span class=\"texticon texticon-allergies-ok\"";
          $title .= " title=\"" . CMbString::htmlEncode(CAppUI::tr("common-$notification->_channel sent")) . "\"";
        }
        elseif (in_array($notification->_message->status, ["failed_transmission", "cancelled", "failed_delivery"])) {
          $title = "<span class=\"texticon texticon-stup texticon-stroke\"";
          $title .= " title=\"" . CMbString::htmlEncode(CAppUI::tr("common-$notification->_channel in error")) . "\"";
        }
      }
      $title .= "style=\"float:right\">" . CAppUI::tr("CNotificationEvent.type.$notification->_channel") . "</span>";
    }

    return $title;
  }

  /**
   * Affecte le bon type de transport à la notification si elle n'existe pas
   *
   * @return CNotification
   * @throws Exception
   */
  function loadRefNotification() {
    $notifications = parent::loadRefNotifications();
    $notification  = new CNotification();
    if (count($notifications)) {
      foreach ($notifications as $_notification) {
        $_notification->loadRefMessage();
        if ($_notification->_message && $_notification->_message->status === "delivered") {
          $notification = $_notification;
          break;
        }
        elseif (!$notification->_id || ($_notification->_message && ($_notification->_message->status === "transmitted"
              || ($_notification->_message->status === "scheduled" && $notification->_message->status !== "transmitted")
              || !in_array($notification->_message->status, array("transmitted", "scheduled"))))
        ) {
          $notification = $_notification;
        }
      }
    }
    $this->_ref_notification = $notification;

    $patient = $this->_ref_patient;
    if ($notification !== null && !$notification->_id && $patient->allow_sms_notification) {
      if (!$patient->tel2 && $patient->email) {
        $notification->_channel = 'email';
      }
      elseif (!$patient->email && $patient->tel) {
        $notification->_channel = 'sms';
      }
      else {
        $praticien_id = $this->_ref_praticien ? $this->_ref_praticien->_id : $this->loadRefPraticien()->_id;
        $event        = CNotificationEvent::searchNotificationUser($praticien_id);
        if ($event->_id) {
          $notification->_channel = $event->channel;
        }
        else {
          $this->_ref_notification = null;
        }
      }
    }
    elseif ($notification->_id) {
      $notification->loadRefContext();
      $notification->loadRefMessage();
    }

    return $this->_ref_notification;
  }


  /**
   * @inheritdoc
   */
  function getSpecialIdex(CIdSante400 $idex) {
    if (CModule::getActive("appFineClient")) {
      if ($idex_type = CAppFineClient::getSpecialIdex($idex)) {
        return $idex_type;
      }
    }

    if (CModule::getActive("doctolib")) {
      if ($idex_type = CDoctolib::getSpecialIdex($idex)) {
        return $idex_type;
      }
    }

    return null;
  }

  /**
   * Charge les relances des dossiers AppFine
   *
   * @param string $type type
   *
   * @return CAppFineClientFolderLiaison
   */
  function loadRefsFoldersRelaunch() {
    return $this->_refs_appfine_client_folders_relaunch = $this->loadBackRefs("folder_relaunch");
  }

  /**
   * Charge les relances des dossiers AppFine par type de dossier
   *
   * @param string $type type
   *
   * @return CAppFineClientFolderLiaison
   */
  function loadRefsFoldersRelaunchByType() {
    $pread   = $this->countBackRefs("folder_relaunch", array('type' => " = 'pread'"), null, false, 'folder_relaunch_pread');
    $preop   = $this->countBackRefs("folder_relaunch", array('type' => " = 'preop'"), null, false, 'folder_relaunch_preop');
    $postop  = $this->countBackRefs("folder_relaunch", array('type' => " = 'postop'"), null, false, 'folder_relaunch_postop');
    $consult = $this->countBackRefs("folder_relaunch", array('type' => " = 'consult'"), null, false, 'folder_relaunch_consult');

    return $this->addToStore(
      "count_appFine_folders_relaunch",
      array(
        'pread' => $pread,
        'preop' => $preop,
        'postop' => $postop,
        'consult' => $consult,
      )
    );
  }

  /**
   * Chargement des demandes AppFine
   *
   * @return CAppFineClientOrderItem[]
   */
  function loadRefsOrdersItem($where = array(), $ljoin = array()) {
    return $this->_ref_orders_item = $this->loadBackRefs("appFine_order_items", null, null, null, $ljoin, null, null, $where);
  }

  /**
   * Chargement des documents AppFine non liés à une demande
   *
   * @return CAppFineClientObjectReceived[]
   */
  function loadRefsObjectsReceived() {
    return $this->_ref_objects_received = $this->loadBackRefs("object_received");
  }

  /**
   * Chargement de la liste complète des infos de checklist
   *
   * @return CInfoChecklist[]
   */
  function loadRefsInfoChecklist() {
    $this->loadRefsInfoChecklistItem();
    if (!$this->_ref_chir) {
      $this->loadRefPraticien();
    }
    $infos = CInfoChecklist::loadListWithFunction($this->_ref_chir->function_id);
    foreach ($infos as $_info) {
      foreach ($this->_refs_info_check_items as $_item) {
        if ($_item->info_checklist_id == $_info->_id) {
          $_info->_item_id = $_item->_id;
        }
      }
    }

    $this->_ref_info_checklist_item = new CInfoChecklistItem();
    $this->_refs_info_checklist     = $infos;
  }

  /**
   * Chargement des item de checklist utilisé
   *
   * @param bool $reponse Réponse
   *
   * @return CInfoChecklistItem[]
   */
  function loadRefsInfoChecklistItem($reponse = false) {
    $where                       = array();
    $where["consultation_class"] = " = 'CConsultation'";
    if ($reponse) {
      $where["reponse"] = " = '1'";
    }
    $this->_refs_info_check_items = $this->loadBackRefs("info_check_item", null, null, "info_checklist_item_id", null, null, "", $where);
    if ($reponse) {
      foreach ($this->_refs_info_check_items as $_item) {
        $_item->loadRefInfoChecklist();
      }
    }

    return $this->_refs_info_check_items;
  }

  /**
   * Charge les transports de la consultation
   *
   * @param array where
   *
   * @return CTransport[]
   */
  public function loadRefsTransports($where = array()) {
    $order   = "transport_id DESC, datetime DESC";
    $where[] = "transport.statut <> 'prescribed'";

    return $this->_refs_transports = $this->loadBackRefs("transports", $order, null, null, null, null, "", $where);
  }

  /**
   * Charge les arrêts de travail de la consultation
   *
   * @param array where
   *
   * @return CAvisArretTravail[]
   */
  public function loadRefsAvisArretsTravail($where = array()) {
    $order = "debut DESC";

    return $this->_refs_avis_arrets_travail = $this->loadBackRefs("arret_travail", $order, null, null, null, null, "", $where);
  }

  /**
   * Crée une observation d'entrée, pour les formulaires en volet
   *
   * @param CMbObject $reference1 First reference
   * @param CMbObject $reference2 Second reference
   *
   * @return CConsultation|null
   */
  function formTabAction_createObsEntree(CMbObject $reference1, CMbObject $reference2 = null) {
    if ($reference1 instanceof CSejour) {
      $consult = $reference1->loadRefObsEntree();

      if (!$consult->_id) {
        $datetime = CMbDT::dateTime();
        $chir     = CMediusers::get();

        $day_now   = CMbDT::format($datetime, "%Y-%m-%d");
        $time_now  = CMbDT::format($datetime, "%H:%M:00");
        $hour_now  = CMbDT::format($datetime, "%H:00:00");
        $hour_next = CMbDT::time("+1 HOUR", $hour_now);

        $plage       = new CPlageconsult();
        $plageBefore = new CPlageconsult();
        $plageAfter  = new CPlageconsult();

        // Cas ou une plage correspond
        $where            = array();
        $where["chir_id"] = "= '$chir->_id'";
        $where["date"]    = "= '$day_now'";
        $where["debut"]   = "<= '$time_now'";
        $where["fin"]     = "> '$time_now'";
        $plage->loadObject($where);

        if (!$plage->_id) {
          // Cas ou on a des plage en collision
          $where            = array();
          $where["chir_id"] = "= '$chir->_id'";
          $where["date"]    = "= '$day_now'";
          $where["debut"]   = "<= '$hour_now'";
          $where["fin"]     = ">= '$hour_now'";
          $plageBefore->loadObject($where);
          $where["debut"] = "<= '$hour_next'";
          $where["fin"]   = ">= '$time_now'";
          $plageAfter->loadObject($where);
          if ($plageBefore->_id) {
            if ($plageAfter->_id) {
              $plageBefore->fin = $plageAfter->debut;
            }
            else {
              $plageBefore->fin = max($plageBefore->fin, $hour_next);
            }
            $plage =& $plageBefore;
          }
          elseif ($plageAfter->_id) {
            $plageAfter->debut = min($plageAfter->debut, $hour_now);
            $plage             =& $plageAfter;
          }
          else {
            $plage->chir_id          = $chir->_id;
            $plage->date             = $day_now;
            $plage->freq             = "00:" . CPlageconsult::$minutes_interval . ":00";
            $plage->debut            = $hour_now;
            $plage->fin              = $hour_next;
            $plage->libelle          = "automatique";
            $plage->_immediate_plage = 1;
          }
          $plage->updateFormFields();
          if ($msg = $plage->store()) {
            CAppUI::setMsg($msg, UI_MSG_ERROR);
          }
        }

        $consult->plageconsult_id = $plage->_id;
        $consult->patient_id      = $reference1->patient_id;
        $consult->heure           = $time_now;
        $consult->arrivee         = "$day_now $time_now";
        $consult->duree           = 1;
        $consult->chrono          = CConsultation::PATIENT_ARRIVE;
        $consult->type            = "entree";
        $consult->motif           = CAppUI::gconf('soins Other default_motif_observation');

        if ($msg = $consult->store()) {
          CAppUI::setMsg($msg, UI_MSG_ERROR);
        }

        return $consult;
      }
    }

    return null;
  }

  /**
   * Charge le dossier complete de la consultation pour AppFineClient
   *
   * @param string $type type
   *
   * @return CAppFineClientFolderLiaison
   */
  function loadRefFolderLiaison($type = null) {
    $folder_liaison               = new CAppFineClientFolderLiaison();
    $folder_liaison->object_id    = $this->_id;
    $folder_liaison->object_class = $this->_class;
    if ($type) {
      $folder_liaison->type = $type;
    }
    $folder_liaison->loadMatchingObject();

    return $this->_ref_appfine_client_folder = $folder_liaison;
  }

  /**
   * @inheritdoc
   */
  function getRGPDContext() {
    return $this->loadRefPatient();
  }

  /**
   * @inheritdoc
   */
  function checkTrigger($first_store = false) {
    return ($first_store || ($this->fieldModified('heure') || $this->fieldModified('patient_id') || $this->fieldModified('plageconsult_id')));
  }

  /**
   * @inheritDoc
   */
  public function getGroupID() {
    $group = $this->loadRefGroup();

    if ($group && $group->_id) {
      return $group->_id;
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  function triggerEvent() {
    $context = $this->getRGPDContext();

    if (!$context || !$context->_id) {
      throw new CMbException('CRGPDConsent-error-Unable to find context');
    }

    $manager = new CRGPDManager($this->getGroupID());
    $manager->askConsentFor($context);
  }

  /**
   * Vérifie si une consultation est considérée
   * comme terminée concernant le codage des actes
   *
   * @return bool
   */
  function isCoded() {
    $this->_coded = false;

    if ($this->sejour_id && CAppUI::gconf('dPsalleOp COperation modif_actes') == 'facturation_web100T'
      && CModule::getActive('web100T') && $this->_ref_sejour->sortie_reelle
    ) {
      $this->_coded = CWeb100TSejour::isSejourBilled($this->_ref_sejour);
    }

    return $this->_coded;
  }

  /**
   * @inheritdoc
   */
  function isExportable($prat_ids = array(), $date_min = null, $date_max = null) {
    $sejour = $this->loadRefSejour();
    if ($sejour && $sejour->_id) {
      return $sejour->isExportable($prat_ids, $date_min, $date_max);
    }

    $this->loadRefPlageConsult();

    return $this->_ref_plageconsult->isExportable($prat_ids, $date_min, $date_max);
  }

  /**
   * @return CStoredObject[]|CReservation[]
   */
  public function loadRefReservedRessources() {
    $reservation             = new CReservation();
    $reservation->date       = $this->_date;
    $reservation->heure      = (new DateTime($this->_datetime))->format('H:i:s');
    $reservation->patient_id = $this->patient_id;

    return $this->_ref_reserved_ressources = $reservation->loadMatchingList();
  }

  /**
   * @inheritDoc
   */
  function loadExternalIdentifiers($group_id = null) {
    // Iconographie de AppFine
    if (CModule::getActive("appFineClient")) {
      CAppFineClient::loadIdexConsult($this, $group_id);
    }
    // Iconographie du portail patient Doctolib
    if (CModule::getActive("doctolib")) {
      CDoctolib::loadIdex($this, $group_id);
    }
  }

  /**
   * @inheritDoc
   */
  public function matchForImport(MatcherVisitorInterface $matcher): ImportableInterface {
    return $matcher->matchConsultation($this);
  }

    /**
     * @return CItem|null
     * @throws CApiException
     */
    public function getResourcePatient(): ?CItem
    {
        $patient = $this->loadRefPatient();
        if (!$patient || !$patient->_id) {
            return null;
        }

        $item = new CItem($patient);
        $item->setName(CPatient::RESOURCE_NAME);

        return $item;
    }

    /**
     * @return CItem|null
     * @throws CApiException
     */
    public function getResourcePlageConsult(): ?CItem
    {
        $plageconsult = $this->loadRefPlageConsult();
        if (!$plageconsult || !$plageconsult->_id) {
            return null;
        }

        $item = new CItem($plageconsult);
        $item->setName(CPlageconsult::RESOURCE_NAME);

        return $item;
    }

    /**
     * @return CItem|null
     * @throws CApiException
     */
    public function getResourceCategorie(): ?CItem
    {
        $categorie = $this->loadRefCategorie();
        if (!$categorie || !$categorie->_id) {
            return null;
        }

        $item = new CItem($categorie);
        $item->setName(CConsultationCategorie::RESOURCE_NAME);

        return $item;
    }

  /**
   * @inheritDoc
   */
  public function persistForImport(PersisterVisitorInterface $persister): ImportableInterface {
    return $persister->persistObject($this);
  }

  /**
   * Get the Covid diagnosis
   *
   * @return void
   */
  function getCovidDiag() {
    $pattern = "/U07\.?[0-9]/";

    $dossier_medical = $this->_ref_patient->loadRefDossierMedical();
    if ($dossier_medical->_id) {
      foreach ($dossier_medical->_codes_cim as $_code) {
        if (preg_match($pattern, $_code)) {
          $this->_covid_diag = CCodeCIM10::get(str_replace(".", "", $_code));
        }
      }
    }

    if ($this->_covid_diag) {
      if (!preg_match("/Covid-19/", $this->_covid_diag->libelle_court)) {
        $this->_covid_diag->libelle_court = "Covid-19 : " . $this->_covid_diag->libelle_court;
      }
    }
  }

    public static function guessUfMedicaleMandatory(array $prats): void
    {
        if (!count($prats)) {
            return;
        }

        if (!CAppUI::gconf('dPcabinet CConsultation attach_consult_sejour')) {
            return;
        }

        if (!CAppUI::gconf('dPcabinet CConsultation create_consult_sejour')) {
            return;
        }

        if (CAppUI::gconf('dPplanningOp CSejour required_uf_med') === 'no') {
            return;
        }

        /** @var CMediusers $_prat */
        foreach ($prats as $_prat) {
            if ($_prat->loadRefFunction()->create_sejour_consult || in_array($_prat->activite, ['salarie', 'mixte'])) {
                $_prat->_uf_medicale_mandatory = true;
            }
        }
    }
}
