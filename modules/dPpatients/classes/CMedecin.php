<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Exception;
use Ox\AppFine\Server\Appointment\CContactPlace;
use Ox\AppFine\Server\Appointment\CPresentation;
use Ox\AppFine\Server\Appointment\CSchedulePlace;
use Ox\AppFine\Server\Appointment\CTemporaryInformation;
use Ox\AppFine\Server\CAppFineAppointment;
use Ox\AppFine\Server\CAppFineMotifConsult;
use Ox\AppFine\Server\Controllers\CDashboardController;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Core\CPerson;
use Ox\Core\CRequest;
use Ox\Core\CStoredObject;
use Ox\Core\FileUtil\CMbvCardExport;
use Ox\Core\SHM;
use Ox\Import\Framework\ImportableInterface;
use Ox\Import\Framework\Matcher\MatcherVisitorInterface;
use Ox\Import\Framework\Persister\PersisterVisitorInterface;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSpecCPAM;
use Ox\Mediboard\System\CGeoLocalisation;
use Ox\Mediboard\System\IGeocodable;

/**
 * The CMedecin Class
 */
class CMedecin extends CPerson implements IGeocodable, ImportableInterface {
  /** @var string  */
  public const RESOURCE_NAME = 'doctor';

  /** @var string */
  public const FIELDSET_CONTACT    = "contact";

  /** @var string  */
  public const FIELDSET_IDENTIFIER = "identifier";

  /** @var string  */
  public const FIELDSET_SPECIALITY = "speciality";

  /** @var string  */
  public const FIELDSET_APPOINTMENT = "appointment";

  /** @var string  */
  public const RELATION_APPOINTMENTS = "appointments";

  /** @var string  */
  public const RELATION_MOTIFS = "motifs";

  /** @var string */
  public const RELATION_MEDECIN_EXERCICE_PLACE = "medecinExercicePlace";

  /** @var string */
  public const RELATION_PRESENTATION = "presentation";

  /** @var string */
  public const RELATION_CONTACT_PLACE = "contactPlace";

  /** @var string */
  public const RELATION_SCHEDULE_PLACE = "schedulePlace";

  /** @var string */
  public const RELATION_TEMPORARY_INFORMATION = "temporaryInformation";

  // DB Table key
  public $medecin_id;

  // Owner
  public $function_id;
  public $group_id;
  public $spec_cpam_id;

  // DB Fields
  public $nom;
  public $prenom;
  public $jeunefille;
  public $sexe;
  public $actif;

  /** @var string Practitioner title */
  public $titre;

  public $adresse;
  public $ville;
  public $cp;
  public $tel;
  public $tel_autre;
  public $fax;
  public $portable;
  public $email;
  public $disciplines;
  public $orientations;
  public $complementaires;
  public $type;
  public $adeli;
  public $rpps;
  public $email_apicrypt;
  public $mssante_address;
  public $last_ldap_checkout;
  public $ignore_import_rpps;
  public $import_file_version;
  public $modalite_publipostage;
  public $ean;
  public $categorie_professionnelle;
  public $mode_exercice;

    // AppFine Prise RDV
    public $use_online_appointment_booking;
    public $authorize_booking_new_patient;
    public $authorize_teleconsultation;

  /** @var integer Allow to link a Mediuser with a CMedecin */
  public $user_id;

  // form fields
  public $_titre_long;

  /** @var string Current user starting formula */
  public $_starting_formula;

  /** @var string Current user closing formula */
  public $_closing_formula;

  public $_tutoiement;

  // Object References
  /** @var CPatient[] */
  public $_ref_patients;

  // Calculated fields
  public $_count_patients_traites;
  public $_count_patients_correspondants;
  public $_has_siblings;
  public $_confraternite;

  private $_is_importing = false;

  /** @var string Practitioner long view (with title in full text) */
  public $_longview;

  /** @var CFunctions */
  public $_ref_function;

  /** @var CSpecCPAM */
  public $_ref_spec_cpam;

  /** @var CMediusers */
  public $_ref_user;

  /** @var CGeoLocalisation */
  public $_ref_geolocalisation;

