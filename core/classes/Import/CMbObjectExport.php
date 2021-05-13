<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Import;

use DOMElement;
use Exception;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLogger;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbPath;
use Ox\Core\CMbString;
use Ox\Core\CMbXMLDocument;
use Ox\Core\Module\CModule;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\CompteRendu\CWkhtmlToPDF;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\System\CContentHTML;
use Throwable;

/**
 * Object exporting utility class
 */
class CMbObjectExport {
  const DEFAULT_DEPTH = 20;

  /** @var CMbXMLDocument */
  public $doc;

  /** @var CMbObject */
  public $object;

  /** @var array */
  public $backrefs_tree;

  /** @var array */
  public $fwdrefs_tree;

  /** @var integer */
  public $depth = self::DEFAULT_DEPTH;

  /** @var bool */
  public $empty_values = true;

  /** @var callable Callback executed when object is exported */
  protected $object_callback;

  /** @var callable Callback executed when object is exported, which tells if the object is to be exported (returns a boolean) */
  protected $filter_callback;

  protected $hashs = [];

  public static $minimized_backrefs_tree = [
    "CPatient"                    => [
      "identifiants",
      "notes",
      "contantes",
      "correspondants",
      "correspondants_patient",
      "dossier_medical",
    ],
    "CDossierMedical"             => [
      "antecedents",
      "traitements",
      "prescriptions",
    ],
    "CPrescription"               => [
      "prescription_line_medicament",
      "prescription_line_element",
    ],
    "CPrescriptionLineMedicament" => [
      "prise_posologie",
    ],
    "CPrescriptionLineElement"    => [
      "prise_posologie",
    ],
  ];

  public static $prescription_backrefs_tree = [
    'CPatient'                    => [
      'sejours'
    ],
    'CSejour' => [
      'prescriptions'
    ],
    'CPrescription' => [
      'files',
      'documents'
    ]
  ];

  public static $default_backrefs_tree = [
    "CPatient"                    => [
      "identifiants",
      "notes",
      "files",
      "documents",
      "permissions",
      "observation_result_sets",
      "constantes",
      "contextes_constante",
      "consultations",
      "correspondants",
      "correspondants_patient",
      "sejours",
      "dossier_medical",
      "correspondants_courrier",
      "grossesses",
      "allaitements",
      "patient_observation_result_sets",
      "patient_links",
      'arret_travail',
      "facture_patient_consult",
      "facture_patient_sejour",
      "bmr_bhre",
    ],
    "CConsultation"               => [
      "files",
      "documents",
      "notes",
      "consult_anesth",
      "examaudio",
      "examcomp",
      "examnyha",
      "exampossum",
      "sejours_lies",
      "intervs_liees",
      "consults_liees",
      "prescriptions",
      "evenements_patient",

      // Codable
      "facturable",
      "actes_ngap",
      "actes_ccam",
      "codages_ccam",
      "actes_caisse",
    ],
    "CConsultAnesth"              => [
      "files",
      "documents",
      "notes",
      "techniques",
    ],
    "CSejour"                     => [
      "identifiants",
      "files",
      "documents",
      "notes",
      "dossier_medical",
      "operations",
      "consultations",

      // Codable
      "facturable",
      "actes_ngap",
      "actes_ccam",
      "codages_ccam",
      "actes_caisse",
    ],
    "COperation"                  => [
      "files",
      "documents",
      "notes",
      "anesth_perops",

      // Codable
      "facturable",
      "actes_ngap",
      "actes_ccam",
      "actes_caisse",
    ],
    "CCompteRendu"                => [
      "files",
    ],
    "CDossierMedical"             => [
      "antecedents",
      "traitements",
      "etats_dent",
      "prescriptions",
      "pathologies",
      'evenements_patient',
    ],
    "CFactureCabinet"             => [
      "items",
      "reglements",
      "relance_fact",
      "rejets",
      "envois_cdm",
      "facture_liaison",
    ],
    "CFactureEtablissement"       => [
      "items",
      "reglements",
      "relance_fact",
      "facture_liaison",
      "rejets",
      "envois_cdm",
    ],
    "CPrescription"               => [
      "prescription_line_medicament",
      "files",
    ],
    "CPrescriptionLineMedicament" => [
      "prise_posologie",
    ],
    "CEvenementPatient"           => [
      'files',
      'documents',
      'facturable',
    ],
  ];

