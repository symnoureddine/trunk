<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Tests;

use DOMElement;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Core\FieldSpecs\CBirthDateSpec;
use Ox\Core\FieldSpecs\CDateSpec;
use Ox\Core\FieldSpecs\CDateTimeSpec;
use Ox\Core\FieldSpecs\CTimeSpec;
use Ox\Core\Import\CMbXMLObjectImport;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Bloc\CSalle;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\Prescription\CCategoryPrescription;
use Ox\Mediboard\Prescription\CElementPrescription;
use Ox\Mediboard\Prescription\CPrescription;

/**
 * Functional test objects' import class
 */
class CTestXMLImport extends CMbXMLObjectImport {
  protected $imported = array();

  protected $import_order = array(
    // Structure objects
    "//object[@class='CGroups']",
    "//object[@class='CFunctions']",
    "//object[@class='CModule']",
    "//object[@class='CUser']",
    "//object[@class='CPermModule']",
    "//object[@class='CMediusers']",
    "//object[@class='CService']",
    "//object[@class='CSalle']",
    "//object[@class='CChambre']",
    "//object[@class='CLit']",

    "//object[@class='CPatient']",
    "//object[@class='CSejour']",
    "//object[@class='CGrossesse']",
    "//object[@class='COperation']",
    "//object[@class='CConsultation']",
    "//object[@class='CNaissance']",
    "//object[@class='CDossierPerinat']",
    "//object[@class='CCompteRendu']",
    "//object[@class='CCategoryPrescription']",
    "//object[@class='CFunctionCategoryPrescription']",
    "//object[@class='CPrescription']",
    "//object[@class='CElementPrescription']",
    "//object[@class='CTransport']",
    "//object[@class='CTransportTypeTransfert']",
    "//object[@class='CTransportModeSortie']",
    "//object",
  );

  static $_ignored_classes = array();

  /** @var CService $service */
  protected $service = null;

  /** @var CChambre $chambre */
  protected $chambre = null;

  /**
   * @inheritdoc
   * @throws TestsException
   */
  function importObject(DOMElement $element) {
    $id = $element->getAttribute("id");

    if (isset($this->imported[$id])) {
      return;
    }

    $error           = null;
    $imported_object = null;
    $object          = $this->getObjectFromElement($element);
    $this->mapExtraFields($object);
    $_class = $object->_class;

    switch ($_class) {
      // Structure import
      case "CGroups":
        /** @var CGroups $_group */
        $_group = CGroups::loadCurrent();
        if ($_group->_id) {
          $imported_object = $_group;
        }
        break;

      case "CService":
        $where             = array();
        $where['nom']      = "LIKE '%$object->nom%'";
        $group             = CGroups::loadCurrent();
        $where['group_id'] = "= $group->_id";

        if ($object->loadObject($where)) {
          $this->service   = $object;
          $imported_object = $object;
        }
        else {
          $this->store($object);
          $imported_object = $object;
        }
        break;

      case "CChambre":
        $where               = array();
        $where['nom']        = "LIKE '%$object->nom%'";
        $where['annule']     = "= '0'";
        $where['service_id'] = "= " . $this->service->_id;

        if ($object->loadObject($where)) {
          $this->chambre   = $object;
          $imported_object = $object;
        }
        break;

      case "CLit":
        $where               = array();
        $where['nom']        = "LIKE '%$object->nom%'";
        $where['chambre_id'] = "= " . $this->chambre->_id;
        $where['annule']     = "= '0'";

        if ($object->loadObject($where)) {
          $imported_object = $object;
        }
        break;

      case "CPatient":
        /** @var CPatient $object */
        $object->loadMatchingPatient();
        $object->civilite = 'guess';
        $this->store($object);
        $imported_object = $object;
        break;

      case "CSejour":
        $_collisions = $object->getCollisions();
        count($_collisions) ? $object = reset($_collisions) : $this->store($object);
        $imported_object = $object;
        break;

      case "COperation":
        /** @var COperation $_interv */
        $_interv = $object;
        $_ds     = $_interv->getDS();

        $where     = array(
          "sejour_id" => $_ds->prepare("= ?", $_interv->sejour_id),
          "chir_id"   => $_ds->prepare("= ?", $_interv->chir_id),
          "date"      => $_ds->prepare("= ?", $_interv->date),
        );
        $_matching = $_interv->loadList($where);

        count($_matching) ? $_interv = reset($_matching) : $this->store($_interv);
        $imported_object = $_interv;
        break;

      case "CGrossesse":
        /** @var CGrossesse $object */
        $object->date_dernieres_regles = CMbDT::date('-9 month');
        $object->loadMatchingObject();
        $this->store($object);
        $imported_object = $object;
        break;

        case 'CUser':
            $user = new CUser();
            $user->user_username = $object->user_username;
            $user->loadMatchingObjectEsc();

            if ($user && $user->_id) {
                $object = $user;
            }

            $this->store($object);
            $imported_object = $object;
            break;

      case "CMediusers":
        // Get CUser id which has already been imported
        /** @var CMediusers $object */
        $user_guid       = explode('-', $this->map[$object->user_id]);
        $user_id         = $user_guid[1];
        $object->user_id = null;

        $user = new CUser();
        $user->load($user_id);
        if ($user->loadRefMediuser()->_id) {
          $object = $user->_ref_mediuser;
        }
        else {
          $object->_guid   = "$_class-$user_id";
          $object->user_id = $user_id;

          $vars = $object->getPlainFields();
          $spec = $object->_spec;
          $spec->ds->insertObject($spec->table, $object, $vars);
        }

        $imported_object = $object;
        break;

      case "CCompteRendu":
        $modeles_ids     = array();
        $modele          = CCompteRendu::importModele($element, null, null, CGroups::loadCurrent()->_id, $modeles_ids);
        $imported_object = $modele;
        break;

      case "CElementPrescription":
        $cat      = new CCategoryPrescription();
        $cat->nom = "Catégorie";
        $cat->loadMatchingObject();

        /** @var CElementPrescription $object */
        if ($cat->_id) {
          $object->category_prescription_id = $cat->_id;
        }
        $this->store($object);
        $imported_object = $object;
        break;

      case "CPrescription":
        $imported     = 0;
        $message      = "";
        $prescription = CPrescription::importProtocole($element, null, null, CGroups::loadCurrent()->_id, $imported, $message);
        if ($prescription->_id) {
          $imported_object = $prescription;
        }
        break;

      case "CModeleEtiquette":
        $object->group_id = CGroups::get()->_id;
        $object->store();
        $imported_object = $object;
        break;

      case "CProtocoleRPU":
        $object->group_id = CGroups::get()->_id;

        if ($object->responsable_id === "selenium") {
          $user                   = new CUser();
          $user->user_last_name   = "CHIR";
          $user->user_first_name  = "Test";
          $object->responsable_id = $user->loadMatchingObject();
        }

        $object->store();
        $imported_object = $object;
        break;

      default:
        // Ignored classes
        if (in_array($_class, self::$_ignored_classes)) {
          break;
        }

        $object->loadMatchingObject();
        $this->store($object);
        $imported_object = $object;
        break;
    }

    if ($imported_object) {
      $this->map[$id]      = $imported_object->_guid;
      $this->imported[$id] = true;
    }
    else {
      throw new TestsException("Error during importation of object: `$id`");
    }
  }

