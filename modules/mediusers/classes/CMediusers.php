<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers;

use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CPerson;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\FieldSpecs\CColorSpec;
use Ox\Core\Handlers\Events\ObjectHandlerEvent;
use Ox\Core\Kernel\Routing\CRouter;
use Ox\Core\Module\CModule;
use Ox\Erp\COXCompetenceItem;
use Ox\Erp\COXMembre;
use Ox\Interop\Eai\CSpecialtyAsip;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\PasswordSpecs\PasswordSpecBuilder;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Cabinet\CAgendaPraticien;
use Ox\Mediboard\Cabinet\CBanque;
use Ox\Mediboard\Cabinet\CLieuConsult;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Facturation\CRetrocession;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Fse\CFseFactory;
use Ox\Mediboard\Hospi\CAffectationUfSecondaire;
use Ox\Mediboard\Hospi\CUniteFonctionnelle;
use Ox\Mediboard\OxPyxvital\CPyxvitalCPS;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CProgrammeClinique;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Mediboard\Personnel\CRemplacement;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CProtocole;
use Ox\Mediboard\PlanningOp\CProtocoleOperatoire;
use Ox\Mediboard\PyxVital\CPvCPS;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\Ssr\CEvenementSSR;
use Ox\Mediboard\Ssr\CIntervenantCdARR;
use Ox\Mediboard\System\CPreferences;
use Ox\Mediboard\System\CSourcePOP;
use Ox\Mediboard\System\CSourceSMTP;
use phpmailerException;

/**
 * The CMediusers class
 */
class CMediusers extends CPerson {
    /** @var string */
    public const RESOURCE_NAME = 'mediuser';

    /** @var string */
    public const RESOURCE_NAME_PRATICIEN = 'praticien';

    /** @var string */
    public const FIELDSET_PRATICIEN = 'praticien';

    /** @var string */
    public const FIELDSET_CONNEXION = 'connexion';

    /** @var string */
    public const FIELDSET_CONTACT = 'contact';

    /** @var string */
    public const FIELDSET_FUNCTION = 'function';

    /** @var string */
    public const RELATION_FUNCTION = 'function';

    /** @var string */
    public const RELATION_GROUP = 'group';

    public $user_id;

  // DB Fields
  public $remote;
  public $adeli;
  public $rpps;
  public $inami;
  public $cps;
  public $titres;
  public $initials;
  public $color;
  public $commentaires;
  public $actif;
  public $activite;
  public $deb_activite;
  public $fin_activite;
  public $compte;
  public $banque_id;
  public $mail_apicrypt;
  public $mssante_address;
  public $compta_deleguee;
  public $last_ldap_checkout;
  public $other_specialty_id;
  public $use_bris_de_glace;
  public $destinataire_favori;
  public $nom_destinataire_favori;
  public $astreinte;

  // DB References
  public $function_id;
  public $discipline_id;
  public $spec_cpam_id;
  /** @var integer Permet d'associer un utilisateur parent à un autre utilisateur existant.
   * Permet de réprésenter plusieurs situations de facturation pour un meêm utilisateur */
  public $main_user_id;

  public $code_intervenant_cdarr;

  public $secteur;
  public $pratique_tarifaire;
  public $mode_tp_acs;
  // Champs utilisés pour l'affichage des ordonnances ALD
  public $cab;
  public $conv;
  public $zisd;
  public $ik;
  public $ean;
  public $ean_base;
  public $ean_xml_factu;
  public $ofac_id;
  public $compte_ch_id;

  public $electronic_bill;
  public $specialite_tarmed;
  public $role_tarmed;
  public $place_tarmed;
  public $reminder_text;
  public $use_cdm;
  public $login_cdm;
  public $mdp_cdm;
  public $num_contrat_prive;
  public $ccam_context;

  // CUser reported fields fields
  public $_user_type;
  public $_user_username;
  public $_user_password;
  public $_user_password2;
  public $_user_first_name;
  public $_user_last_name;
  public $_user_sexe;
  public $_user_birthday;
  public $_user_email;
  public $_user_phone;
  public $_internal_phone;
  public $_user_astreinte;
  public $_user_astreinte_autre;
  public $_user_adresse;
  public $_user_cp;
  public $_user_ville;
  public $_user_last_login;
  public $_user_template;
  public $_is_robot;

    /** @var bool Does the password need to be changed? */
  public $_force_change_password;

  /** @var bool Does the user can change its own password? */
  public $_allow_change_password;

  /** @var bool Are user connections logged ? */
  public $_dont_log_connection;

  // Other fields
  public $_profile_id;
  public $_is_praticien;
  public $_is_chirurgien;
  public $_is_medecin;
  public $_is_dieteticien;
  public $_is_kine;
  public $_is_professionnel_sante;
  public $_is_dentiste;
  public $_is_secretaire;
  public $_is_anesth;
  public $_is_infirmiere;
  public $_is_aide_soignant;
  public $_is_sage_femme;
  public $_is_pharmacien;
  public $_basic_info = [];
  public $_is_urgentiste;
  public $_force_merge = false;
  public $_user_id;
  public $_keep_user;
  public $_user_type_view;
  public $_common_name;
  public $_is_connected = false;

  // Distant fields
  public $_group_id;

  public $_color; // color following this or function
  public $_font_color;

  // Behaviour fields
  static $user_autoload = true;
  public $_bind_cps;
  public $_id_cps;
  public $_uf_medicale_mandatory;

  /** @var string Form filter */
  public $_ldap_bound;

  /** @var CBanque */
  public $_ref_banque;

  /** @var CFunctions */
  public $_ref_function;

  /** @var CSecondaryFunction[] */
  public $_ref_secondary_functions = [];

  /** @var CSpecCPAM */
  public $_ref_spec_cpam;

  /** @var COXMembre */
  public $_ref_membre;

  /** @var CSpecialtyAsip */
  public $_ref_other_spec;

  /** @var CDiscipline */
  public $_ref_discipline;

  /** @var CUser */
  public $_ref_profile;

  /** @var CUser */
  public $_ref_user;

  /** @var CIntervenantCdARR */
  public $_ref_intervenant_cdarr;

  /** @var CProtocole[] */
  public $_ref_protocoles = [];
  public $_count_protocoles;

  /** @var CFunctions[] */
  public $_ref_current_functions = [];

  /** @var CPlageOp[] */
  public $_ref_plages = [];

  /** @var CPlageConge[] */
  public $_ref_plages_conge = [];

  /** @var COperation[] */
  public $_ref_urgences = [];

  /** @var COperation[] */
  public $_ref_deplacees = [];

  /** @var CSourcePOP[] */
  public $_refs_source_pop = [];

  /** @var CRetrocession[] */
  public $_ref_retrocessions = [];

  /** @var CFile */
  public $_ref_signature;

  /** @var COXCompetenceItem[] */
  public $_ref_competences = [];

  /** @var CMediusers[] */
  public $_ref_remplacant = [];

  /** @var CRemplacement */
  public $_ref_remplace;

  /** @var CMediusers A link to the ascendant user */
  public $_ref_main_user;

  /** @var CMediusers[] A link to the descendant users */
  public $_ref_secondary_users = [];

  /** @var CProgrammeClinique[] */
  public $_refs_programmes_clinique = [];

  /** @var CMediusersCompteCh[] */
  public $_ref_comptes_ch = [];

  /** @var  CMediusersCompteCh */
  public $_ref_compte_ch;

  /** @var CUniteFonctionnelle */
  public $_ref_uf_medicale;

  /** @var CUniteFonctionnelle[] */
  public $_ref_ufs_medicales = [];

  /** @var CUniteFonctionnelle[] */
  public $_ref_uf_medicale_secondaire = [];

  /** @var CAgendaPraticien[] */
  public $_ref_agendas_praticien = [];

  /** @var CLieuConsult[] */
  public $_ref_lieux_consult = [];

  /** @var CProtocoleOperatoire[] */
  public $_ref_protocoles_op = [];

  /** @var CAgendaPraticien[] */
  private $_ref_agendas_to_sync;

  /** @var CPyxvitalCPS[]|CPvCPS[] */
  public $_ref_cps;

  /** @var array Fields used to state if professional context is set */
  static $professional_context_fields = array(
    'spec_cpam_id'       => true,
    'secteur'            => true,
    'pratique_tarifaire' => false,
  );

  /**
   * Lazy access to a given user, defaultly connected user
   *
   * @param integer $user_id The user id, connected user if null
   *
   * @return self
   */
  static function get($user_id = null) {
    if ($user_id) {
      $user = new self;

      return $user->getCached($user_id);
    }

    // CAppUI::$user is available *after* CAppUI::$instance->_ref_user
    return CAppUI::$instance->_ref_user;
  }

  /**
   * @see parent::isInstalled()
   */
  function isInstalled() {
    // Prevents zillions of uncachable SQL queries on table existence
    return CModule::getInstalled("mediusers");
  }

  /**
   * @return CFunctions[]
   */
  static function loadCurrentFunctions() {
    $user                         = CMediusers::get();
    $group_id                     = CGroups::loadCurrent()->_id;
    $secondary_function           = new CSecondaryFunction();
    $ljoin                        = array();
    $where                        = array();
    $where["group_id"]            = "= '$group_id'";
    $where["user_id"]             = "= '$user->_id'";
    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = secondary_function.function_id";

    return $user->_ref_current_functions = $secondary_function->loadList($where, null, null, null, $ljoin);
  }

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec             = parent::getSpec();
    $spec->table      = 'users_mediboard';
    $spec->key        = 'user_id';
    $spec->merge_type = 'check';