  public static $minimized_fwrefs_tree = [
    "CPatient"                    => [
      "medecin_traitant",
    ],
    "CMediusers"                  => [
      "user_id",
    ],
    "CCorrespondant"              => [
      "patient_id",
      "medecin_id",
    ],
    "CPrescription"               => [
      "praticien_id",
      "function_id",
      "group_id",
    ],
    "CPrescriptionLineMedicament" => [
      "praticien_id",
      "creator_id",
      "extension_produit_unite_id",
    ],
    "CPrescriptionLineElement"    => [
      "praticien_id",
      "creator_id",
      "element_prescription_id",
    ],
    "CElementPrescription"        => [
      "category_prescription_id",
    ],
    "CPrisePosologie"             => [
      "moment_unitaire_id",
      "object_id",
    ],
    "CCategoryPrescription"       => [
      "group_id",
      "function_id",
      "user_id",
    ],
  ];

  public static $default_fwdrefs_tree = [
    "CPatient"              => [
      "medecin_traitant",
    ],
    "CConstantesMedicales"  => [
      "context_id",
      "patient_id",
      "user_id",
    ],
    "CConsultation"         => [
      "plageconsult_id",
      "sejour_id",
      "grossesse_id",
      "patient_id",
      "consult_related_id",
    ],
    "CConsultAnesth"        => [
      "consultation_id",
      "operation_id",
      "sejour_id",
      "chir_id",
    ],
    "CPlageconsult"         => [
      "chir_id",
    ],
    "CSejour"               => [
      "patient_id",
      "praticien_id",
      "service_id",
      "group_id",
      "grossesse_id",
      "uf_medicale_id",
      "uf_soins_id",
      "uf_hebergement_id",
    ],
    "COperation"            => [
      "sejour_id",
      "chir_id",
      "anesth_id",
      "plageop_id",
      "salle_id",
      "type_anesth",
      "consult_related_id",
      "prat_visite_anesth_id",
      "sortie_locker_id",
    ],
    "CGrossesse"            => [
      "group_id",
      "parturiente_id",
    ],
    "CCorrespondant"        => [
      "patient_id",
      "medecin_id",
    ],
    "CMediusers"            => [
      "user_id",
    ],
    "CPlageOp"              => [
      "chir_id",
      "anesth_id",
      "spec_id",
      "salle_id",
    ],

    // Actes
    "CActeCCAM"             => [
      "executant_id",
    ],
    "CActeNGAP"             => [
      "executant_id",
    ],
    "CActeCaisse"           => [
      "executant_id",
    ],
    "CFraisDivers"          => [
      "executant_id",
    ],
    // Fin Actes

    // Facturation
    "CFactureItem"          => [
      "object_id",
      "executant_id",
    ],
    "CFactureLiaison"       => [
      "facture_id",
      "object_id",
    ],
    "CFactureCabinet"       => [
      "group_id",
      "patient_id",
      "praticien_id",
      "coeff_id",
      "category_id",
      "assurance_maladie",
      "assurance_accident",
    ],
    "CFactureEtablissement" => [
      "group_id",
      "patient_id",
      "praticien_id",
      "coeff_id",
      "category_id",
      "assurance_maladie",
      "assurance_accident",
    ],
    "CReglement"            => [
      "banque_id",
      "object_id",
    ],
    "CFactureCategory"      => [
      "group_id",
      "function_id",
    ],
    "CFactureCoeff"         => [
      "praticien_id",
      "group_id",
    ],
    "CFactureRejet"         => [
      "praticien_id",
      "facture_id",
    ],
    "CRelance"              => [
      "object_id",
    ],
    "CRetrocession"         => [
      "praticien_id",
    ],
    // Fin facturation

    "CTypeAnesth"           => [
      "group_id",
    ],
    "CFile"                 => [
      "object_id",
      "author_id",
      "file_category_id",
    ],
    "CCompteRendu"          => [
      "object_id",
      "author_id",
      "file_category_id",

      "user_id",
      "function_id",
      "group_id",

      "content_id",

      "locker_id",
    ],
    "CPrisePosologie"       => [
      "object_id",
      "moment_unitaire_id",
    ],
    "CPathologie"           => [
      "owner_id",
    ],
    "CEvenementPatient"     => [
      "praticien_id",
      "owner_id",
      "traitement_user_id",
      "type_evenement_patient_id",
    ],
    "CTypeEvenementPatient" => [
      "function_id",
    ],
  ];