  public static $types = array(
    10 => 'medecin', // Médecin
    21 => 'pharmacie', // Pharmacie
    26 => 'audio', // Audioprothésiste
    28 => 'opticien', // Opticien-Lunetier
    31 => 'assistant_dent', // Assistant dentaire
    40 => 'dentiste', // Chirurgien dentiste
    41 => 'assistant_service_social', // Assistant de service social
    50 => 'sagefemme', // Sage femme
    60 => 'infirmier', // Infirmier
    69 => 'infirmierpsy', // Infirmier psychiatrique
    70 => 'kine', // Masseur-Kinésithérapeute
    71 => 'osteo', // Ostéopathe
    72 => 'psychotherapeute', // Psychothérapeute
    73 => 'chiro', // Chiropracteur
    80 => 'podologue', // Pédicure-podologue
    81 => 'orthoprot', // Orthoprothésiste
    82 => 'podoorth', // Podo-orthésiste
    83 => 'ortho', // Orthopédiste-orthésiste
    84 => 'oculariste', // Oculariste
    85 => 'epithesiste', // Épithésiste
    86 => 'technicien', // Technicien de laboratoire médical
    91 => 'orthophoniste', // Orthophoniste
    92 => 'orthoptiste', // Orthoptiste
    93 => 'psychologue', // Psychologue
    94 => 'ergo', // Érgothérapeute
    95 => 'diete', // Diététicien
    96 => 'psycho', // Psychomotricien
    98 => 'maniperm', // Manipulateur ERM
  );

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'medecin';
    $spec->key   = 'medecin_id';
    $spec->seek  = 'match';

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();

    $medecin_strict = (CAppUI::gconf("dPpatients CMedecin medecin_strict") == 1 ? ' notNull' : '');

    $props["function_id"]  = "ref class|CFunctions back|medecins_function";
    $props["group_id"]     = "ref class|CGroups back|medecins";
    $props["spec_cpam_id"] = "num fieldset|speciality";
    $props["nom"]          = "str notNull confidential seekable|order fieldset|default";
    $props["prenom"]       = "str seekable|order fieldset|default";
    $props["jeunefille"]   = "str confidential fieldset|default";
    $props["sexe"]         = "enum list|u|f|m default|u fieldset|default";
    $props["actif"]        = "bool default|1";
    $props["titre"]        = "enum list|m|mme|dr|pr fieldset|default";
    $props["adresse"]      = "text$medecin_strict confidential seekable fieldset|contact";
    $props["ville"]        = "str$medecin_strict confidential seekable fieldset|contact";
    [$min_cp, $max_cp] = CPatient::getLimitCharCP();
    $props["cp"]                    = "str$medecin_strict minLength|$min_cp maxLength|$max_cp confidential fieldset|contact";
    $props["tel"]                   = "phone confidential$medecin_strict fieldset|contact";
    $props["tel_autre"]             = "str maxLength|20 fieldset|contact";
    $props["fax"]                   = "phone confidential fieldset|contact";
    $props["portable"]              = "phone confidential fieldset|contact";
    $props["email"]                 = "str confidential fieldset|contact";
    $props["disciplines"]           = "text seekable fieldset|speciality";
    $props["orientations"]          = "text seekable fieldset|speciality";
    $props["complementaires"]       = "text seekable fieldset|speciality";
    $props["type"]                  = "enum list|" . implode('|', self::$types) . "|pharmacie|maison_medicale|autre default|medecin fieldset|speciality";
    $props["adeli"]                 = "code confidential mask|9*S*S99999S9 adeli fieldset|identifier";
    $props["rpps"]                  = "numchar length|11 confidential mask|99999999999 control|luhn fieldset|identifier";
    $props["email_apicrypt"]        = "email confidential fieldset|contact";
    $props['mssante_address']       = 'email confidential fieldset|contact';
    $props["last_ldap_checkout"]    = "date";
    $props["ignore_import_rpps"]    = "bool default|0";
    $props["import_file_version"]   = "str loggable|0";
    $props['user_id']               = 'ref class|CMediusers nullify back|medecin';
    $props["modalite_publipostage"] = "enum list|apicrypt|docapost|mail|mssante fieldset|contact";
    $props["ean"]                   = "str fieldset|identifier";
    $props["categorie_professionnelle"] = "enum list|civil|militaire|etudiant default|civil fieldset|speciality";
    $props["mode_exercice"]             = "enum list|liberal|salarie|benevole default|liberal fieldset|speciality";
    $props["use_online_appointment_booking"] = "bool fieldset|appointment";
    $props["authorize_booking_new_patient"]  = "bool fieldset|appointment";
    $props["authorize_teleconsultation"]  = "bool default|1 fieldset|appointment";

