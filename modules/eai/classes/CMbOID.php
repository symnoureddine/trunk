<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Interop\Hl7\CExchangeHL7v3;
use Ox\Interop\Xds\Structure\CXDSRegistryPackage;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Management of OID
 */
class CMbOID implements IShortNameAutoloadable {

  static $delimiter     = "1";
  static $class_mappage = array(
    "CCompteRendu" => "1", "CFile" => "2", "CPatient" => "3",
    "CMediusers" => "4", "CGroups" => "5", "CXDSRegistryPackage" => "6",
    "CExchangeHL7v3" => "7");

  /**
   * Return the instance OID
   *
   * @param CInteropReceiver $receiver Receiver
   *
   * @return String
   */
  static function getOIDRoot($receiver = null) {
    if ($receiver) {
      $receiver->loadConfigValues();
    }

    if ($receiver && $receiver->_configs["use_receiver_oid"]) {
      return $receiver->OID;
    }

    return CAppUI::conf("mb_oid");
  }

  /**
   * Return the instance OID
   *
   * @param CMbObject        $class         Class
   * @param CInteropReceiver $receiver      Receiver
   * @param bool             $only_oid_root Only OID root
   *
   * @return string
   */
  static function getOIDOfInstance($class, $receiver = null, $only_oid_root = false) {
    $oid_root  = self::getOIDRoot($receiver);
    if ($only_oid_root) {
      return $oid_root;
    }

    $delimiter = self::$delimiter;
    $oid_group = self::getGroupId($class);

    return $oid_root.".".$delimiter.".".$oid_group;
  }

  /**
   * Return the group Id
   *
   * @param CMbObject $class Class
   *
   * @return string
   */
  static function getGroupId($class) {
    $object = null;
    $result = null;
    if ($class instanceof CFile || $class instanceof CCompteRendu) {
      /** @var CCompteRendu|CFile $class */
      $class = $object = $class->loadTargetObject();
    }
    if ($class instanceof CConsultAnesth) {
      /** @var CConsultAnesth $class */
      $class = $class->loadRefConsultation();
    }

    switch (get_class($class)) {
      case CMediusers::class:
        /** @var CMediusers $class */
        $result = $class->_group_id;
        break;
      case CSejour::class:
        /** @var CSejour $class */
        $result = $class->group_id;
        break;
      case COperation::class:
        /** @var COperation $class */
        $result = $class->loadRefSejour()->group_id;
        break;
      case CConsultAnesth::class:
        /** @var CConsultAnesth $class */
        $result = $class->loadRefConsultation()->loadRefGroup()->group_id;
        break;
      case CConsultation::class;
        /** @var CConsultation $class */
        $result = $class->loadRefGroup()->group_id;
        break;
      case CPatient::class:
        /** @var CPatient $class */
        $result = "0";
        break;
      case CGroups::class:
        /** @var CGroups $class */
        $result = $class->_id;
        break;
      case CXDSRegistryPackage::class:
        /** @var CXDSRegistryPackage $class */
        $result = $class->_group_id;
        break;
      case CExchangeHL7v3::class:
        /** @var CExchangeHL7v3 $class */
        $result = $class->group_id;
      default:
    }

    return $result;
  }

  /**
   * Return the class OID
   *
   * @param CMbObject        $class         Class
   * @param CInteropReceiver $receiver      Receiver
   * @param bool             $only_oid_root Only OID root
   *
   * @return string
   */
  static function getOIDFromClass($class, $receiver = null, $only_oid_root = false) {
    $oid_instance = self::getOIDOfInstance($class, $receiver, $only_oid_root);

    $delimiter    = self::$delimiter;
    $oid          = self::$class_mappage[$class->_class];

    return $oid_instance.".".$delimiter.".".$oid;
  }
}