  public static $tarmed_fw_tree = [
    "CActeCaisse"       => [
      "caisse_maladie_id",
      "object_id",
      "executant_id",
    ],
    "CCaisseMaladie"    => [
      "function_id",
    ],
    "CActeTarmed"       => [
      "object_id",
      "executant_id",
    ],
    "CEnvoiCDM"         => [
      "group_id",
      "object_id",
    ],
    "CPrestationCaisse" => [
      "caisse_maladie_id",
    ],
  ];

  public static $tarmed_back_tree = [
    "CEnvoiCDM"      => [
      "messages_cdm",
    ],
    "CCaisseMaladie" => [
      "prestation_caisse_maladie",
    ],
    "CConsultation"  => [
      "actes_tarmed",
      "actes_caisse",
    ],
  ];

  public static $notif_back_tree = [
    'CTypeEvenementPatient' => [
      'notification',
    ],
  ];

  public static $notif_fw_tree = [
    "CNotificationEvent" => [
      "praticien_id",
      "group_id",
      "function_id",
      "object_id",
    ],
  ];

  /**
   * Trim no break space and 0xFF chars
   *
   * @param string $s String to trim
   *
   * @return string
   */
  static function trimString($s) {
    return trim(trim($s), "\xA0\xFF");
  }

  /**
   * Export constructor
   *
   * @param CMbObject  $object        Object to export
   * @param array|null $backrefs_tree Backrefs tree
   *
   * @throws CMbException
   */
  function __construct(CMbObject $object = null, $backrefs_tree = null) {
    if ($object) {
      if (!$object->getPerm(PERM_READ)) {
        throw new CMbException("Permission denied");
      }

      $this->object        = $object;
      $this->backrefs_tree = isset($backrefs_tree) ? $backrefs_tree : $object->getExportedBackRefs();
    }
  }

  /**
   * Callback exexuted on each object
   *
   * @param callable $callback The callback
   *
   * @return void
   */
  function setObjectCallback(callable $callback) {
    $this->object_callback = $callback;
  }

  /**
   * Callback exexuted on each object to tell if it has to be exported
   *
   * @param callable $callback The callback
   *
   * @return void
   */
  function setFilterCallback(callable $callback) {
    $this->filter_callback = $callback;
  }

  /**
   * Set the forward refs tree to export
   *
   * @param array $fwdrefs_tree Forward refs tree to export
   *
   * @return void
   */
  function setForwardRefsTree($fwdrefs_tree) {
    $this->fwdrefs_tree = $fwdrefs_tree;
  }

  /**
   * Export to DOM
   *
   * @return CMbXMLDocument
   */
  function toDOM() {
    $this->doc               = new CMbXMLDocument("utf-8");
    $this->doc->formatOutput = true;
    $root                    = $this->doc->createElement("mediboard-export");
    $root->setAttribute("date", CMbDT::dateTime());
    $root->setAttribute("root", $this->object->_guid);
    $this->doc->appendChild($root);

    $this->_toDOM($this->object, $this->depth);
    $this->hashToDOM();

    return $this->doc;
  }