    $props["_starting_formula"] = "str";
    $props["_closing_formula"]  = "str";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function store() {
    // Création d'un correspondant en mode cabinets distincts
    if (!$this->_id && !$this->isImporting()) {
      if (CAppUI::isCabinet()) {
        $this->function_id = CMediusers::get()->function_id;
      }
      elseif (CAppUI::isGroup()) {
        $this->group_id = CMediusers::get()->loadRefFunction()->group_id;
      }
    }

    return parent::store();
  }

  /**
   * Compte les patients attachés
   *
   * @return void
   */
  function countPatients() {
    $this->_count_patients_traites        = $this->countBackRefs("patients_traites");
    $this->_count_patients_correspondants = $this->countBackRefs("patients_correspondants");
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    $this->nom    = CMbString::upper($this->nom);
    $this->prenom = CMbString::capitalize(CMbString::lower($this->prenom));

    $this->mapPerson();
    parent::updateFormFields();

    $this->_shortview = "{$this->nom} {$this->prenom}";
    $this->_view      = "{$this->nom} {$this->prenom}";
    $this->_longview  = "{$this->nom} {$this->prenom}";

    if ($this->type == "medecin") {
      $this->_confraternite = $this->sexe == "f" ? "Chère consoeur" : "Cher confrère";

      if (!$this->titre) {
        $this->_view     = CAppUI::tr("CMedecin.titre.dr") . " {$this->nom} {$this->prenom}";
        $this->_longview = CAppUI::tr("CMedecin.titre.dr-long") . " {$this->nom} {$this->prenom}";
      }
    }

    if ($this->titre) {
      $this->_view       = CAppUI::tr("CMedecin.titre.{$this->titre}") . " {$this->_view}";
      $this->_titre_long = CAppUI::tr("CMedecin.titre.{$this->titre}-long");
      $this->_longview   = "{$this->_titre_long} {$this->nom} {$this->prenom}";
    }

    if ($this->type && $this->type != 'medecin') {
      $this->_view     .= " ({$this->_specs['type']->_locales[$this->type]})";
      $this->_longview .= " ({$this->_specs['type']->_locales[$this->type]})";
    }
  }

  /**
   * @inheritdoc
   */
  function updatePlainFields() {
    parent::updatePlainFields();

    if ($this->nom) {
      $this->nom = CMbString::upper($this->nom);
    }
    if ($this->prenom) {
      $this->prenom = CMbString::capitalize(CMbString::lower($this->prenom));
    }
  }

  /**
   * @inheritdoc
   */
  function loadRefs() {
    // Backward references
    $obj                 = new CPatient();
    $this->_ref_patients = $obj->loadList("medecin_traitant = '$this->medecin_id'");
  }

  /**
   * Chargement de la fonction reliée
   *
   * @return CFunctions
   */
  function loadRefFunction() {
    return $this->_ref_function = $this->loadFwdRef("function_id", true);
  }

  /**
   * Chargement de la spécialité CPAM reliée
   *
   * @return CSpecCPAM
   */
  function loadRefSpecCPAM() {
    return $this->_ref_spec_cpam = CSpecCPAM::get($this->spec_cpam_id);
  }

  /**
   * Load the CMediusers
   *
   * @param bool $cache Set to true if you want to use the cache
   *
   * @return CMediusers
   */
  public function loadRefUser($cache = true) {
    return $this->_ref_user = $this->loadFwdRef('user_id', $cache);
  }