  /**
   * Stores an object and log the message if failed
   *
   * @param CStoredObject $object Object to store
   *
   * @return bool
   */
  function store(CStoredObject &$object) {
    if ($object->_id || !($msg = $object->store())) {
      return true;
    }

    if (PHP_SAPI !== "cli") {
      CAppUI::stepAjax($msg, UI_MSG_ERROR);
    }
    $object = null;

    return false;
  }

  /**
   * Sets specific fields values according to xml
   *
   * @param CMbObject $object Object to import
   *
   * @return CMbObject
   */
  function mapExtraFields($object) {
    $this->manageObjectDate($object);

    // Handle custom field values
    switch ($object->_class) {
      case "CSejour":
        if ($object->praticien_id == "selenium") {
          $user                  = new CUser();
          $user->user_last_name  = "CHIR";
          $user->user_first_name = "Test";

          $user->loadMatchingObject();
          $object->praticien_id = $user->_id;
        }

        if ($object->patient_id == "selenium") {
          $patient         = new CPatient();
          $patient->nom    = "PATIENTLASTNAME";
          $patient->prenom = "Patientfirstname";
          $patient->loadMatchingObject();

          $object->patient_id = $patient->_id;
        }
        break;

      case 'CConstantesMedicales':
        // Disables the conversion of the constants with several units
        $object->_convert_value = false;
        break;

      case "CPlageOp":
        /** @var CPlageOp $object */
        if ($object->spec_id === "selenium") {
          $function       = new CFunctions();
          $function->text = "OX";

          $object->spec_id = $function->loadMatchingObject();
        }

        if (in_array($object->salle_id, array("salle1", "salle2"))) {
          $salle      = new CSalle();
          $salle->nom = $object->salle_id === "salle1" ? "NomSalle" : "NomSalle2";

          $object->salle_id = $salle->loadMatchingObject();
        }
        break;

      default:
        // Nothing to do
        break;
    }

    return $object;
  }

  /**
   * Manages object date, dateTime and time fields if 'now', 'now 12:00:00' or
   * 'now +1 hour' (works with any relative date) is provided in xml
   *
   * @param CStoredObject $object Object
   *
   * @return void
   */
  function manageObjectDate($object) {
    $plainFields = $object->getPlainFields();

    foreach ($plainFields as $_field => $_value) {
      $specs     = $object->getSpecs();
      $new_value = null;
      $relative  = null;

      if (strpos($_value, 'now') !== false) {
        $splitedValue = explode(' ', $_value, 2);

        if (count($splitedValue) > 1) {
          // 'now' with fixed time case
          if (is_numeric($splitedValue[1][0])) {
            $new_value = CMbDT::date() . " " . $splitedValue[1];
          }
          else {
            $relative = $splitedValue[1];
          }
        }

        if (!$new_value) {
          if ($specs[$_field] instanceof CDateSpec || $specs[$_field] instanceof CBirthDateSpec) {
            $new_value = CMbDT::date($relative);
          }
          elseif ($specs[$_field] instanceof CDateTimeSpec) {
            $new_value = CMbDT::dateTime($relative);
          }
          elseif ($specs[$_field] instanceof CTimeSpec) {
            $new_value = CMbDT::time($relative);
          }
        }

        $object->$_field = $new_value;
      }
    }
  }

  /**
   * @inheritdoc
   */
  function afterImport() {
    CAppUI::setMsg('system-msg-Import completed');
  }
}