  /**
   * Convert a list of object to DOM
   *
   * @param array $objects Objects to convert to DOM
   *
   * @return CMbXMLDocument
   */
  function objectListToDOM($objects) {
    $this->doc               = new CMbXMLDocument('utf-8');
    $this->doc->formatOutput = true;
    $root                    = $this->doc->createElement("mediboard-export");
    $root->setAttribute("date", CMbDT::dateTime());
    $root->setAttribute("root", $this->object->_guid);
    $this->doc->appendChild($root);

    foreach ($objects as $_obj) {
      $this->_toDOM($_obj, $this->depth);
    }

    return $this->doc;
  }

  /**
   * Append an object to the DOM
   *
   * @param CStoredObject $object Object to append
   *
   * @return DOMElement|null
   */
  function appendObject(CStoredObject $object) {
    if (!$object->_id) {
      $object->_id = "none";
    }

    return $this->_toDOM($object, 1);
  }

  /**
   * Internal DOM export method
   *
   * @param CStoredObject $object Object to export
   * @param int           $depth  Export depth
   *
   * @return DOMElement|null
   */
  protected function _toDOM(CStoredObject $object, $depth) {
    if ($depth == 0 || !$object->_id || !$object->getPerm(PERM_READ)) {
      return null;
    }

    if ($this->filter_callback && is_callable($this->filter_callback) && !call_user_func($this->filter_callback, $object)) {
      return null;
    }

    $doc         = $this->doc;
    $object_node = $doc->getElementById($object->_guid);

    // Objet deja exporté
    if ($object_node) {
      return $object_node;
    }

    $object_node = $doc->createElement("object");
    $object_node->setAttribute('class', $object->_class);
    $object_node->setAttribute('id', $object->_guid);
    $object_node->setIdAttribute('id', true);
    $doc->documentElement->appendChild($object_node);

    $db_fields = $object->getExportableFields();

    foreach ($db_fields as $key => $value) {
      // Forward Refs Fields
      $_fwd_spec = $object->_specs[$key];
      if ($_fwd_spec instanceof CRefSpec) {
        if ($key === $object->_spec->key && $object->_specs[$key]->className !== $object->_class) {
          continue;
        }

        if (!isset($this->fwdrefs_tree[$object->_class]) || !in_array($key, $this->fwdrefs_tree[$object->_class])) {
          continue;
        }

        $object->loadFwdRef($key);
        $guid    = "";
        $_object = $object->_fwd[$key];

        if ($_object && $_object->_id) {
          $this->_toDOM($_object, $depth - 1);

          $guid = $_object->_guid;
        }

        if ($this->empty_values || $guid) {
          $object_node->setAttribute($key, $guid);
        }
      }

      // Scalar fields
      else {
        $value = self::trimString($value);

        if ($this->empty_values || $value !== "") {
          if ($object instanceof CContentHTML && $key == 'content') {
            $value = str_replace('&', '&amp;', $value);
          }
          $doc->insertTextElement($object_node, "field", $value, ["name" => $key]);
        }
      }
    }

    if ($this->object_callback && is_callable($this->object_callback)) {
      call_user_func($this->object_callback, $object, $object_node, $depth);
    }

    // Collections
    if (!isset($this->backrefs_tree[$object->_class])) {
      return $object_node;
    }

    foreach ($object->_backProps as $backName => $backProp) {
      if (!in_array($backName, $this->backrefs_tree[$object->_class])) {
        continue;
      }

      $_backspec = $object->makeBackSpec($backName);

      // Add fwd ref field value for each object in the collection
      if ($_backspec) {
        $_class = $_backspec->class;
        if (!isset($this->fwdrefs_tree[$_class])) {
          $this->fwdrefs_tree[$_class] = [];
        }

        if (!array_key_exists($_backspec->field, $this->fwdrefs_tree[$_class])) {
          $this->fwdrefs_tree[$_class][] = $_backspec->field;
        }
      }

      $objects = $object->loadBackRefs($backName);

      if ($objects) {
        foreach ($objects as $_object) {
          $this->_toDOM($_object, $depth - 1);
        }
      }
    }

    return $object_node;
  }