  /**
   * Charge les médecins identiques
   *
   * @param bool $strict_cp Stricte sur la recherche par code postal
   *
   * @return self[]
   */
  function loadExactSiblings($strict_cp = true) {
    $ds = $this->getDS();

    $medecin      = new self();
    $where        = array();
    $where["nom"] = $ds->prepare(" = %", $this->nom);

    if ($this->prenom) {
      $where["prenom"] = $ds->prepare(" = %", $this->prenom);
    }
    else {
      $where["prenom"] = "IS NULL";
    }

    if (CAppUI::isCabinet()) {
      $where["function_id"] = $ds->prepare(" = %", CMediusers::get()->function_id);
    }
    elseif (CAppUI::isGroup()) {
      $where["group_id"] = $ds->prepare(" = %", CMediusers::get()->loadRefFunction()->group_id);
    }


    if ($this->cp) {
      if (!$strict_cp) {
        $cp          = substr($this->cp, 0, 2);
        $where["cp"] = " LIKE '{$cp}___'";
      }
      else {
        $where["cp"] = " = '$this->cp'";
      }
    }

    $medecin->escapeValues();

    $siblings = $medecin->loadList($where);
    unset($siblings[$this->_id]);

    return $siblings;
  }

  /**
   * @inheritdoc
   */
  function getSexFieldName() {
    return "sexe";
  }

  /**
   * @inheritdoc
   */
  function getPrenomFieldName() {
    return "prenom";
  }

  /**
   * @inheritdoc
   */
  function getNomFieldName() {
    return 'nom';
  }

  /**
   * Exporte au format vCard
   *
   * @param CMbvCardExport $vcard Objet vCard
   *
   * @return void
   */
  function toVcard(CMbvCardExport $vcard) {
    $vcard->addName($this->prenom, $this->nom, "");
    $vcard->addPhoneNumber($this->tel, 'WORK');
    $vcard->addPhoneNumber($this->portable, 'CELL');
    $vcard->addPhoneNumber($this->fax, 'FAX');
    $vcard->addEmail($this->email);
    $vcard->addAddress($this->adresse, $this->ville, $this->cp, "", 'WORK');
  }

  /**
   * Map the class variable with CPerson variable
   *
   * @return void
   */
  function mapPerson() {
    $this->_p_city                = $this->ville;
    $this->_p_postal_code         = $this->cp;
    $this->_p_street_address      = $this->adresse;
    $this->_p_phone_number        = $this->tel;
    $this->_p_fax_number          = $this->fax;
    $this->_p_mobile_phone_number = $this->portable;
    $this->_p_email               = $this->email;
    $this->_p_first_name          = $this->prenom;
    $this->_p_last_name           = $this->nom;
    $this->_p_maiden_name         = $this->jeunefille;
  }

