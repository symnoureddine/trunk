<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Xds\Structure;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\InteropResources\CInteropResources;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Génération du value set ContentTypeCode pour XDS
 */
class CXDSValueSet implements IShortNameAutoloadable {
  static $type = "XDS";

  static $JDV = array(
    "practiceSettingCode"        => "practiceSettingCode",
    "classCode"                  => "classCode",
    "typeCode"                   => "JDV_J07-XdsTypeCode-CISIS",// "typeCode",
    "healthcareFacilityTypeCode" => "healthcareFacilityTypeCode",
    "contentTypeCode"            => "contentTypeCode",
    "formatCode"                 => "formatCode",
    "confidentialityCode"        => "JDV_J08-XdsConfidentialityCode-CISIS",
  );

  /**
   * Get factory
   *
   * @param string $xds_type XDS receiver type
   *
   * @return CXDSValueSet|CDMPValueSet
   */
  static function getFactory($xds_type) {
    $factory = new self;
    if ($xds_type == "DMP" && CModule::getActive("dmp")) {
      $factory = new CDMPValueSet();
    }

    return $factory;
  }

  /**
   * Get content type code value set
   *
   * @param CMbObject $object Object
   *
   * @return array
   * @throws Exception
   */
  function getContentTypeCode(CMbObject $object) {
    // @todo faire évoluer si besoin
    return CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "contentTypeCode"), "11488-4", self::$type);
  }

  /**
   * Get class code value set
   *
   * @param string $code Code
   *
   * @return array
   * @throws Exception
   */
  function getClassCode($code = null) {
    // @todo faire évoluer si besoin
    $code = "51851-4";

    $entry = CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "classCode"), $code, self::$type);

    return array($entry["code"], $entry["codeSystem"], $entry["displayName"]);
  }

  /**
   * Get healthcare facility type code
   *
   * @param bool $array_unique Is unique array ?
   *
   * @return array
   */
  function getHealthcareFacilityTypeCodeEntries($array_unique = false) {
    return CInteropResources::loadJV(CMbArray::get(self::$JDV, "healthcareFacilityTypeCode"), self::$type, $array_unique);
  }

  /**
   * Get healthcare facility type code
   *
   * @param CGroups $group Group
   *
   * @return array
   * @throws Exception
   */
  static function getHealthcareFacilityTypeCode(CGroups $group) {
    return CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "healthcareFacilityTypeCode"), CIdSante400::getValueFor($group, "xds_association_code"), self::$type);
  }

  /**
   * Get confidentiality code
   *
   * @param string $confidentialite Confidentiality
   *
   * @return array
   * @throws Exception
   */
  static function getConfidentialityCode($confidentialite = "N") {
    return CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "confidentialityCode"), $confidentialite, self::$type);
  }

  /**
   * Get practice setting code
   *
   * @return array
   * @throws Exception
   */
  static function getPracticeSettingCode() {
    // @todo faire évoluer si besoin
    return CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "practiceSettingCode"), "General Medicine", self::$type);
  }

  /**
   * Get confidentiality code
   *
   * @param string $type Document type
   *
   * @return array
   * @throws Exception
   */
  static function getTypeCode($type) {
    if (!$type) {
      $type = "11488-4";
    }

    return CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "typeCode"), $type, self::$type);
  }

  /**
   * Get format code
   *
   * @param string $mediaType  Media type
   * @param string $templateId Template ID
   *
   * @return array
   * @throws Exception
   */
  function getFormatCode($mediaType, $templateId) {
    $code = "urn:ihe:iti:xds-sd:text:2008";

    $entry = CInteropResources::loadEntryJV(CMbArray::get(self::$JDV, "formatCode"), $code, self::$type);

    return array(
      "codingScheme" => CMbArray::get($entry, "codeSystem"),
      "name"         => CMbArray::get($entry, "displayName"),
      "formatCode"   => CMbArray::get($entry, "code")
    );
  }
}