  /**
   * Add a hash to the XML file
   *
   * @return void
   */
  function hashToDOM() {
    foreach ($this->hashs as $_hash_name => $_hash) {

      $object_node = $this->doc->createElement("hash");
      $object_node->setAttribute('hash_name', $_hash_name);
      $object_node->setAttribute('hash_value', $_hash);
      $this->doc->documentElement->appendChild($object_node);
    }
  }

  /**
   * Stream in text/xml mimetype
   *
   * @param bool $download Force download
   *
   * @return void
   */
  function streamXML($download = true) {
    $this->stream("text/xml", $download);
  }

  /**
   * Stream the DOM
   *
   * @param string $mimetype Mime type type
   * @param bool   $download Force download
   *
   * @return void
   */
  function stream($mimetype, $download = true) {
    $xml  = $this->toDOM()->saveXML();
    $date = CMbDT::dateTime();

    if ($download) {
      header("Content-Disposition: attachment;filename=\"{$this->object} - $date.xml\"");
    }

    header("Content-Type: $mimetype");
    header("Content-Length: " . strlen($xml));

    echo $xml;
  }

  /**
   * @param string $hash_name Hash name
   * @param string $hash      Hash
   *
   * @return void
   */
  function addHash($hash_name, $hash) {
    $this->hashs[$hash_name] = $hash;
  }

  /**
   * Get all the patients
   *
   * @param int   $start Start at
   * @param int   $step  Number of patients to get
   * @param array $order Order to retrieve patients
   *
   * @return array
   */
  public static function getAllPatients($start = 0, $step = 100, $order = null) {
      $patient = new CPatient();
      $ds      = $patient->getDS();

      $group = CGroups::loadCurrent();

      $ljoin_consult = [
          "consultation" => "consultation.patient_id = patients.patient_id",
          "plageconsult" => "plageconsult.plageconsult_id = consultation.plageconsult_id",
          'users_mediboard' => 'plageconsult.chir_id = users_mediboard.user_id',
          'functions_mediboard' => 'functions_mediboard.function_id = users_mediboard.user_id',
      ];

      $where_consult = [
          "consultation.annule" => " = '0'",
          'functions_mediboard.group_id' => $ds->prepare('= ?', $group->_id)
      ];


      $patient_ids_consult = $patient->loadIds($where_consult, $order, null, "patients.patient_id", $ljoin_consult);

      $ljoin_sejour = [
          "sejour" => "sejour.patient_id = patients.patient_id",
      ];

      $where_sejour                        = [
          "sejour.annule" => "= '0'",
          'sejour.group_id' => $ds->prepare('= ?', $group->_id)
      ];



      $patient_ids_sejour = $patient->loadIds($where_sejour, $order, null, "patients.patient_id", $ljoin_sejour);

      $patient_ids = array_merge($patient_ids_consult, $patient_ids_sejour);
      $patient_ids = array_unique($patient_ids);

      $total = count($patient_ids);

      if ($step) {
          $patient_ids = array_slice($patient_ids, $start, $step);
      }

      $where = [
          "patient_id" => $patient->getDS()->prepareIn($patient_ids),
      ];

      /** @var CPatient[] $patients */
      $patients = $patient->loadList($where);

      return [$patients, $total];
  }