  /**
   * Load all the CMedecin object with one RPPS
   *
   * @param string $rpps        RPPS to search for
   * @param int    $function_id Function id of CMedecin
   *
   * @return CMedecin
   */
  function loadByRpps($rpps, $function_id = null) {
    $cache = new Cache(__METHOD__, func_get_args(), CACHE::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $where = [
      'rpps'        => $this->getDS()->prepare('= ?', $rpps),
      'function_id' => ($function_id !== null) ? $this->getDS()->prepare('= ?', $function_id) : 'IS NULL',
    ];

    $this->loadObject($where);

    return $cache->put($this);
  }

  /**
   * Load all the CMedecin object with one RPPS
   *
   * @param string $adeli       ADELI to search for
   * @param int    $function_id Function id of CMedecin
   *
   * @return CMedecin
   */
  function loadByAdeli($adeli, $function_id = null) {
    $cache = new Cache(__METHOD__, func_get_args(), CACHE::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $where = [
      'adeli'       => $this->getDS()->prepare('= ?', $adeli),
      'function_id' => ($function_id !== null) ? $this->getDS()->prepare('= ?', $function_id) : 'IS NULL',
    ];

    $this->loadObject($where);

    return $cache->put($this);
  }

  /**
   * Load all the CMedecin with $nom, $prenom, $type, $cp and $function_id
   *
   * @param string $nom         Last name to search for
   * @param string $prenom      First name to search for
   * @param string $type        Type
   * @param string $cp          Cp can be full or only first two nums
   * @param int    $function_id Function ID
   *
   * @return CMedecin
   */
  function loadMedecinList($nom, $prenom, $type, $cp, $function_id = null) {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $ds = $this->getDS();

    if ($cp && strlen($cp) > 2) {
      $cp_condition = $ds->prepare('= ?', $cp);
    }
    elseif ($cp) {
      $cp_condition = $ds->prepareLike("$cp%");
    }
    else {
      $cp_condition = "IS NULL";
    }

    $where = array(
      'nom'         => $ds->prepare('= ?', $nom),
      'prenom'      => $ds->prepare('= ?', $prenom),
      'type'        => $ds->prepare('= ?', self::$types[$type]),
      'cp'          => $cp_condition,
      'function_id' => ($function_id !== null) ? $ds->prepare('= ?', $function_id) : 'IS NULL',
    );

    $medecins = $this->loadList($where);

    if (count($medecins) > 1) {
      $this->handleImportDoublon($medecins);
    }

    if ($medecins) {
      return $cache->put(reset($medecins));
    }

    return $this;
  }

  /**
   * Store the CMedecin duplicates in SHM
   *
   * @param array $medecins Array of duplicates CMedecin
   *
   * @return void
   */
  function handleImportDoublon($medecins) {
    $doublons = array();
    if (SHM::exists('CMedecin-doublons-import')) {
      $doublons = SHM::get('CMedecin-doublons-import');
    }

    $medecin = reset($medecins);
    $key     = sprintf("%s-%s-%s", $medecin->nom, $medecin->prenom, $medecin->type);

    if ($medecin->cp) {
      $key .= "-$medecin->cp";
    }

    if (isset($doublons[$key])) {
      foreach ($medecins as $_med) {
        if (!isset($doublons[$key][$_med->_id])) {
          $doublons[$key][$_med->_id] = true;
        }
      }
    }
    else {
      $doublons[$key] = array();
      foreach ($medecins as $_med) {
        $doublons[$key][$_med->_id] = true;
      }
    }

    SHM::put('CMedecin-doublons-import', $doublons);
  }

  /**
   * @inheritdoc
   */
  function getGeocodeFields() {
    return array(
      'adresse', 'cp', 'ville',
    );
  }

  function getAddress() {
    return $this->adresse;
  }

  function getZipCode() {
    return $this->cp;
  }

  function getCity() {
    return $this->ville;
  }

  function getCountry() {
    return null;
  }

  function getFullAddress() {
    return $this->getAddress() . ' ' . $this->getZipCode() . ' ' . $this->getCity() . ' ' . $this->getCountry();
  }

  /**
   * @inheritdoc
   */
  function loadRefGeolocalisation() {
    return $this->_ref_geolocalisation = $this->loadUniqueBackRef('geolocalisation');
  }

  /**
   * @inheritdoc
   */
  function createGeolocalisationObject() {

    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      $geo = new CGeoLocalisation();
      $geo->setObject($this);
      $geo->processed = '0';
      $geo->store();

      return $geo;
    }
    else {
      return $this->_ref_geolocalisation;
    }
  }

  /**
   * @inheritdoc
   */
  function getLatLng() {
    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      return null;
    }

    return $this->_ref_geolocalisation->lat_lng;
  }

  /**
   * @inheritdoc
   */
  function setLatLng($latlng) {
    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      return null;
    }

    $this->_ref_geolocalisation->lat_lng = $latlng;