    return $spec;
  }

  /**
   * @inheritDoc
   */
  public function getApiLink(): string {
    return CRouter::getInstance()
      ->generate('mediuser_mediuser', ["user_id" => $this->_id]);
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    // Note: notamment utile pour les seeks
    // Dans les faits c'est plus logique puisque la classe n'est pas autoincremented
    $props["user_id"] = "ref class|CUser seekable show|0";

    $props["remote"]                 = "bool default|1 show|0 fieldset|connexion";
    $props["adeli"]                  = "numchar length|9 confidential mask|99S9S99999S9 control|luhn fieldset|praticien";
    $props["rpps"]                   = "numchar length|11 confidential mask|99999999999 control|luhn fieldset|praticien";
    $props['inami']                  = 'numchar length|11 confidential mask|99999999999 fieldset|praticien';
    $props["cps"]                    = "str fieldset|praticien";
    $props["function_id"]            = "ref notNull class|CFunctions seekable back|users fieldset|function";
    $props["discipline_id"]          = "ref class|CDiscipline back|users";
    $props['main_user_id']           = 'ref class|CMediusers back|secondary_users';
    $props["other_specialty_id"]     = "ref class|CSpecialtyAsip autocomplete|libelle back|other_specialties";
    $props["titres"]                 = "text fieldset|praticien";
    $props["initials"]               = "str fieldset|default";
    $props["color"]                  = "color fieldset|default";
    $props["_color"]                  = "color fieldset|default";
    $props["use_bris_de_glace"]      = "bool default|0 fieldset|extra";
    $props["commentaires"]           = "text fieldset|extra";
    $props["actif"]                  = "bool default|1 fieldset|default";
    $props["activite"]               = "enum list|liberale|salarie|mixte default|liberale fieldset|praticien";
    $props["deb_activite"]           = "date fieldset|default";
    $props["fin_activite"]           = "date fieldset|default";
    $props["spec_cpam_id"]           = "num fieldset|praticien";
    $props["compte"]                 = "code rib confidential mask|99999S99999S***********S99 show|0 fieldset|praticien";
    $props["banque_id"]              = "ref class|CBanque show|0 back|users";
    $props["code_intervenant_cdarr"] = "str length|2 fieldset|praticien";
    $props["secteur"]                = "enum list|1|1dp|2|nc fieldset|praticien";
    $props['pratique_tarifaire']     = "enum list|none|optam|optamco fieldset|praticien";
    $props['mode_tp_acs']            = 'enum list|tp_coordonne|amc_standard fieldset|praticien';
    $props["cab"]                    = "str fieldset|praticien";
    $props["conv"]                   = "str fieldset|praticien";
    $props["zisd"]                   = "str fieldset|praticien";
    $props["ik"]                     = "str fieldset|praticien";
    $props["ean"]                    = "str fieldset|praticien";
    $props["ean_base"]               = "str fieldset|praticien";
    $props["ean_xml_factu"]          = "str fieldset|praticien";
    $props['ofac_id']                = 'str fieldset|praticien';
    $props["compte_ch_id"]           = "ref class|CMediusersCompteCh autocomplete|name dependsOn|user_id back|comptech_mediusers";
    $props["astreinte"]              = "bool default|0 fieldset|extra";

    $props["electronic_bill"]         = "bool default|0 fieldset|praticien";
    $props["specialite_tarmed"]       = "numchar length|4 fieldset|praticien";
    $props["role_tarmed"]             = "str fieldset|praticien";
    $props["place_tarmed"]            = "str fieldset|praticien";
    $props["reminder_text"]           = "text";
    $props["use_cdm"]                 = "bool default|0 fieldset|extra";
    $props["login_cdm"]               = "str maxLength|20 fieldset|extra";
    $props["mdp_cdm"]                 = "str maxLength|64 show|0 fieldset|extra";
    $props["num_contrat_prive"]       = "str maxLength|64 fieldset|praticien";
    $props["mail_apicrypt"]           = "email fieldset|praticien";
    $props['mssante_address']         = 'email fieldset|praticien';
    $props["compta_deleguee"]         = "enum list|0|1|with_prat default|0 fieldset|praticien";
    $props["last_ldap_checkout"]      = "date fieldset|connexion";
    $props['ccam_context']            = "num min|0 max|52 fieldset|praticien";
    $props["destinataire_favori"]     = "email fieldset|extra";
    $props["nom_destinataire_favori"] = "str fieldset|extra";

    $props["_group_id"] = "ref notNull class|CGroups fieldset|praticien";

    $props["_user_username"]        = "str notNull minLength|3 reported fieldset|connexion";
    $props["_user_password2"]       = "password sameAs|_user_password reported";
    $props["_user_first_name"]      = "str reported show|1 fieldset|default";
    $props["_user_last_name"]       = "str notNull confidential reported show|1 fieldset|default";
    $props["_user_sexe"]            = "enum list|u|f|m default|u reported show|1 fieldset|default";
    $props["_user_birthday"]        = "birthDate reported fieldset|extra";
    $props["_user_email"]           = "str confidential reported fieldset|contact";
    $props["_user_phone"]           = "phone confidential reported fieldset|contact";
    $props["_internal_phone"]       = "str confidential reported fieldset|contact";
    $props["_user_astreinte"]       = "str confidential reported fieldset|extra";
    $props["_user_astreinte_autre"] = "str confidential reported fieldset|extra";
    $props["_user_adresse"]         = "str confidential reported fieldset|contact";
    $props["_user_last_login"]      = "dateTime reported";
    [$min_cp, $max_cp] = (CModule::getActive('dPpatients')) ? CPatient::getLimitCharCP() : [4, 5];
    $props["_user_cp"]               = "str minLength|$min_cp maxLength|$max_cp confidential reported fieldset|contact";
    $props["_user_ville"]            = "str confidential reported fieldset|contact";
    $props["_profile_id"]            = "ref reported class|CUser";
    $props["_user_type"]             = "num notNull min|0 max|25 reported";
    $props["_user_type_view"]        = "str";
    $props["_force_change_password"] = "bool default|0";
    $props["_allow_change_password"] = "bool default|1";
    $props["_dont_log_connection"]   = "bool default|0";
    $props["_is_robot"]              = "bool default|0";

    $props["_user_password"] = $this->getPasswordSpecBuilder()->build()->getProp() . ' reported';

    $props['_ldap_bound'] = 'set list|1|0';

    return $props;
  }

    /**
     * Update the object's specs
     *
     * @throws Exception
     */
    public function updateSpecs(): void
    {
        $spec                                     = $this->getPasswordSpecBuilder()->build();
        $this->_props['_user_password']           = $spec->getProp() . ' reported';
        $this->_specs['_user_password']           = $spec->getSpec('_user_password');
        $this->_specs['_user_password']->reported = true;
    }

    /**
     * @return PasswordSpecBuilder
     * @throws Exception
     */
    public function getPasswordSpecBuilder(): PasswordSpecBuilder
    {
        return new PasswordSpecBuilder($this);
    }

  /**
   * Création d'un utilisateur
   *
   * @return CUser
   */
  function createUser() {
    $user                = new CUser();
    $user->user_id       = ($this->_user_id) ? $this->_user_id : $this->user_id;
    $user->user_type     = $this->_user_type;
    $user->user_username = $this->_user_username;

    if (isset($this->_ldap_store)) {
      $user->user_password = $this->_user_password;
    }
    else {
      $user->_user_password = $this->_user_password;
    }

    $user->user_first_name       = $this->_user_first_name;
    $user->user_last_name        = $this->_user_last_name;
    $user->user_sexe             = $this->_user_sexe;
    $user->user_birthday         = $this->_user_birthday;
    $user->user_email            = $this->_user_email;
    $user->user_phone            = $this->_user_phone;
    $user->internal_phone        = $this->_internal_phone;
    $user->user_astreinte        = $this->_user_astreinte;
    $user->user_astreinte_autre  = $this->_user_astreinte_autre;
    $user->user_address1         = $this->_user_adresse;
    $user->user_zip              = $this->_user_cp;
    $user->user_city             = $this->_user_ville;
    $user->profile_id            = $this->_profile_id;
    $user->force_change_password = $this->_force_change_password;
    $user->allow_change_password = $this->_allow_change_password;
    $user->dont_log_connection   = $this->_dont_log_connection;
    $user->template              = 0;
    $user->is_robot              = $this->_is_robot;

    $user->_merging = $this->_merging;

    return $user;
  }

  /**
   * @see parent::delete()
   */
  function delete() {
    $msg = null;

    if (!isset($this->_keep_user)) {
      // Delete corresponding dP user first
      if (!$msg = $this->canDeleteEx()) {
        $user = $this->createUser();
        if ($msg = $user->delete()) {
          return $msg;
        }
      }
    }

    $this->_keep_user = null;

    return parent::delete();
  }

  /**
   * @see parent::merge()
   */
  function merge($objects = array/*<CMbObject>*/
  (), $fast = false
  ) {
    if ($this->_force_merge) {
      $codages = $this->loadBackRefs('codage_ccam');
      $codables = self::massLoadFwdRef($codages, 'codable_id');
      /** @var CMediusers $_object */
      foreach ($objects as $_object) {
        /* Prevent the creation of duplicates CCodageCCAM when the back references are merged */
        $_codages = $_object->loadBackRefs('codage_ccam');
        /** @var CCodageCCAM $_codage */
        foreach ($_codages as $_codage) {
          if (array_key_exists("{$_codage->codable_class}-{$_codage->codable_id}", $codables)) {
            $_codage->delete();
          }
        }
      }

      return parent::merge($objects, $fast);
    }

    return CAppUI::tr("CMediusers-merge-impossible");
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->loadRefUser();

    $this->updateColor();
  }

  /**
   * @see parent::loadView()
   */
  function loadView() {
    parent::loadView();
    $this->isPraticien();
    $this->loadRefFunction();
    $this->loadRefSpecCPAM();
    $this->loadRefDiscipline();
    $this->loadNamedFile("identite.jpg");
  }

  /**
   * @see parent::loadQueryList()
   */
  function loadQueryList($query, ?int $limit_time = null) {
    /** @var self[] $mediusers */
    CMediusers::$user_autoload = false;
    $mediusers                 = parent::loadQueryList($query, $limit_time);
    CMediusers::$user_autoload = true;

    if (!count($mediusers)) {
      return array();
    }

    // Mass user speficic preloading
    $user = new CUser();
    $user->loadAll(array_keys($mediusers));

    // Attach cached user
    foreach ($mediusers as $_mediuser) {
      $_mediuser->updateFormFields();
    }

    return $mediusers;
  }

  /**
   * Chargement de l'utilisateur système
   *
   * @return CUser
   */
  function loadRefUser() {
    $user = new CUser();

    // Usefull hack for mass preloading
    if (self::$user_autoload) {
      $user = $user->getCached($this->user_id);
    }

    if ($user->_id) {
      $this->_user_type             = $user->user_type;
      $this->_user_username         = $user->user_username;
      $this->_user_password         = $user->user_password;
      $this->_user_first_name       = CMbString::capitalize($user->user_first_name);
      $this->_user_last_name        = CMbString::upper($user->user_last_name);
      $this->_user_sexe             = $user->user_sexe;
      $this->_user_birthday         = $user->user_birthday;
      $this->_user_email            = $user->user_email;
      $this->_user_phone            = $user->user_phone;
      $this->_internal_phone        = $user->internal_phone;
      $this->_user_astreinte        = $user->user_astreinte;
      $this->_user_astreinte_autre  = $user->user_astreinte_autre;
      $this->_user_adresse          = $user->user_address1;
      $this->_user_cp               = $user->user_zip;
      $this->_user_ville            = $user->user_city;
      $this->_user_template         = $user->template;
      $this->_profile_id            = $user->profile_id;
      $this->_force_change_password = $user->force_change_password;
      $this->_allow_change_password = $user->allow_change_password;

      // Encrypt this datas
      $this->checkConfidential();
      $this->_view      = "$this->_user_last_name $this->_user_first_name";
      $this->_shortview = "";

      // Initiales
      if (!$this->_shortview = $this->initials) {
        $separator        = strpos($this->_user_first_name, " ") !== false ? " " : "-";
        $this->_shortview .= CMbString::makeInitials($this->_user_first_name, $separator);
        $this->_shortview .= CMbString::makeInitials($this->_user_last_name);
      }

      $this->_user_type_view = CValue::read(CUser::$types, $this->_user_type);
    }

    $this->_ref_user = $user;

    $this->mapPerson();
    $this->updateSpecs();

    return $this->_ref_user;
  }

  /**
   * @return CBanque
   */
  function loadRefBanque() {
    return $this->_ref_banque = $this->loadFwdRef("banque_id", true);
  }

  /**
   * Chargement du profil associé
   *
   * @return CUser
   */
  function loadRefProfile() {
    return $this->_ref_profile = $this->loadFwdRef("_profile_id", true);
  }

  /**
   * Chargement de la fonction principale
   *
   * @return CFunctions
   */
  function loadRefFunction() {
    /** @var CFunctions $function */
    $function            = $this->loadFwdRef("function_id", true);
    $this->_group_id     = $function ? $function->group_id : null;
    $this->_ref_function = $function;
    $this->updateColor();

    return $this->_ref_function;
  }

  /**
   * Retourne la liste des fonctions secondaires dde l'utilisateur
   *
   * @param int $group_id Group de la fonction secondaire
   *
   * @return CFunctions[]
   */
  function loadRefsSecondaryFunctions($group_id = null) {
    $this->_ref_secondary_functions = $this->loadBackRefs("secondary_functions") ?? [];
    $secondary_functions            = array();
    foreach ($this->_ref_secondary_functions as $_sec_func) {
      /** @var CSecondaryFunction $_sec_func */
      $_sec_func->loadRefFunction();
      $_sec_func->loadRefUser();
      $_function = $_sec_func->_ref_function;
      if (!$group_id || $_function->group_id == $group_id) {
        $secondary_functions[$_function->_id] = $_function;
      }
    }

    return $secondary_functions;
  }

  function loadRefsSecondaryFunctionsByGroup() {
    $secondary_functions = [];
    foreach ($this->loadRefsSecondaryFunctions() as $_sec_func) {
      if (!isset($secondary_functions[$_sec_func->group_id])) {
        $secondary_functions[$_sec_func->group_id] = [];
      }

      $secondary_functions[$_sec_func->group_id][] = $_sec_func;
    }

    return $secondary_functions;
  }

  /**
   * Utilisation de la couleur de l'utilisateur si définie
   * sinon de la fonction
   *
   * @return string User color
   */
  function updateColor() {
    $function_color    = $this->_ref_function ? $this->_ref_function->color : null;
    $this->_color      = $this->color ? $this->color : $function_color;
    $this->_font_color = CColorSpec::get_text_color($this->_color) > 130 ? "000000" : "ffffff";

    return $this->_color;
  }

  /**
   * Chargement de la discipline médicale
   *
   * @return CDiscipline
   */
  function loadRefDiscipline() {
    return $this->_ref_discipline = $this->loadFwdRef("discipline_id", true);
  }

  /**
   * Chargement de la spécialité CPAM
   *
   * @return CSpecCPAM
   */
  function loadRefSpecCPAM() {
    return $this->_ref_spec_cpam = CSpecCPAM::get($this->spec_cpam_id);
  }

  /**
   * Chargement de l'aute spécialité
   *
   * @return CSpecialtyAsip
   */
  function loadRefOtherSpec() {
    return $this->_ref_other_spec = $this->loadFwdRef("other_specialty_id", true);
  }

  /**
   * @return CIntervenantCdARR
   */
  function loadRefIntervenantCdARR() {
    return $this->_ref_intervenant_cdarr = CIntervenantCdARR::get($this->code_intervenant_cdarr);
  }

  /**
   * @see parent::loadRefsFwd()
   * @deprecated
   */
  function loadRefsFwd() {
    $this->loadRefFunction();
    $this->loadRefSpecCPAM();
    $this->loadRefDiscipline();
  }

  /**
   * @see parent::getPerm()
   */
  function getPerm($permType) {
    if ($this->user_id == CAppUI::$user->_id) {
      return true;
    }
    $this->loadRefFunction();
    if ($perm = CPermObject::getPermObject($this, $permType, $this->_ref_function)) {
      return $perm;
    }

    $this->loadBackRefs("secondary_functions");
    foreach ($this->_back["secondary_functions"] as $_link) {
      /** @var  CSecondaryFunction $_link */
      $fonction = $_link->loadRefFunction();
      $fonction->load($_link->function_id);
      if ($perm = $perm || CPermObject::getPermObject($this, $permType, $fonction)) {
        return $perm;
      }
    }

    return $perm;
  }

  /**
   * Chargement de la liste des protocoles de DHE de l'utilisateur
   *
   * @param string $type type du séjour
   *
   * @return CProtocole[]
   */
  function loadProtocoles($type = null) {
    $this->loadRefFunction();
    $functions = array($this->function_id);
    $this->loadBackRefs("secondary_functions");
    foreach ($this->_back["secondary_functions"] as $curr_sec_func) {
      $functions[] = $curr_sec_func->function_id;
    }
    $list_functions = implode(",", $functions);
    $where          = array(
      "protocole.chir_id = '$this->_id' OR protocole.function_id IN ($list_functions)",
    );

    if ($type) {
      $where["type"] = "= '$type'";
    }

    $protocole             = new CProtocole();
    $this->_ref_protocoles = $protocole->loadList($where, "libelle_sejour, libelle, codes_ccam");
  }

  function countProtocoles($type = null, $only_interv = false, $actif = null) {
    $this->loadRefFunction();
    //Limite de la recherche des protocoles de DHE à l'établissement courant
    [$ljoinSecondary, $whereSecondary, $functions] = CProtocole::checkMultiEtab($this->_ref_function);
    $this->loadBackRefs("secondary_functions", null, null, null, $ljoinSecondary, null, "", $whereSecondary);
    foreach ($this->_back["secondary_functions"] as $curr_sec_func) {
      $functions[] = $curr_sec_func->function_id;
    }
    $list_functions = implode(",", $functions);
    $where          = array(
      "protocole.chir_id = '$this->_id' OR protocole.function_id IN ($list_functions)",
    );

    if ($type) {
      $where["type"] = "= '$type'";
    }

    if ($actif) {
      $where["actif"] = "= '$actif'";
    }

    if ($only_interv) {
      $where["for_sejour"] = "= '0'";
    }

    $protocole               = new CProtocole();
    $this->_count_protocoles = $protocole->countList($where);
  }

  /**
   * Tableau comprenant l'utilisateur et son organigramme
   *
   * @return CMbObject[]
   */
  function getOwners() {
    $func = $this->loadRefFunction();
    $etab = $func->loadRefGroup();

    return [
      "prat"     => $this,
      "func"     => $func,
      "etab"     => $etab,
      "instance" => CCompteRendu::getInstanceObject()
    ];
  }

  /**
   * @see parent::check()
   */
  function check() {
    // TODO: voir a fusionner cette fonction avec celle de admin.php qui est exactement la meme
    // Chargement des specs des attributs du mediuser
    $this->updateSpecs();

    $specs = $this->getSpecs();

    // On se concentre dur le mot de passe (_user_password)
    $pwdSpecs = $specs['_user_password'];

    $pwd = $this->_user_password;

    // S'il a été défini, on le contréle (necessaire de le mettre ici a cause du md5)
    if ($pwd) {

      // minLength
      if ($pwdSpecs->minLength > strlen($pwd)) {
        return "Mot de passe trop court (minimum {$pwdSpecs->minLength})";
      }

      // notContaining
      if ($target = $pwdSpecs->notContaining) {
        if ($field = $this->$target) {
          if (stristr($pwd, $field)) {
            return "Le mot de passe ne doit pas contenir '$field'";
          }
        }
      }

      // notNear
      if ($target = $pwdSpecs->notNear) {
        if ($field = $this->$target) {
          if (levenshtein($pwd, $field) < 3) {
            return "Le mot de passe ressemble trop à '$field'";
          }
        }
      }

      // alphaAndNum
      if ($pwdSpecs->alphaAndNum) {
        if (!preg_match("/[A-z]/", $pwd) || !preg_match("/\d+/", $pwd)) {
          return 'Le mot de passe doit contenir au moins un chiffre ET une lettre';
        }
      }

      // alphaLowChars
      if ($pwdSpecs->alphaLowChars && (!preg_match('/[a-z]/', $pwd))) {
        return 'Le mot de passe doit contenir au moins une lettre bas-de-casse (sans disacritique)';
      }

      // alphaUpChars
      if ($pwdSpecs->alphaUpChars && (!preg_match('/[A-Z]/', $pwd))) {
        return 'Le mot de passe doit contenir au moins une lettre en capitale d\'imprimerie (sans accent)';
      }

      // alphaChars
      if ($pwdSpecs->alphaChars && (!preg_match('/[A-z]/', $pwd))) {
        return 'Le mot de passe doit contenir au moins une lettre (sans accent)';
      }

      // numChars
      if ($pwdSpecs->numChars && (!preg_match('/\d/', $pwd))) {
        return 'Le mot de passe doit contenir au moins un chiffre';
      }

      // specialChars
      if ($pwdSpecs->specialChars && (!preg_match('/[!-\/:-@\[-`\{-~]/', $pwd))) {
        return 'Le mot de passe doit contenir au moins un caractère spécial';
      }
    }
    else {
      $this->_user_password = null;
    }

    return parent::check();
  }

  /**
   * @todo Use CStoredObject->store()
   */
  function store() {
    // Properties checking
    $this->updatePlainFields();

    $this->loadOldObject();

    if (CApp::isReadonly()) {
      return CAppUI::tr($this->_class) .
        CAppUI::tr("CMbObject-msg-store-failed") .
        CAppUI::tr("Mode-readonly-msg");
    }

    if ($msg = $this->check()) {
      return CAppUI::tr($this->_class) .
        CAppUI::tr("CMbObject-msg-check-failed") .
        CAppUI::tr($msg);
    }

    // Trigger before event
    $this->notify(ObjectHandlerEvent::BEFORE_STORE());

    $spec = $this->_spec;

    if ($this->fieldModified("remote", 0) && !CAppUI::$user->isAdmin()) {
      if (!$this->_user_password) {
        return "Veuillez saisir à nouveau votre mot de passe";
      }
    }

    /*
    if (!CAppUI::$user->isAdmin()) {
      if ($this->fieldModified("_user_type", 1) || (!$this->_id && $this->_user_type)) {
        return "Opération interdite";
      }
    }
    */

    /// <diff>
    // Store corresponding core user first
    $user = $this->createUser();
    if ($msg = $user->store()) {
      return $msg;
    }

    // User might have been re-created
    if ($this->user_id != $user->user_id) {
      $this->user_id = null;
    }

    // Can't use parent::store cuz user_id don't auto-increment
    if ($this->user_id) {
      $vars = $this->getPlainFields();
      $ret  = $spec->ds->updateObject($spec->table, $vars, $spec->key, $spec->nullifyEmptyStrings);
    }
    else {
      $this->user_id = $user->user_id;
      $vars          = $this->getPlainFields();
      $keyToUpdate   = $spec->incremented ? $spec->key : null;
      $ret           = $spec->ds->insertObject($spec->table, $this, $vars, $keyToUpdate);
    }
    /// </diff>

    if (!$ret) {
      return CAppUI::tr($this->_class) .
        CAppUI::tr("CMbObject-msg-store-failed") .
        $spec->ds->error();
    }

    /// <diff>
    // Bind CPS
    if ($this->_bind_cps && $this->_id && CModule::getActive("fse")) {
      $cps = CFseFactory::createCPS();
      if ($cps) {
        if ($msg = $cps->bindCPS($this)) {
          return $msg;
        }
      }
    }
    /// </diff>

    // Préparation du log, doit être fait AVANT $this->load()
    if (CAppUI::conf("activer_user_action")) {
      $this->prepareUserAction();
    }
    else {
      $this->prepareLog();
    }

    // Load the object to get all properties
    //$this->load(); // peut poser probleme, à tester

    // Enregistrement du log une fois le store terminé
    if (CAppUI::conf("activer_user_action")) {
      $this->doUserAction();
    }
    else {
      $this->doLog();
    }

    // Trigger event
    $this->notify(ObjectHandlerEvent::AFTER_STORE());

    $this->_old = null;

    return null;
  }

  function delFunctionPermission() {
    $where                 = array();
    $where["user_id"]      = "= '$this->user_id'";
    $where["object_class"] = "= 'CFunctions'";
    $where["object_id"]    = "= '$this->function_id'";

    $perm = new CPermObject();
    if ($perm->loadObject($where)) {
      $perm->delete();
    }
  }

  function delGroupPermission()
  {
    $function = CFunctions::findOrNew($this->function_id);
    $where                 = array();
    $where["user_id"]      = "= '$this->user_id'";
    $where["object_class"] = "= 'CGroups'";
    $where["object_id"]    = "= '$function->group_id'";

    $perm = new CPermObject();
    if ($perm->loadObject($where)) {
      $perm->delete();
    }
  }

  /**
   * Ajout de la permission sur sa fonction à un utilisateur
   *
   * @return void
   */
  function insFunctionPermission() {
    $where                 = array();
    $where["user_id"]      = "= '$this->user_id'";
    $where["object_class"] = "= 'CFunctions'";
    $where["object_id"]    = "= '$this->function_id'";

    $perm = new CPermObject;
    if (!$perm->loadObject($where)) {
      $perm               = new CPermObject;
      $perm->user_id      = $this->user_id;
      $perm->object_class = "CFunctions";
      $perm->object_id    = $this->function_id;
      $perm->permission   = PERM_EDIT;
      $perm->store();
    }
  }

  /**
   * Ajout de la permission sur son établissement à un utilisateur
   *
   * @return void
   */
  function insGroupPermission() {
    $function = new CFunctions;
    $function->load($this->function_id);
    $where                 = array();
    $where["user_id"]      = "= '$this->user_id'";
    $where["object_class"] = "= 'CGroups'";
    $where["object_id"]    = "= '$function->group_id'";

    $perm = new CPermObject;
    if (!$perm->loadObject($where)) {
      $perm               = new CPermObject;
      $perm->user_id      = $this->user_id;
      $perm->object_class = "CGroups";
      $perm->object_id    = $function->group_id;
      $perm->permission   = PERM_EDIT;
      $perm->store();
    }
  }

  /**
   * Chargement de la liste des utilisateurs à partir de leur type
   *
   * @param array  $user_types  Tableau des types d'utilisateur
   * @param int    $permType    Niveau de permission
   * @param int    $function_id Filtre sur une fonction spécifique
   * @param string $name        Filtre sur un nom d'utilisateur
   * @param bool   $actif       Filtre sur les utilisateurs actifs
   * @param bool   $secondary   Inclut les fonctions secondaires dans le filtre sur les fonctions
   * @param bool   $reverse     Utilise les types en inclusion ou en exclusion
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   *
   * @return CMediusers[]
   */
  function loadListFromType(
    $user_types = null, $permType = PERM_READ, $function_id = null,
    $name = null, $actif = true, $secondary = false, $reverse = false, $use_group = true, $group_id = null
  ) {
    $where = array();
    $ljoin = array();

    if ($actif) {
      $where["users_mediboard.actif"] = "= '1'";
      $where[]                        = "users_mediboard.fin_activite IS NULL OR users_mediboard.fin_activite > '" . CMbDT::date() . "'";
    }

    // Filters on users values
    $ljoin["users"] = "`users`.`user_id` = `users_mediboard`.`user_id`";

    if ($name) {
      $where["users.user_last_name"] = "LIKE '$name%' OR users.user_first_name LIKE '$name%'";
    }

    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";
    $ljoin["secondary_function"]  = "secondary_function.user_id = users_mediboard.user_id";
    $ljoin[]                      = "functions_mediboard AS sec_fnc_mb ON sec_fnc_mb.function_id = secondary_function.function_id";

    if ($function_id) {
      if ($secondary) {
        $where[] = "'$function_id' IN (users_mediboard.function_id, secondary_function.function_id)";
      }
      else {
        $where["users_mediboard.function_id"] = "= '$function_id'";
      }
    }

    if ($use_group) {
      // Filter on current group or users in secondaries functions
      if (!$group_id) {
        $group_id = CGroups::loadCurrent()->_id;
      }
      $where[] = "functions_mediboard.group_id = '$group_id' OR sec_fnc_mb.group_id = '$group_id'";
    }

    // Filter on user type
    if (is_array($user_types)) {
      $utypes_flip = array_flip(CUser::$types);
      foreach ($user_types as &$_type) {
        $_type = $utypes_flip[$_type];
      }

      $where["users.user_type"] = $reverse ?
        CSQLDataSource::prepareNotIn($user_types) :
        CSQLDataSource::prepareIn($user_types);
    }

    $order    = "`users`.`user_last_name`, `users`.`user_first_name`";
    $group_by = array("user_id");

    // Get all users
    $mediuser = new CMediusers();
    /** @var CMediusers[] $mediusers */
    $mediusers = $mediuser->loadList($where, $order, null, $group_by, $ljoin);

    // Mass fonction standard preloading
    self::massLoadFwdRef($mediusers, "function_id");
    self::massLoadBackRefs($mediusers, "secondary_users");
    self::massCountBackRefs($mediusers, "secondary_functions");

    // Filter a posteriori to unable mass preloading of function
    self::filterByPerm($mediusers, $permType);

    // Associate cached function
    foreach ($mediusers as $_mediuser) {
      $_mediuser->loadRefFunction();
      $_mediuser->loadRefsSecondaryUsers();
    }

    return $mediusers;
  }

  /**
   * Liste de Tous les établissements
   *
   * @param int $permType Type de permission à valider
   *
   * @return CGroups[]
   */
  static function loadEtablissements($permType = PERM_READ) {
    return CGroups::loadGroups($permType);
  }

  /**
   * Load list overlay for current group
   *
   * @param array  $where   list of SQL WHERE statements
   * @param array  $order   list of SQL ORDER statement
   * @param string $limit   SQL limit statement
   * @param string $groupby SQL GROUP BY statement
   * @param array  $ljoin   list of SQL LEFT JOIN statements
   *
   * @return self[]
   */
  function loadGroupList($where = array(), $order = null, $limit = null, $groupby = null, $ljoin = array()) {
    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";
    // Filtre sur l'établissement
    $group                                 = CGroups::loadCurrent();
    $where["functions_mediboard.group_id"] = "= '$group->_id'";

    return $this->loadList($where, $order, $limit, $groupby, $ljoin);
  }

  /**
   * Get the ids of Mediusers from the current group
   *
   * @param array  $where   Where SQL clause to add
   * @param string $order   Result order
   * @param int    $limit   Limit SQL
   * @param string $groupby Group by
   * @param array  $ljoin   Left join SQL
   *
   * @return integer[]
   */
  function getGroupIds($where = array(), $order = null, $limit = null, $groupby = null, $ljoin = array()) {
    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";
    // Filtre sur l'établissement
    $group                                 = CGroups::loadCurrent();
    $where["functions_mediboard.group_id"] = "= '$group->_id'";

    return $this->loadIds($where, $order, $limit, $groupby, $ljoin);
  }

  /**
   * Load functions with permissions for given group, current group by default
   *
   * @param int    $permType Level of permission
   * @param int    $group_id Filter on group
   * @param string $type     Type of function
   * @param string $name     Name of the group
   * @param array  $where    Optionnals parameters
   * @param array  $ljoin    Optionnals left join parameters
   *
   * @return CFunctions[] Found functions
   */
  static function loadFonctions(
    $permType = PERM_READ, $group_id = null, $type = null, $name = "", $where = array(), $ljoin = array()
  ) {
    $group = CGroups::loadCurrent();

    $function = new CFunctions();

    $where["functions_mediboard.actif"] = "= '1'";
    $where["group_id"]                  = "= '" . CValue::first($group_id, $group->_id) . "'";

    if ($type) {
      $where["type"] = "= '$type'";
    }

    if ($name) {
      $where["text"] = "LIKE '$name%'";
    }

    $order = "text";

    /** @var CFunctions[] $functions */
    if (!$functions = $function->loadList($where, $order, null, null, $ljoin)) {
      return null;
    }

    CMbObject::filterByPerm($functions, $permType);

    // Group association
    foreach ($functions as $function) {
      $function->_ref_group = $group;
    }

    return $functions;
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadUsers($permType = PERM_READ, $function_id = null, $name = null, $actif = true, $use_group = true, $group_id = null) {
    return $this->loadListFromType(null, $permType, $function_id, $name, $actif, false, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadMedecins($permType = PERM_READ, $function_id = null, $name = null, $actif = true, $use_group = true, $group_id = null) {
    return $this->loadListFromType(array("Médecin"), $permType, $function_id, $name, $actif, false, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadChirurgiens($permType = PERM_READ, $function_id = null, $name = null, $actif = true, $use_group = true, $group_id = null) {
    $types = array("Chirurgien", "Dentiste");

    return $this->loadListFromType($types, $permType, $function_id, $name, $actif, false, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadAnesthesistes($permType = PERM_READ, $function_id = null, $name = null, $actif = true, $use_group = true, $group_id = null) {
    return $this->loadListFromType(array("Anesthésiste"), $permType, $function_id, $name, $actif, false, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $secondary   secondary
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadProfessionnelDeSanteByPref($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true, $group_id = null) {
    $list = array();
    if (CAppUI::pref("take_consult_for_chirurgien")) {
      $list[] = "Chirurgien";
    }
    if (CAppUI::pref("take_consult_for_anesthesiste")) {
      $list[] = "Anesthésiste";
    }
    if (CAppUI::pref("take_consult_for_medecin")) {
      $list[] = "Médecin";
    }
    if (CAppUI::pref("take_consult_for_dentiste")) {
      $list[] = "Dentiste";
    }
    if (CAppUI::pref("take_consult_for_infirmiere")) {
      $list[] = "Infirmière";
    }
    if (CAppUI::pref("take_consult_for_reeducateur")) {
      $list[] = "Rééducateur";
    }
    if (CAppUI::pref("take_consult_for_sage_femme")) {
      $list[] = "Sage Femme";
    }
    if (CAppUI::pref("take_consult_for_dieteticien")) {
      $list[] = "Diététicien";
    }
    if (CAppUI::pref("take_consult_for_assistante_sociale")) {
      $list[] = "Assistante sociale";
    }

    return $this->loadListFromType($list, $permType, $function_id, $name, $actif, $secondary, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $secondary   secondary
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadPraticiens($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true, $group_id = null) {
    $types = array("Chirurgien", "Anesthésiste", "Médecin", "Dentiste");

    return $this->loadListFromType($types, $permType, $function_id, $name, $actif, $secondary, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $secondary   secondary
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadProfessionnelDeSante($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true, $group_id = null) {
    $types = array("Chirurgien", "Anesthésiste", "Médecin", "Infirmière", "Rééducateur", "Sage Femme", "Dentiste", "Diététicien");

    return $this->loadListFromType($types, $permType, $function_id, $name, $actif, $secondary, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $secondary   secondary
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadExecutantsCCAM($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true, $group_id = null) {
    $types = array("Chirurgien", "Anesthésiste", "Médecin", "Sage Femme", "Dentiste");

    return $this->loadListFromType($types, $permType, $function_id, $name, $actif, $secondary, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $secondary   secondary
   * @param bool   $actif       actif
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadNonProfessionnelDeSante($permType = PERM_READ, $function_id = null, $name = null, $secondary = false, $actif = true, $use_group = true, $group_id = null) {
    $types = array("Chirurgien", "Anesthésiste", "Médecin", "Infirmière", "Rééducateur", "Sage Femme", "Dentiste", "Diététicien");

    return $this->loadListFromType($types, $permType, $function_id, $name, $secondary, $actif, true, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadPersonnels($permType = PERM_READ, $function_id = null, $name = null, $use_group = true, $group_id = null) {
    return $this->loadListFromType(array("Personnel"), $permType, $function_id, $name, true, false, false, $use_group, $group_id);
  }

  /**
   * @param int    $permType    permission
   * @param int    $function_id fontion
   * @param string $name        nom
   * @param bool   $use_group   Restreint la recherche à l'établissement courant
   * @param bool   $group_id    Etablissement alternatif
   *
   * @return CMediusers[]
   */
  function loadKines($permType = PERM_READ, $function_id = null, $name = null, $use_group = true, $group_id = null) {
    return $this->loadListFromType(array("Rééducateur"), $permType, $function_id, $name, true, false, false, $use_group, $group_id);
  }

  function isFromType($user_types) {
    // Warning: !== operator
    return array_search(@CUser::$types[$this->_user_type], $user_types) !== false;
  }

  /**
   * Check whether user is a pratician
   *
   * @return bool
   */
  function isPraticien() {
    return $this->_is_praticien = $this->isFromType(array("Médecin", "Chirurgien", "Anesthésiste", "Dentiste"));
  }

  /**
   * Check whether user is a pratician
   *
   * @return bool
   */
  function isExecutantCCAM() {
    return $this->_is_praticien = $this->isFromType(array("Médecin", "Chirurgien", "Anesthésiste", "Dentiste", 'Sage Femme'));
  }

  /**
   * Check whether user is a medical professionnal
   *
   * @return bool
   */
  function isProfessionnelDeSante() {
    return $this->_is_professionnel_sante = $this->isFromType(
      array("Chirurgien", "Anesthésiste", "Médecin", "Infirmière", "Rééducateur", "Sage Femme", "Dentiste", "Diététicien")
    );
  }

  /**
   * Check whether user is a surgeon
   *
   * @return bool
   */
  function isChirurgien() {
    return $this->_is_chirurgien = $this->isFromType(array("Chirurgien"));
  }

  /**
   * Check whether user is a doctor
   *
   * @return bool
   */
  function isMedecin() {
    return $this->_is_medecin = $this->isFromType(array("Médecin"));
  }

  /**
   * Check whether user is an anesthesist
   *
   * @return bool
   */
  function isAnesth() {
    return $this->_is_anesth = $this->isFromType(array("Anesthésiste"));
  }

  /**
   * Check whether user is an pharmacist
   *
   * @return bool
   */
  function isPharmacien() {
    return $this->_is_pharmacien = $this->isFromType(array("Pharmacien"));
  }

  /**
   * Check whether user is a dentist
   *
   * @return bool
   */
  function isDentiste() {
    return $this->_is_dentiste = $this->isFromType(array("Dentiste"));
  }

  /**
   * Check whether user is a nurse
   *
   * @return bool
   */
  function isInfirmiere() {
    return $this->_is_infirmiere = $this->isFromType(array("Infirmière"));
  }

  /**
   * Check whether user is an IADE
   *
   * @return bool
   */
  function isIADE() {
    return $this->isFromType(array("IADE"));
  }

  function isPersonnel() {
    return $this->isFromType(["Personnel"]);
  }

  /**
   * @return bool
   */
  function isAideSoignant() {
    return $this->_is_aide_soignant = $this->isFromType(array("Aide soignant"));
  }

  /**
   * @return bool
   */
  function isSageFemme() {
    return $this->_is_sage_femme = $this->isFromType(array("Sage Femme"));
  }

  /**
   * Check whether user is a secretary
   *
   * @return bool
   */
  function isSecretaire() {
    return $this->_is_secretaire = $this->isFromType(array("Secrétaire", "Administrator"));
  }

  /**
   * Check whether user is a medical user
   *
   * @return bool
   */
  function isMedical() {
    return $this->isFromType(array("Administrator", "Chirurgien", "Anesthésiste", "Infirmière", "Médecin", "Rééducateur", "Sage Femme", "Dentiste", "Pharmacien", "Diététicien"));
  }

  /**
   * @return bool
   */
  function isExecutantPrescription() {
    return $this->isFromType(array("Infirmière", "Aide soignant", "Rééducateur", "Sage Femme", "Diététicien", "ASSC"));
  }

  /**
   * Check whether user is a kine
   *
   * @return bool
   */
  function isKine() {
    return $this->_is_kine = $this->isFromType(array("Rééducateur"));
  }

  /**
   * Check whether user is a dieteticien
   *
   * @return bool
   */
  function isDieteticien() {
    return $this->_is_dieteticien = $this->isFromType(array("Diététicien"));
  }

  /**
   * @return bool
   */
  function isAdmin() {
    return $this->isFromType(array("Administrator"));
  }

  /**
   * Check whether user is a urgentiste
   *
   * @return bool
   */
  function isUrgentiste() {
    $service_urgences_id  = CGroups::loadCurrent()->service_urgences_id;
    $this->_is_urgentiste = ($this->function_id == $service_urgences_id);

    // Check in secondaries functions
    if (!$this->_is_urgentiste) {
      $secondary_func              = new CSecondaryFunction();
      $secondary_func->user_id     = $this->_id;
      $secondary_func->function_id = $service_urgences_id;

      if ($secondary_func->loadMatchingObject()) {
        $this->_is_urgentiste = true;
      }
    }

    return $this->_is_urgentiste;
  }

  /**
   * Check whether user is PMSI
   *
   * @return bool
   */
  function isPMSI() {
    return $this->isFromType(array("PMSI"));
  }

  /**
   * Check whether user is ASSC
   *
   * @return bool
   */
  function isASSC() {
    return $this->isFromType(array("ASSC"));
  }

  /**
   * Check whether user is Surveillante de bloc
   *
   * @return bool
   */
  function isSurveillantBloc() {
    return $this->isFromType(['Surveillante de bloc']);
  }

  /**
   * load the list of POP account
   *
   * @return CStoredObject[]
   */
  function loadRefsSourcePop() {
    return $this->_refs_source_pop = $this->loadBackRefs("sources_pop");
  }

  function fillTemplate(&$template) {
    $this->loadRefFunction();
    $this->loadRefSpecCPAM();
    $this->loadRefDiscipline();
    $this->_ref_function->fillTemplate($template);

    // Remplacé par
    $this->loadRefRemplace();
    // Remplacant de
    $this->loadRefRemplacant(CMbDT::dateTime());

    $praticien_section = CAppUI::tr('CMediusers-praticien');
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-_p_last_name'), $this->_user_last_name);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-_p_first_name'), $this->_user_first_name);

    if ($this->_ref_remplace) {
      $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-Replaced'), $this->_ref_remplace->_view);
    }

    if ($this->_ref_remplacant) {
      $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-Substitute'), $this->_ref_remplacant->_view);
    }

    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-initials'), $this->_shortview);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-Gender Agreement'), $this->_user_sexe == "f" ? "e" : "");
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-discipline_id'), $this->_ref_discipline->_view);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CDiscipline'), $this->_ref_spec_cpam->_view);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-cab'), $this->cab);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-conv'), $this->conv);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-zisd'), $this->zisd);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-ik'), $this->ik);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-rcc'), $this->compte_ch_id ? $this->loadRefCompteCh()->rcc : "");
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-_user_phone'), $this->getFormattedValue("_user_phone"));

    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-titres'), $this->titres);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMedecin-adeli'), $this->adeli);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-ADELI / AM'), $this->adeli);
    $template->addBarcode("$praticien_section - " . CAppUI::tr('CMediusers-ADELI / AM bar code'), $this->adeli, array("barcode" => array(
      "title" => CAppUI::tr("{$this->_class}-adeli"),
    )));
    $template->addBarcode("$praticien_section - " . CAppUI::tr('CMediusers-ADELI bar code'), $this->adeli, array("barcode" => array(
      "title" => CAppUI::tr("{$this->_class}-adeli"),
    )));
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-rpps'), $this->rpps);
    $template->addBarcode("$praticien_section - " . CAppUI::tr('CMedecin-RPPS bar code'), $this->rpps, array("barcode" => array(
      "title" => CAppUI::tr("{$this->_class}-rpps"),
    )));
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-_user_email'), $this->_user_email);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-mail_apicrypt'), $this->mail_apicrypt);
    $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-mssante_address'), $this->mssante_address);

    // Identité
    $identite = $this->loadNamedFile("identite.jpg");
    $template->addImageProperty("$praticien_section - " . CAppUI::tr('CMediusers-ID photo'), $identite->_id, array("title" => "$praticien_section - " . CAppUI::tr('CMediusers-ID photo')));

    // Signature
    $signature   = new CFile();
    $preferences = CPreferences::getAllPrefs($this->_id);
    if ($preferences["secure_signature"]) {
      $signature->_id = "[$praticien_section - " . CAppUI::tr('common-Signature') . "]";
    }
    else {
      $signature = $this->loadRefSignature();
    }

    $template->addImageProperty("$praticien_section - " . CAppUI::tr('common-Signature'), $signature->_id, array("title" => "$praticien_section - " . CAppUI::tr('common-Signature')));

    if (CAppUI::conf("ref_pays") == 2) {
      //EAN et EAN facturation
      $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-ean'), $this->ean);
      $template->addProperty("$praticien_section - " . CAppUI::tr('CMediusers-ean_base'), $this->ean_base);
      $template->addBarcode("$praticien_section - " . CAppUI::tr('CMediusers-EAN bar code'), $this->ean);
      $template->addBarcode("$praticien_section - " . CAppUI::tr('CMediusers-Billing EAN bar code'), $this->ean_base);
    }
  }

  /**
   * Charge la liste de plages et interventions pour un jour donné
   * Analogue à CSalle::loadRefsForDay
   *
   * @param string $date        Date to look for
   * @param bool   $second_chir Use chir_2, chir_3 and chir_4
   *
   * @return void
   */
  function loadRefsForDay($date, $second_chir = false) {
    $this->loadBackRefs("secondary_functions");
    $secondary_specs = array();
    foreach ($this->_back["secondary_functions"] as $_sec_spec) {
      /** @var CSecondaryFunction $_sec_spec */
      $_sec_spec->loadRefFunction();
      $_sec_spec->loadRefUser();
      $_function                        = $_sec_spec->_ref_function;
      $secondary_specs[$_function->_id] = $_function;
    }
    // Plages d'intervention
    $plage     = new CPlageOp();
    $ljoin     = array();
    $add_where = "";
    if ($second_chir) {
      $ljoin["operations"] = "plagesop.plageop_id = operations.plageop_id";
      $add_where           = " OR operations.chir_id = '$this->_id' OR operations.chir_2_id = '$this->_id'
                    OR operations.chir_3_id = '$this->_id' OR operations.chir_4_id = '$this->_id'";
    }
    $where                  = array();
    $where["plagesop.date"] = "= '$date'";
    $where[]                = "plagesop.chir_id = '$this->_id' OR plagesop.spec_id = '$this->function_id' OR plagesop.spec_id " . CSQLDataSource::prepareIn(array_keys($secondary_specs)) . $add_where;
    $order                  = "debut";
    $this->_ref_plages      = $plage->loadList($where, $order, null, "plageop_id", $ljoin);

    // Chargement d'optimisation

    CMbObject::massLoadFwdRef($this->_ref_plages, "chir_id");
    CMbObject::massLoadFwdRef($this->_ref_plages, "anesth_id");
    CMbObject::massLoadFwdRef($this->_ref_plages, "spec_id");
    CMbObject::massLoadFwdRef($this->_ref_plages, "salle_id");

    CMbObject::massCountBackRefs($this->_ref_plages, "notes");
    CMbObject::massCountBackRefs($this->_ref_plages, "affectations_personnel");

    foreach ($this->_ref_plages as $_plage) {
      /** @var CPlageOp $_plage */
      $_plage->loadRefChir();
      $_plage->loadRefAnesth();
      $_plage->loadRefSpec();
      $_plage->loadRefSalle();
      $_plage->makeView();
      $_plage->loadRefsOperations();
      $_plage->loadRefsNotes();
      $_plage->loadAffectationsPersonnel();
      $_plage->_unordered_operations = array();

      // Chargement d'optimisation
      CMbObject::massLoadFwdRef($_plage->_ref_operations, "chir_id");
      $sejours = CMbObject::massLoadFwdRef($_plage->_ref_operations, "sejour_id");
      CMbObject::massLoadFwdRef($sejours, "patient_id");

      foreach ($_plage->_ref_operations as $_operation) {
        if ($_operation->chir_id != $this->_id && (!$second_chir || ($_operation->chir_2_id != $this->_id && $_operation->chir_3_id != $this->_id && $_operation->chir_4_id != $this->_id))) {
          unset($_plage->_ref_operations[$_operation->_id]);
        }
        else {
          $_operation->_ref_chir = $this;
          $_operation->loadExtCodesCCAM();
          $_operation->updateSalle();
          $_operation->loadRefPatient();

          // Extraire les interventions non placées
          if ($_operation->rank == 0) {
            $_plage->_unordered_operations[$_operation->_id] = $_operation;
            unset($_plage->_ref_operations[$_operation->_id]);
          }
        }
      }
      if (count($_plage->_ref_operations) + count($_plage->_unordered_operations) < 1) {
        unset($this->_ref_plages[$_plage->_id]);
      }
    }

    // Interventions déplacés
    $deplacee                       = new COperation();
    $ljoin                          = array();
    $ljoin["plagesop"]              = "operations.plageop_id = plagesop.plageop_id";
    $where                          = array();
    $where["operations.plageop_id"] = "IS NOT NULL";
    $where["operations.annulee"]    = "= '0'";
    $where["plagesop.salle_id"]     = "!= operations.salle_id";
    $where["plagesop.date"]         = "= '$date'";
    $where[]                        = "plagesop.chir_id = '$this->_id'" . $add_where;
    $order                          = "operations.time_operation";
    $this->_ref_deplacees           = $deplacee->loadList($where, $order, null, "operation_id", $ljoin);

    // Chargement d'optimisation
    CMbObject::massLoadFwdRef($this->_ref_deplacees, "chir_id");
    $sejours_deplacees = CMbObject::massLoadFwdRef($this->_ref_deplacees, "sejour_id");
    CMbObject::massLoadFwdRef($sejours_deplacees, "patient_id");

    foreach ($this->_ref_deplacees as $_deplacee) {
      /** @var COperation $_deplacee */
      $_deplacee->loadRefChir();
      $_deplacee->loadRefPatient();
      $_deplacee->loadExtCodesCCAM();
    }

    // Urgences
    $urgence             = new COperation();
    $where               = array();
    $where["plageop_id"] = "IS NULL";
    $where["date"]       = "= '$date'";
    if ($second_chir) {
      $where[] = "chir_id = '$this->_id' OR chir_2_id = '$this->_id' OR chir_3_id = '$this->_id' OR chir_4_id = '$this->_id'";
    }
    else {
      $where["chir_id"] = "= '$this->_id'";
    }
    $where["annulee"] = "= '0'";

    $this->_ref_urgences = $urgence->loadList($where, null, null, "operation_id");

    // Chargement d'optimisation
    CMbObject::massLoadFwdRef($this->_ref_urgences, "chir_id");
    $sejours_urgences = CMbObject::massLoadFwdRef($this->_ref_urgences, "sejour_id");
    CMbObject::massLoadFwdRef($sejours_urgences, "patient_id");

    foreach ($this->_ref_urgences as $_urgence) {
      /** @var COperation $_urgence */
      $_urgence->loadRefChir();
      $_urgence->loadRefPatient();
      $_urgence->loadExtCodesCCAM();
    }
  }

  /**
   * Builds a structure containing basic information about the user, to be used in JS in window.User
   *
   * @return array|null
   */
  function getBasicInfo() {
    if (!$this->_ref_module) {
      return null;
    }

    $this->updateFormFields();
    $this->loadRefFunction()->loadRefGroup();

    return $this->_basic_info = array(
      'id'       => $this->_id,
      'guid'     => $this->_guid,
      'view'     => $this->_view,
      'login'    => $this->_user_username,
      'type'     => $this->_user_type,
      'function' => array(
        'id'    => $this->_ref_function->_id,
        'guid'  => $this->_ref_function->_guid,
        'view'  => $this->_ref_function->_view,
        'color' => $this->_ref_function->color,
      ),
      'group'    => array(
        'guid' => $this->_ref_function->_ref_group->_guid,
        'id'   => $this->_ref_function->_ref_group->_id,
        'view' => $this->_ref_function->_ref_group->_view,
      ),
      'config'   => array(
        'ldap_connection'          => CAppUI::conf('admin LDAP ldap_connection'),
        'allow_ldap_loginas_admin' => CAppUI::conf('admin LDAP allow_login_as_admin'),
      ),
    );
  }

  function makeUsernamePassword($first_name, $last_name, $id = null, $number = false, $prepass = "mdp") {
    $length               = 20 - strlen($id);
    $this->_user_username = substr(preg_replace($number ? "/[^a-z0-9]/i" : "/[^a-z]/i", "", strtolower(CMbString::removeDiacritics(($first_name ? $first_name[0] : '') . $last_name))), 0, $length) . $id;
    $this->_user_password = $prepass . substr(preg_replace($number ? "/[^a-z0-9]/i" : "/[^a-z]/i", "", strtolower(CMbString::removeDiacritics($last_name))), 0, $length) . $id;
  }

  function getNbJoursPlanningSSR($date) {
    $sunday   = CMbDT::date("next sunday", CMbDT::date("- 1 DAY", $date));
    $saturday = CMbDT::date("-1 DAY", $sunday);

    $_evt               = new CEvenementSSR();
    $where              = array();
    $where["debut"]     = "BETWEEN '$sunday 00:00:00' AND '$sunday 23:59:59'";
    $where["sejour_id"] = " = '$this->_id'";
    $count_event_sunday = $_evt->countList($where);

    $nb_days = 7;

    // Si aucun evenement le dimanche
    if (!$count_event_sunday) {
      $nb_days              = 6;
      $where["debut"]       = "BETWEEN '$saturday 00:00:00' AND '$saturday 23:59:59'";
      $count_event_saturday = $_evt->countList($where);
      // Aucun evenement le samedi et aucun le dimanche
      if (!$count_event_saturday) {
        $nb_days = 5;
      }
    }

    return $nb_days;
  }

  /**
   * @inheritDoc
   */
  function getAutocompleteList($keywords, $where = null, $limit = null, $ljoin = null, $order = null, $group_by = null, bool $strict = true) {
    $ljoin = array_merge($ljoin ?? [], array("users" => "users.user_id = users_mediboard.user_id"));
    /** @var CMediusers[] $list */
    $list = $this->seek($keywords, $where, $limit, null, $ljoin, "users.user_last_name", $group_by, $strict);

    foreach ($list as $_mediuser) {
      $_mediuser->loadRefFunction();
    }

    return $list;
  }

  /**
   * Return idex type if it's special (e.g. software/...)
   *
   * @param CIdSante400 $idex Idex
   *
   * @return string|null
   */
  function getSpecialIdex(CIdSante400 $idex) {
    //identifier les comptes de type logiciel
    if ($idex->tag == self::getTagSoftware()) {
      return "software";
    }

    return null;
  }

  /**
   * return the tag used for identifying "software user"
   *
   * @param int $group_id group_id
   *
   * @return mixed
   */
  static function getTagSoftware($group_id = null) {
    // Pas de tag Mediusers
    if (null == $tag_mediusers_software = CAppUI::gconf("mediusers CMediusers tag_mediuser_software")) {
      return null;
    }

    // Permettre des id externes en fonction de l'établissement
    $group = CGroups::loadCurrent();
    if (!$group_id) {
      $group_id = $group->_id;
    }

    return str_replace('$g', $group_id, $tag_mediusers_software);
  }

  /**
   * Construit le tag Mediusers en fonction des variables de configuration
   *
   * @param int $group_id Permet de charger l'id externe d'un Mediuser pour un établissement donné si non null
   *
   * @return string
   */
  static function getTagMediusers($group_id = null) {
    // Pas de tag Mediusers
    if (null == $tag_mediusers = CAppUI::gconf("mediusers CMediusers tag_mediuser")) {
      return null;
    }

    // Permettre des id externes en fonction de l'établissement
    $group = CGroups::loadCurrent();
    if (!$group_id) {
      $group_id = $group->_id;
    }

    return str_replace('$g', $group_id, $tag_mediusers);
  }

  /**
   * @see parent::getDynamicTag
   */
  function getDynamicTag() {
    return CAppUI::gconf("mediusers CMediusers tag_mediuser");
  }

  /**
   * Is the user a robot?
   *
   * @return bool
   */
  function isRobot() {
    if (!$this->_id) {
      return false;
    }
    $tag_software = CMediusers::getTagSoftware();

    if (CModule::getActive("dPsante400") && $tag_software) {
      if (CIdSante400::getMatch($this->_class, $tag_software, null, $this->_id)->_id != null) {
        return true;
      }
    }

    if (!$this->_ref_user || !$this->_ref_user->_id) {
      $this->loadRefUser();
    }

    return $this->_ref_user->isRobot();
  }

  /**
   * Map the class variable with CPerson variable
   *
   * @return void
   */
  function mapPerson() {
    $this->_p_city           = $this->_user_ville;
    $this->_p_postal_code    = $this->_user_cp;
    $this->_p_street_address = $this->_user_adresse;
    $this->_p_phone_number   = $this->_user_phone;
    $this->_p_email          = $this->_user_email;
    $this->_p_first_name     = $this->_user_first_name;
    $this->_p_last_name      = $this->_user_last_name;
  }

  /**
   * Fonction récupérant les rétrocessions du praticien
   *
   * @return $this->_ref_retrocessions
   **/
  function loadRefsRetrocessions() {
    return $this->_ref_retrocessions = $this->loadBackRefs("retrocession");
  }

  static function loadFromAdeli($adeli) {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $mediuser                       = new CMediusers();
    $where                          = array();
    $where["users_mediboard.adeli"] = " = '$adeli'";
    $mediuser->loadObject($where);

    return $cache->put($mediuser, false);
  }

  static function loadFromRPPS($rpps) {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $mediuser                      = new CMediusers();
    $where                         = array();
    $where["users_mediboard.rpps"] = " = '$rpps'";
    $mediuser->loadObject($where);

    return $cache->put($mediuser, false);
  }

  function getLastLogin() {
    return $this->_user_last_login = $this->_ref_user->getLastLogin();
  }

  function loadRefSignature() {
    return $this->_ref_signature = $this->loadNamedFile("signature.jpg");
  }

  /**
   * Sends a credentials notification
   *
   * @param string $password User's password
   * @param string $login    Username
   * @param string $email    User's email address
   *
   * @return void
   */
  function notifyAccount($password, $login = null, $email = null) {
    $login = ($login) ?: $this->_user_username;
    $email = ($email) ?: $this->_user_email;

    if (!$login || !$password || !$email) {
      CAppUI::stepAjax('common-error-Missing parameter', UI_MSG_ERROR);
    }

    /** @var $exchange_source CSourceSMTP */
    $exchange_source         = new CSourceSMTP();
    $exchange_source->name   = 'system-message';
    $exchange_source->active = 1;
    $exchange_source->loadMatchingObject();

    if (!$exchange_source->_id) {
      CAppUI::stepAjax("CExchangeSource.none", UI_MSG_ERROR);
    }

    try {
      $exchange_source->init();

      $exchange_source->addTo($email);
      $exchange_source->setSubject(CAppUI::tr('CMediusers-Credential|pl'));
      $exchange_source->setBody(
        CAppUI::tr(
          'CMediusers-msg-Your new credentials are available: %s %s %s',
          rtrim(CAppUI::conf('external_url'), '/') . '/index.php',
          $login,
          $password
        )
      );

      $exchange_source->send();
      CAppUI::setMsg('common-msg-Notification send', UI_MSG_OK);
    }
    catch (phpmailerException $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
    }
    catch (CMbException $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
    }

    return;
  }

  /**
   * @see CUser::isSuperAdmin()
   */
  function isSuperAdmin() {
    return $this->loadRefUser()->isSuperAdmin();
  }

  /**
   * @see CUser::mustChangePassword()
   */
  public function mustChangePassword() {
    return $this->loadRefUser()->mustChangePassword();
  }

  /**
   * @see parent::getSexFieldName()
   */
  function getSexFieldName() {
    return "sexe";
  }

  /**
   * @see parent::getPrenomFieldName()
   */
  function getPrenomFieldName() {
    return "prenom";
  }

  /**
   * Charge les compétences d'un utilisateur
   *
   * @param bool $archivees Récupérer également les compétences archivées
   *
   * @return COXCompetenceItem[]
   */
  function loadRefCompetences($archivees = false) {
    $where = array();
    $ljoin = array();

    if (!$archivees) {
      $where['archivee']      = "= '0'";
      $ljoin['ox_competence'] = 'ox_competence_item.competence_id = ox_competence.ox_competence_id';
    }

    return $this->_ref_competences = $this->loadBackRefs('competence_items', null, null, null, $ljoin, null, null, $where);
  }

  /**
   * Recherche de remplaçant
   *
   * @param string $datetime Moment de la recherche de remplacant
   *
   * @return CMediusers|null
   */
  function loadRefRemplacant($datetime) {
    $where                = array();
    $where["remplace_id"] = " = '$this->_id'";
    $where["debut"]       = " <= '$datetime'";
    $where["fin"]         = " >= '$datetime'";
    $remplacement         = new CRemplacement();
    $remplacement->loadObject($where, "debut DESC, fin DESC", "remplacement_id");

    return $this->_ref_remplacant = $remplacement->_id ? $remplacement->loadRefRemplacant() : null;
  }

  /**
   * Recherche de remplacé
   *
   * @return CRemplacement|null
   */
  function loadRefRemplace() {
    $datetime               = CMbDT::dateTime();
    $where                  = array();
    $where["remplacant_id"] = " = '$this->_id'";
    $where["debut"]         = " <= '$datetime'";
    $where["fin"]           = " >= '$datetime'";
    $remplacant             = new CRemplacement();
    $remplacant->loadObject($where);

    return $this->_ref_remplace = $remplacant->_id ? $remplacant->loadRefRemplace() : null;
  }

  /**
   * Load the parent user
   *
   * @return CMediusers
   */
  public function loadRefMainUser() {
    return $this->_ref_main_user = $this->loadFwdRef('main_user_id');
  }

  /**
   * Load the descendants users
   *
   * @return CMediusers[]
   */
  public function loadRefsSecondaryUsers() {
    $this->_ref_secondary_users = $this->loadBackRefs('secondary_users');
    foreach ($this->_ref_secondary_users as $_user) {
      $_user->loadRefFunction();
    }

    return $this->_ref_secondary_users;
  }

  /**
   * Return an SQL where clause for selecting an object linked to the user, and it's secondary accounts (if any)
   *
   * @return string
   */
  public function getUserSQLClause() {
    $sql = " = '{$this->_id}'";

    $this->loadRefsSecondaryUsers();
    if ($this->_id && count($this->_ref_secondary_users)) {
      $sql = CSQLDataSource::prepareIn(array_merge(array($this->_id), array_keys($this->_ref_secondary_users)));
    }

    return $sql;
  }

  /**
   * Tells whether a user is a primary user or not
   *
   * @return bool
   */
  function isSecondary() {
    return ($this->_id && $this->main_user_id);
  }

  /**
   * load the list of clinical program
   *
   * @return CProgrammeClinique[]
   */
  function loadRefsProgrammesClinique() {
    return $this->_refs_programmes_clinique = $this->loadBackRefs("programmes_clinique");
  }

  /**
   * Charge l'unité fonctionnelle médicale du user
   *
   * @param string $type_sejour Type de séjour optionnel
   *
   * @return void
   */
  function loadRefUfMedicale($type_sejour = null) {
    if (!CModule::getActive("dPhospi")) {
      return;
    }

    $this->loadRefsUfsMedicales($type_sejour);

    $this->_ref_uf_medicale = new CUniteFonctionnelle();

    if (count($this->_ref_ufs_medicales)) {
      $this->_ref_uf_medicale = reset($this->_ref_ufs_medicales);
    }
  }

  /**
   * Charge les unités fonctionnelles médicales du user
   *
   * @param string $type_sejour Type de séjour optionnel
   *
   * @return void
   */
  function loadRefsUfsMedicales($type_sejour = null) {
    if (!CModule::getActive("dPhospi")) {
      return;
    }

    $where = array(
      "uf.type" => "= 'medicale'",
    );

    $ljoin = array(
      "uf" => "uf.uf_id = affectation_uf.uf_id",
    );

    if ($type_sejour) {
      $where["type_sejour"] = "IS NULL OR type_sejour = '$type_sejour'";
    }

    $aff_uf = $this->loadBackRefs("ufs", null, null, null, $ljoin, null, "ufs$type_sejour", $where);

    $this->_ref_ufs_medicales = CStoredObject::massLoadFwdRef($aff_uf, "uf_id");
  }

  /**
   * Mass loading de l'unité fonctionnelle médicale pour une collection de users
   *
   * @param self[] $users       Collection des users
   * @param string $type_sejour Type de séjour optionnel
   *
   * @return void
   */
  static function massLoadUfMedicale($users, $type_sejour = null) {
    if (!CModule::getActive("dPhospi") || !count($users)) {
      return;
    }

    $where = array(
      "uf.type" => "= 'medicale'",
    );

    $ljoin = array(
      "uf" => "uf.uf_id = affectation_uf.uf_id",
    );

    if ($type_sejour) {
      $where["type_sejour"] = "IS NULL OR type_sejour = '$type_sejour'";
    }

    $aff_ufs = CStoredObject::massLoadBackRefs($users, "ufs", null, $where, $ljoin, "ufs$type_sejour");
    CStoredObject::massLoadFwdRef($aff_ufs, "uf_id");

    /** @var self $_user */
    foreach ($users as $_user) {
      $_user->loadRefUfMedicale();
    }
  }

  /**
   * Charge les unités fonctionnelles médicale secondaires du user
   *
   * @param string $type_sejour Type de séjour optionnel
   *
   * @return void
   */
  function loadRefUfMedicaleSecondaire($type_sejour = null) {
    if (!CModule::getActive("dPhospi")) {
      return;
    }

    $where = array(
      "uf.type" => "= 'medicale'",
    );

    $ljoin = array(
      "uf" => "uf.uf_id = affectation_uf_second.uf_id",
    );

    if ($type_sejour) {
      $where["type_sejour"] = "IS NULL OR type_sejour = '$type_sejour'";
    }

    $aff_uf = $this->loadBackRefs("ufs_secondaires", null, null, null, $ljoin, null, "ufs_secondaires$type_sejour", $where);

    $this->_ref_uf_medicale_secondaire = array();
    /** @var CAffectationUfSecondaire $_aff_uf */
    foreach ($aff_uf as $_aff_uf) {
      $this->_ref_uf_medicale_secondaire[$_aff_uf->uf_id] = $_aff_uf->loadRefUniteFonctionnelle();
    }
  }

  /**
   * Mass loading des unités fonctionnelles médicale secondaires pour une collection de users
   *
   * @param self[] $users       Collection des users
   * @param string $type_sejour Type de séjour optionnel
   *
   * @return void
   */
  static function massLoadUfMedicaleSecondaire($users, $type_sejour = null) {
    if (!CModule::getActive("dPhospi") || !count($users)) {
      return;
    }

    $where = array(
      "uf.type" => "= 'medicale'",
    );

    $ljoin = array(
      "uf" => "uf.uf_id = affectation_uf_second.uf_id",
    );

    if ($type_sejour) {
      $where["type_sejour"] = "IS NULL OR type_sejour = '$type_sejour'";
    }

    $aff_ufs = CStoredObject::massLoadBackRefs($users, "ufs_secondaires", null, $where, $ljoin, "ufs_secondaires$type_sejour");
    CStoredObject::massLoadFwdRef($aff_ufs, "uf_id");

    /** @var self $_user */
    foreach ($users as $_user) {
      $_user->loadRefUfMedicaleSecondaire();
    }
  }

  /**
   * Tells if current user must fill his professional context
   *
   * @return bool
   */
  static function mustFillProfessionalContext() {
    if (!CAppUI::gconf('mediusers CMediusers force_professional_context')) {
      return false;
    }

    if (static::isProfessionalContextSet()) {
      return false;
    }

    $user = CMediusers::get();

    if (!$user || !$user->_id) {
      return false;
    }

    return ($user->isExecutantCCAM() && !$user->hasProfessionalContextFilled());
  }

  /**
   * Tells if a user has filled his professional context
   *
   * @return bool
   */
  function hasProfessionalContextFilled() {
    $has = true;

    foreach (static::$professional_context_fields as $_field => $_mandatory) {
      if (!$_mandatory) {
        continue;
      }

      $has = ($has && $this->{$_field});
    }

    return $has;
  }

  /**
   * Tells if professional context is set (from session)
   *
   * @return bool
   */
  static function isProfessionalContextSet() {
    return (bool)CValue::sessionAbs('PROFESSIONAL_CONTEXT_FILLED');
  }

  /**
   * Stores information about professional context in session
   *
   * @return void
   */
  static function setProfessionalContext() {
    CValue::setSessionAbs('PROFESSIONAL_CONTEXT_FILLED', 1);
  }

  /**
   * Load the list of comptes ch
   *
   * @return CMediusersCompteCh[]
   */
  function loadRefsCompteCh() {
    return $this->_ref_comptes_ch = $this->loadBackRefs("comptes_ch", "name ASC");
  }

  /**
   * @return CMediusersCompteCh
   */
  function loadRefCompteCh() {
    return $this->_ref_compte_ch = $this->loadFwdRef("compte_ch_id", true);
  }

  /**
   * Check if the practitioner has a range of consultation
   *
   * @param string $date Date
   *
   * @return int
   */
  function checkRangeConsult($date) {
    $where            = array();
    $where["chir_id"] = " = '$this->_id'";
    $where["date"]    = " = '$date'";

    $plage     = new CPlageconsult();
    $nb_plages = $plage->countList($where, "date, debut");

    return $nb_plages;
  }

  /**
   * Tells whether a user is active (bool or activity dates)
   *
   * @return bool
   */
  public function isActive() {
    if (!$this->actif) {
      return false;
    }

    $now = CMbDT::date();

    if ($this->deb_activite && ($this->deb_activite > $now)) {
      return false;
    }

    if ($this->fin_activite && ($this->fin_activite <= $now)) {
      return false;
    }

    return true;
  }

  /**
   * Charge les agendas consultation par group
   *
   * @param bool $only_active
   *
   * @return CAgendaPraticien[]
   * @throws Exception
   */
  function loadRefsAgendasPraticienByGroup($only_active = true) {
    $where = [
      "lieuconsult.group_id" => "= '" . CGroups::loadCurrent()->_id . "'",
    ];

    $ljoin = [
      "lieuconsult" => "lieuconsult.lieuconsult_id = agenda_praticien.lieuconsult_id",
    ];

    if ($only_active) {
      $where["agenda_praticien.active"] = "= '1'";
    }

    return $this->_ref_agendas_praticien = $this->loadBackRefs("agendas_praticien", null, null, null, $ljoin, null, "agendas_prat_grp", $where);
  }

  /**
   * Charge les agendas consultation
   *
   * @return CStoredObject[]|CAgendaPraticien[]
   * @throws Exception
   */
  function loadRefsAgendasPraticien() {
    return $this->_ref_agendas_praticien = $this->loadBackRefs("agendas_praticien");
  }

  /**
   * Loads active agandas that are synced
   *
   * @return CStoredObject[]|CAgendaPraticien[]|null
   * @throws Exception
   */
  function loadActiveAgendasToSync() {
    $ds    = $this->getDS();
    $where = [
      "active" => $ds->prepare("= ?", "1"),
      "sync"   => $ds->prepare("= ?", "1")
    ];

    return $this->_ref_agendas_to_sync = $this->loadBackRefs("agendas_praticien", null, null, null, null, null, "agendas_sync", $where);
  }

  /**
   * Charge les lieux de consultation associés au praticien
   *
   * @param boolean $only_active Seulement les lieux actifs
   *
   * @return CLieuConsult[]
   */
  function loadRefsLieuxConsult($only_active = true) {
    $agendas_praticien = $this->loadRefsAgendasPraticienByGroup($only_active);

    CStoredObject::massLoadFwdRef($agendas_praticien, "lieuconsult_id");

    $curr_group = CGroups::loadCurrent();

    /** @var CAgendaPraticien $_agenda */
    foreach ($agendas_praticien as $_agenda) {
      $_lieu = $_agenda->loadRefLieu();

      if ($_lieu->group_id != $curr_group->_id || ($only_active && (!$_lieu->active || !$_agenda->active))) {
        continue;
      }

      $_lieu->loadRefsAgendasPrat();
      $this->_ref_lieux_consult[$_lieu->_id] = $_lieu;
    }

    return $this->_ref_lieux_consult;
  }

  /**
   * Charge les protocoles opératoires associés au praticien
   *
   * @param string $limit Limite éventelle
   *
   * @return CProtocoleOperatoire[]
   */
  public function loadRefsProtocolesOperatoires($limit = null) {
    return $this->_ref_protocoles_op = $this->loadBackRefs("protocoles_op", "libelle", $limit);
  }

    /**
     * @return CItem|null
     * @throws CApiException
     */
    public function getResourceFunction(): ?CItem
    {
        $function = $this->loadRefFunction();
        if (!$function || !$function->_id) {
            return null;
        }

        $item = new CItem($function);
        $item->setName(CFunctions::RESOURCE_NAME);

        return $item;
    }

    /**
     * @return CItem|null
     * @throws CApiException
     */
    public function getResourceGroup(): ?CItem
    {
        $function = $this->loadRefFunction();
        if (!$function || !$function->_id) {
            return null;
        }

        $group = $function->loadRefGroup();
        if (!$group || !$group->_id) {
            return null;
        }

        $item = new CItem($group);
        $item->setName(CGroups::RESOURCE_NAME);

        return $item;
    }

    /**
   * Charge les CPS associés à l'utilisateur
   *
   * @return CPyxvitalCPS[]|CPvCPS[]|null
   * @throws Exception
   */
  public function loadRefsCPS() {
    $back_name_cps = null;
    if (CModule::getActive('oxPyxvital')) {
      $back_name_cps ="cps_pyxvital";
    }
    elseif (CModule::getActive('pyxVital')) {
      $back_name_cps ="cps_pyxvital";
    }

    if (!$back_name_cps) {
      return null;
    }

    return $this->_ref_cps = $this->loadBackRefs($back_name_cps);
  }

  public function isLDAPLinked() {
      if (!CAppUI::conf("admin LDAP ldap_connection") || !$this->_id) {
          return null;
      }

      if (!$this->_ref_user) {
          $this->_ref_user = CUser::findOrFail($this->_id);
      }

      return $this->_ref_user->isLDAPLinked();
  }
}