  /**
   * Get all the patients to export
   *
   * @param array  $praticien_ids Praticiens ids to use
   * @param string $date_min      Minimum consult and sejour date
   * @param string $date_max      Maximum consult and sejour date
   * @param int    $start         Start at
   * @param int    $step          Number of patients to retrieve
   * @param array  $order         Order to get patients
   * @param string $type          Type to search for (consult or sejour)
   *
   * @return array
   */
  public static function getPatientsToExport(
    $praticien_ids, $date_min = null, $date_max = null, $start = 0, $step = null, $order = null, $type = null
  ) {
    $patient = new CPatient();
    $ds      = $patient->getDS();

    $patient_ids_consult = [];

    if (!$type || $type == 'consult') {
      $ljoin_consult = [
        "consultation" => "consultation.patient_id = patients.patient_id",
        "plageconsult" => "plageconsult.plageconsult_id = consultation.plageconsult_id",
      ];

      $where_consult = [];

      $where_consult["plageconsult.chir_id"] = $ds->prepareIn($praticien_ids);
      $where_consult["consultation.annule"]  = " = '0'";

      if ($date_min && $date_max) {
        $where_consult["plageconsult.date"] = $ds->prepare("BETWEEN ?1 AND ?2", $date_min, $date_max);
      }
      elseif ($date_min) {
        $where_consult["plageconsult.date"] = $ds->prepare(">= ?", $date_min);
      }
      elseif ($date_max) {
        $where_consult["plageconsult.date"] = $ds->prepare("<= ?", $date_max);
      }

      $patient_ids_consult = $patient->loadIds($where_consult, $order, null, "patients.patient_id", $ljoin_consult);
    }

    $patient_ids_sejour = [];

    if (!$type || $type == 'sejour') {
      $ljoin_sejour = [
        "sejour" => "sejour.patient_id = patients.patient_id",
      ];

      $where_sejour                        = [];
      $where_sejour["sejour.praticien_id"] = $ds->prepareIn($praticien_ids);
      $where_sejour["annule"]              = " = '0'";

      if ($date_min && $date_max) {
        $where_sejour["sejour.sortie"] = $ds->prepare("BETWEEN ?1 AND ?2", $date_min, $date_max);
      }
      elseif ($date_min) {
        $where_sejour["sejour.sortie"] = $ds->prepare(">= ?", $date_min);
      }
      elseif ($date_max) {
        $where_sejour["sejour.sortie"] = $ds->prepare("<= ?", $date_max);
      }

      $patient_ids_sejour = $patient->loadIds($where_sejour, $order, null, "patients.patient_id", $ljoin_sejour);
    }

    $patient_ids = array_merge($patient_ids_consult, $patient_ids_sejour);
    $patient_ids = array_unique($patient_ids);

    $total = count($patient_ids);

    if ($step) {
      $patient_ids = array_slice($patient_ids, $start, $step);
    }

    $where = [
      "patient_id" => $patient->getDS()->prepareIn($patient_ids),
    ];

    /** @var CPatient[] $patients */
    $patients = $patient->loadList($where);

    return [$patients, $total];
  }

  /**
   * Get all the praticiens from the current group
   *
   * @param array $types    Types to load
   * @param bool  $actif    Only active users or not
   * @param int   $permType Perm type
   *
   * @return CMediusers[]
   */
  public static function getPraticiensFromGroup($types = ["Chirurgien", "Anesthésiste", "Médecin", "Dentiste"], $actif = true,
                                                $permType = PERM_READ
  ) {
    $where = [];
    $ljoin = [];

    if ($actif) {
      $where["users_mediboard.actif"] = "= '1'";
    }

    // Filters on users values
    $ljoin["users"] = "`users`.`user_id` = `users_mediboard`.`user_id`";

    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";

    $group_id = CGroups::loadCurrent()->_id;
    $where[]  = "functions_mediboard.group_id = '$group_id'";

    // Filter on user type
    if (is_array($types)) {
      $utypes_flip = array_flip(CUser::$types);
      foreach ($types as &$_type) {
        $_type = $utypes_flip[$_type];
      }

      $where["users.user_type"] = CSQLDataSource::prepareIn($types);
    }

    $order    = "`users`.`user_last_name`, `users`.`user_first_name`";
    $group_by = ["user_id"];

    // Get all users
    $mediuser = new CMediusers();
    /** @var CMediusers[] $mediusers */
    $mediusers = $mediuser->loadList($where, $order, null, $group_by, $ljoin);

    // Mass fonction standard preloading
    CStoredObject::massLoadFwdRef($mediusers, "function_id");

    // Filter a posteriori to unable mass preloading of function
    CStoredObject::filterByPerm($mediusers, $permType);

    // Associate cached function
    foreach ($mediusers as $_mediuser) {
      $_mediuser->loadRefFunction();
    }

    return $mediusers;
  }