    return $this->_ref_geolocalisation->store();
  }

  /**
   * @inheritdoc
   */
  static function isGeocodable() {
    return true;
  }

  /**
   * @inheritdoc
   */
  function getCommuneInsee() {
    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      return null;
    }

    return $this->_ref_geolocalisation->commune_insee;
  }

  /**
   * @inheritdoc
   */
  function setCommuneInsee($commune_insee) {
    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      return null;
    }

    $this->_ref_geolocalisation->commune_insee = $commune_insee;

    return $this->_ref_geolocalisation->store();
  }

  /**
   * @inheritdoc
   */
  function resetProcessed() {
    $this->loadRefGeolocalisation();

    if (!$this->_ref_geolocalisation || !$this->_ref_geolocalisation->_id) {
      return null;
    }

    $this->_ref_geolocalisation->processed = "0";

    return $this->_ref_geolocalisation->store();
  }

  function setProcessed(CGeoLocalisation $object = null) {
    if (!$object || !$object->_id) {
      $object = $this->loadRefGeolocalisation();
    }

    if (!$object || !$object->_id) {
      return null;
    }

    $object->processed = "1";

    return $object->store();
  }

  /**
   * @return bool
   */
  function isImporting() {
    return $this->_is_importing;
  }

  /**
   * Enable the importation state and do not put function_id on store
   *
   * @return void
   */
  function enableImporting() {
    $this->_is_importing = true;
  }

  /**
   * Disable the importation state and do not put function_id on store
   *
   * @return void
   */
  function disableImporting() {
    $this->_is_importing = false;
  }

  /**
   * @inheritDoc
   */
  public function matchForImport(MatcherVisitorInterface $matcher): ImportableInterface {
    return $matcher->matchMedecin($this);
  }

  /**
   * @inheritDoc
   */
  public function persistForImport(PersisterVisitorInterface $persister): ImportableInterface {
    return $persister->persistObject($this);
  }

  /**
   * @return array
   * @throws Exception
   */
  public function countByVersion(): array {
    $request = new CRequest();
    $request->addSelect(['import_file_version', 'COUNT(*) as total']);
    $request->addTable($this->_spec->table);
    $request->addGroup('import_file_version');
    $request->addOrder('total DESC');

    return $this->getDS()->loadList($request->makeSelect());
  }

  /**
   * @return array
   * @throws Exception
   */
  public function getSyncAvancement(): array {
    $versions = $this->countByVersion();
    $total = $this->countList();

    foreach ($versions as &$_version) {
      $_version['pct']   = ($total > 0) ? number_format(($_version['total'] / $total) * 100, 4, ',', ' ') : 0;
      $_version['total'] = number_format($_version['total'], 0, ',', ' ');
    }

    return [$versions, number_format($total, 0, ',', ' ')];
  }

    /**
     * @return CCollection|null
     * @throws CApiException
     * @throws Exception
     */
    public function getResourceMotifs(): ?CCollection
    {
        $where = [];

        // On retourne que les créneaux dispos et dans le futur
        if (!$motifs = $this->loadBackRefs('praticien_motif_consult', null, null, null, null, null, null, $where)) {
            return null;
        }

        $items = new CCollection($motifs);
        $items->setName(CAppFineMotifConsult::RESOURCE_NAME);

        return $items;
    }

    /**
     * @return CCollection|null
     * @throws CApiException
     * @throws Exception
     */
    public function getResourceAppointments(): ?CCollection
    {
        $appointment = new CAppFineAppointment();
        $default_where = [
            'unavailable' => " = '0'",
        ];

        // gestion des dates
        if (CDashboardController::$dates_appointments) {
            $start = CDashboardController::$dates_appointments['start'];
            if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $start)) {
                $start = CMbDT::dateTime(null, $start);
            }
            $end = CDashboardController::$dates_appointments['end'];
            if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $end)) {
                $end = CMbDT::dateTime("+23 HOURS +59 MINUTES +59 SECONDS", $end);
            }

            // prend max entre start & now si now e [start,end]
            if ($start < CMbDT::dateTime()) {
                $start = CMbDT::dateTime();
            }

            if ($end < CMbDT::dateTime()) {
                $end = CMbDT::dateTime("+23 HOURS +59 MINUTES +59 SECONDS", CMbDT::roundTime($start, CMbDT::ROUND_DAY));
            }

            $limit_date = "start_at " . $appointment->getDS()->prepareBetween($start, $end);
        } else {
            $default_where['start_at'] = " >= '" . CMbDT::dateTime() . "'";
        }

        if (CDashboardController::$teleconsultation_appointments) {
            $teleconsultation = CDashboardController::$teleconsultation_appointments['eligible_teleconsultation'];
            $default_where['eligible_teleconsultation'] = $teleconsultation ? " = '" . $teleconsultation . "'" : null;
        }

        // On retourne que les créneaux dispos et dans le futur
        $where   = array_merge($default_where, isset($limit_date) ? [$limit_date] : []);

        $appointments = $this->loadBackRefs('praticien_appointment', null, null, null, null, null, null, $where);
        if (!$appointments && CDashboardController::$dates_appointments) {
            $start = $start ?? CMbDT::dateTime();
            $where = array_merge($default_where, ['start_at' => $appointment->getDS()->prepare(' >= ?1', $start)]);
            /** @var CAppFineAppointment $firt_appointment */
            $firt_appointment = $this->loadFirstBackRef('praticien_appointment', 'start_at ASC', null, null, null, true, $where);
            if ($firt_appointment && $firt_appointment->_id) {
                $datetime = $firt_appointment->start_at;
                $datetime = CMbDT::daysIs($datetime) !== "Monday" ? CMbDT::dateTime('last monday', $datetime) : $datetime;
                // on prend le max entre le start et monday si datetime e [start,end]
                if ($datetime < $start && $datetime < $end) {
                    $datetime = $start;
                }
                $monday          = CMbDT::roundTime($datetime, CMbDT::ROUND_DAY);
                $relative_sunday = CMbDT::daysIs($monday) !== "Sunday" ? 'next Sunday ' : '';
                $sunday          = CMbDT::dateTime($relative_sunday . '+23 HOURS +59 MINUTES +59 SECONDS', $monday);

                $limit_date = "start_at " . $appointment->getDS()->prepareBetween($monday, $sunday);
                $where = array_merge($default_where, [$limit_date]);
                $appointments = $this->loadBackRefs('praticien_appointment', null, null, null, null, null, null, $where);
                if (!$appointments) {
                    return null;
                }
            }
        } elseif (!$appointments) {
            return null;
        }

        $items = new CCollection($appointments);
        $items->setName(CAppFineAppointment::RESOURCE_NAME);

        return $items;
    }

    /**
     * @return CItem|null
     * @throws CApiException
     * @throws Exception
     */
    public function getResourcePresentation(): ?CItem
    {
        $presentation = $this->loadUniqueBackRef('presentation');
        if (!$presentation || !$presentation->_id) {
            return null;
        }

        $items = new CItem($presentation);
        $items->setName(CPresentation::RESOURCE_NAME);

        return $items;
    }

    /**
     * @return CCollection|null
     * @throws Exception|CApiException
     */
    public function getResourceMedecinExercicePlace(): ?CCollection
    {
      $medecin_exercice_place             = new CMedecinExercicePlace();
      $medecin_exercice_place->medecin_id = $this->_id;

      $medecin_exercice_places = $medecin_exercice_place->loadMatchingList();

      if (empty($medecin_exercice_places)) {
        return null;
      }

      return new CCollection($medecin_exercice_places);
    }

    /**
     * @return CItem|null
     * @throws Exception|CApiException
     */
    public function getResourceContactPlace(): ?CItem
    {
      $contact_place               = new CContactPlace();
      $contact_place->object_id    = $this->_id;
      $contact_place->object_class = $this->_class;
      $contact_place->loadMatchingObject();

      if (!$contact_place || !$contact_place->_id) {
        return null;
      }

      return new CItem($contact_place);
    }

    /**
     * @return CCollection|null
     * @throws Exception|CApiException
     */
    public function getResourceSchedulePlace(): ?CCollection
    {
      $schedule_place               = new CSchedulePlace();
      $schedule_place->object_id    = $this->_id;
      $schedule_place->object_class = $this->_class;

      $schedule_places = $schedule_place->loadMatchingList();

      if (empty($schedule_places)) {
        return null;
      }

      return new CCollection($schedule_places);
    }

    /**
     * @return CCollection|null
     * @throws Exception|CApiException
     */
    public function getResourceTemporaryInformation(): ?CItem
    {
      $temporary_information               = new CTemporaryInformation();
      $temporary_information->object_id    = $this->_id;
      $temporary_information->object_class = $this->_class;
      $temporary_information->active       = true;
      $temporary_information->loadMatchingObject();

      if (!$temporary_information || !$temporary_information->_id) {
        return null;
      }

      return new CItem($temporary_information);
    }

    public function getExercicePlaces(): array
    {
        if (!$this->_id) {
            return [];
        }

        $medecin_exercice_places = $this->loadBackRefs('exercice_places');

        $exercice_places = CStoredObject::massLoadFwdRef($medecin_exercice_places, 'exercice_place_id');

        return is_array($exercice_places) ? array_unique($exercice_places) : [];
    }
}