  /**
   * Get all the patients to export using users' functions
   *
   * @param array $praticien_ids Users ids to use for functions
   * @param int   $start         Start at
   * @param int   $step          Number of patients to retrieve
   *
   * @return array
   */
  public static function getPatientToExportFunction($praticien_ids, $start = 0, $step = 100) {
    $patient = new CPatient();
    $ds      = $patient->getDS();

    $query = new CRequest();
    $query->addSelect('P.patient_id');
    $query->addTable(['patients P', 'users_mediboard M']);
    $query->addWhere(
      [
        'P.function_id' => '= M.function_id',
        'M.user_id'     => $ds->prepareIn($praticien_ids),
      ]
    );

    $ids     = $ds->loadList($query->makeSelect());
    $all_ids = CMbArray::pluck($ids, 'patient_id');

    $patient_total = ($all_ids) ? count($all_ids) : 0;

    $patient  = new CPatient();
    $where    = ['patient_id' => $ds->prepareIn($all_ids)];
    $patients = $patient->loadList($where, 'patient_id ASC', "$start,$step");

    return [
      $patients,
      $patient_total,
    ];
  }

  /**
   * Callback to filter objects to export
   *
   * @param CStoredObject $object          Object to check
   * @param string        $date_min        Date min to filter
   * @param string        $date_max        Date max to filter
   * @param array         $praticiens_ids  Praticiens ids to filter
   * @param array         $ignored_classes Classes to ignore for the export
   *
   * @return bool
   */
  public static function exportFilterCallback(CStoredObject $object, $date_min, $date_max, $praticiens_ids,
                                              $ignored_classes = []
  ) {
    if (in_array($object->_class, $ignored_classes)) {
      return false;
    }

    return $object->isExportable($praticiens_ids, $date_min, $date_max);
  }

  /**
   * Callback to do actions on certain classes
   *
   * @param CStoredObject $object               Object to filter
   * @param string        $dir                  Export directory
   * @param bool          $generate_pdfpreviews Generate files previews
   * @param bool          $ignore_files         Ignore files
   * @param bool          $archive_sejour       Make PDF archive for sejours
   * @param bool          $zip_files            Zip the CSejour print
   * @param bool          $archive_mode         Archive the data, make timeline and synthese_med to PDF
   *
   * @return int
   */
  public static function exportCallBack(CStoredObject $object, $dir, $generate_pdfpreviews = true, $ignore_files = false,
                                        $archive_sejour = false, $zip_files = false, $archive_mode = false
  ) {
    switch ($object->_class) {
      case "CPatient":
        if ($archive_mode) {
          $exp_dir = "$dir/" . CMbString::removeDiacritics(CAppUI::tr($object->_class)) . "/"
            . preg_replace('/\W+/', '_', CMbString::removeDiacritics($object->_view));

          if (CModule::getActive("oxCabinet")) {
            if (!is_dir($exp_dir)) {
              CMbPath::forceDir($exp_dir);
            }

            // Impression timeline
            $query = [
              "m"                     => "oxCabinet",
              "dialog"                => "ajax_print_global",
              "patient_id"            => $object->_id,
              "patient_event_type_id" => null,
              "print"                 => 1,
              "categories_names"      => [
                "allergie", "antecedents", "constantes", "consultations", "documents", "evenements", "formulaires", "infogroup",
                "laboratoire", "naissance", "ordonnances", "pathologie", "rosp", "traitements",
              ],
              "archive"               => 1,
              "archive_path"          => $exp_dir,
            ];

            CApp::fetchQuery($query);

            // Synthèse medicale
            $query = [
              [
                "m"          => "oxCabinet",
                "a"          => "vw_synthese_medicale",
                "patient_id" => $object->_id,
                "dialog"     => 1,
              ],
            ];

            $pdf = CWkhtmlToPDF::makePDF(null, null, $query, "A4", "Portrait", "screen", false);
            file_put_contents($exp_dir . "/synthese_medicale.pdf", $pdf);
          }
          else {
            // Fiche patient
            $query = [
              [
                "m"          => "dPpatients",
                "a"          => "print_patient",
                "patient_id" => $object->_id,
                "dialog"     => 1,
              ],
            ];

            $pdf = CWkhtmlToPDF::makePDF(null, null, $query, "A4", "Portrait", "screen", false);
            file_put_contents($exp_dir . "/Fiche patient.pdf", $pdf);
          }
        }
        break;
      case "CCompteRendu":
        /** @var CCompteRendu $object */
        if ($generate_pdfpreviews) {
          try {
            $object->makePDFpreview(true);
          }
          catch (Throwable $e) {
            CApp::log($e->getMessage(), null, CLogger::LEVEL_WARNING);
            return 0;
          }

          if ($object->_ref_file && $object->_ref_file->_id) {
            return $object->_ref_file->doc_size;
          }
        }
        break;

      case "CFile":
        if ($ignore_files) {
          break;
        }

        /** @var CFile $object */
        if (class_exists("CPrescription") && $object->object_class == 'CPrescription') {
          /** @var CPrescription $presc */
          $presc      = $object->loadTargetObject();
          $new_target = $presc->loadRefObject();

          $object->setObject($new_target);
        }

        if ($archive_mode) {
          $target    = $object->loadTargetObject();
          $_dir      = "$dir/" . CMbString::removeDiacritics(CAppUI::tr($object->object_class)) . "/" . preg_replace('/\W+/', '_', CMbString::removeDiacritics($target->_view));
          $file_name = utf8_encode($object->file_name);
        }
        else {
          $_dir      = "$dir/$object->object_class/$object->object_id";
          $file_name = $object->file_real_filename;
        }

        CMbPath::forceDir($_dir);

        file_put_contents($_dir . "/" . $file_name, @$object->getBinaryContent());

        return $object->doc_size;
        break;

      case "CSejour":
        if ($ignore_files || !$archive_sejour) {
          break;
        }

        CView::disableSlave();
        /** @var CSejour $object */
        static::archiveSejour($object, $zip_files);
        CView::enforceSlave();
        break;

      default:
        // Do nothing
    }

    return 0;
  }

  /**
   * @param CSejour $sejour   Sejour to create archive for
   * @param bool    $zip_file Zip the generated file
   *
   * @return void
   */
  static function archiveSejour($sejour, $zip_file = false) {
    try {
      $sejour->makePDFarchive("Dossier complet", true, $zip_file, false);
    }
    catch (Exception $e) {
      CApp::log($e->getMessage(), null, CLogger::LEVEL_WARNING);
    }


    if (CModule::getActive("dPprescription")) {
      $prescriptions = $sejour->loadRefsPrescriptions();

      foreach ($prescriptions as $_type => $_prescription) {
        if ($_prescription->_id && in_array($_type, ["pre_admission", "sortie"])) {
          if ($_prescription->countBackRefs("prescription_line_medicament") > 0
            || $_prescription->countBackRefs("prescription_line_element") > 0
            || $_prescription->countBackRefs("prescription_line_comment") > 0
            || $_prescription->countBackRefs("prescription_line_mix") > 0
            || $_prescription->countBackRefs("administration_dm") > 0
          ) {
            $query = [
              "m"               => "prescription",
              "raw"             => "print_prescription",
              "prescription_id" => $_prescription->_id,
              "dci"             => 0,
              "in_progress"     => 0,
              "preview"         => 0,
            ];

            $base = $_SERVER["SCRIPT_NAME"] . "?" . http_build_query($query, "", "&");

            CApp::serverCall("http://127.0.0.1$base");

            CAppUI::stepAjax("Archive créée pour la prescription de %s", UI_MSG_OK, CAppUI::tr("CPrescription.type.$_type"));
          }
        }
      }
    }
  }
}
