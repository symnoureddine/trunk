<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * Mailing and home or office addresses. A sequence of
 * address parts, such as street or post office Box, city,
 * postal code, country, etc.
 *
 * @property $delimiter
 * @property $country
 * @property $state
 * @property $county
 * @property $city
 * @property $postalCode
 * @property $streetAddressLine
 * @property $houseNumber
 * @property $houseNumberNumeric
 * @property $direction
 * @property $streetName
 * @property $streetNameBase
 * @property $streetNameType
 * @property $additionalLocator
 * @property $unitID
 * @property $unitType
 * @property $careOf
 * @property $censusTract
 * @property $deliveryAddressLine
 * @property $deliveryInstallationType
 * @property $deliveryInstallationArea
 * @property $deliveryInstallationQualifier
 * @property $deliveryMode
 * @property $deliveryModeIdentifier
 * @property $buildingNumberSuffix
 * @property $postBox
 * @property $precinct
 * @property $useablePeriod
 */
class CCDAAD extends CCDAANY {

  public $delimiter                     = array();
  public $country                       = array();
  public $state                         = array();
  public $county                        = array();
  public $city                          = array();
  public $postalCode                    = array();
  public $streetAddressLine             = array();
  public $houseNumber                   = array();
  public $houseNumberNumeric            = array();
  public $direction                     = array();
  public $streetName                    = array();
  public $streetNameBase                = array();
  public $streetNameType                = array();
  public $additionalLocator             = array();
  public $unitID                        = array();
  public $unitType                      = array();
  public $careOf                        = array();
  public $censusTract                   = array();
  public $deliveryAddressLine           = array();
  public $deliveryInstallationType      = array();
  public $deliveryInstallationArea      = array();
  public $deliveryInstallationQualifier = array();
  public $deliveryMode                  = array();
  public $deliveryModeIdentifier        = array();
  public $buildingNumberSuffix          = array();
  public $postBox                       = array();
  public $precinct                      = array();

  /**
   * A General Timing Specification (GTS) specifying the
   * periods of time during which the address can be used.
   * This is used to specify different addresses for
   * different times of the year or to refer to historical
   * addresses.
   *
   * @var array
   */
  public $useablePeriod = array();

  /**
   * A set of codes advising a system or user which address
   * in a set of like addresses to select for a given purpose.
   *
   * @var CCDAset_PostalAddressUse
   */
  public $use;

  /**
   * A boolean value specifying whether the order of the
   * address parts is known or not. While the address parts
   * are always a Sequence, the order in which they are
   * presented may or may not be known. Where this matters, the
   * isNotOrdered property can be used to convey this
   * information.
   *
   * @var CCDA_base_bl
   */
  public $isNotOrdered;

  /**
   * Setter isNotOrdered
   *
   * @param String $isNotOrdered String
   *
   * @return void
   */
  public function setIsNotOrdered($isNotOrdered) {
    if (!$isNotOrdered) {
      $this->isNotOrdered = null;
      return;
    }
    $isNotOrd = new CCDA_base_bl;
    $isNotOrd->setData($isNotOrdered);
    $this->isNotOrdered = $isNotOrd;
  }

  /**
   * Getter isNotOrdered
   *
   * @return CCDA_base_bl
   */
  public function getIsNotOrdered() {
    return $this->isNotOrdered;
  }

  /**
   * Setter use
   *
   * @param String[] $use String[]
   *
   * @return void
   */
  public function setUse($use) {
    $setPost = new CCDAset_PostalAddressUse();
    foreach ($use as $_use) {
      $setPost->addData($_use);
    }
    $this->use = $setPost;
  }

  /**
   * Getter use
   *
   * @return CCDAset_PostalAddressUse
   */
  public function getUse() {
    return $this->use;
  }

  /**
   * Ajoute l'instance dans le champ spécifié
   *
   * @param String $name  String
   * @param mixed  $value mixed
   *
   * @return void
   */
  function append($name, $value) {
    array_push($this->$name, $value);
  }

  /**
   * retourne le tableau du champ spécifié
   *
   * @param String $name String
   *
   * @return mixed
   */
  function get($name) {
    return $this->$name;
  }

  /**
   * Efface le tableau du champ spécifié
   *
   * @param String $name String
   *
   * @return void
   */
  function resetListdata($name) {
    $this->$name = array();
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["delimiter"] = "CCDAadxp_delimiter xml|element";
    $props["country"] = "CCDAadxp_country xml|element";
    $props["state"] = "CCDAadxp_state xml|element";
    $props["county"] = "CCDAadxp_county xml|element";
    $props["city"] = "CCDAadxp_city xml|element";
    $props["postalCode"] = "CCDAadxp_postalCode xml|element";
    $props["streetAddressLine"] = "CCDAadxp_streetAddressLine xml|element";
    $props["houseNumber"] = "CCDAadxp_houseNumber xml|element";
    $props["houseNumberNumeric"] = "CCDAadxp_houseNumberNumeric xml|element";
    $props["direction"] = "CCDAadxp_direction xml|element";
    $props["streetName"] = "CCDAadxp_streetName xml|element";
    $props["streetNameBase"] = "CCDAadxp_streetNameBase xml|element";
    $props["streetNameType"] = "CCDAadxp_streetNameType xml|element";
    $props["additionalLocator"] = "CCDAadxp_additionalLocator xml|element";
    $props["unitID"] = "CCDAadxp_unitID xml|element";
    $props["unitType"] = "CCDAadxp_unitType xml|element";
    $props["careOf"] = "CCDAadxp_careOf xml|element";
    $props["censusTract"] = "CCDAadxp_censusTract xml|element";
    $props["deliveryAddressLine"] = "CCDAadxp_deliveryAddressLine xml|element";
    $props["deliveryInstallationType"] = "CCDAadxp_deliveryInstallationType xml|element";
    $props["deliveryInstallationArea"] = "CCDAadxp_deliveryInstallationArea xml|element";
    $props["deliveryInstallationQualifier"] = "CCDAadxp_deliveryInstallationQualifier xml|element";
    $props["deliveryMode"] = "CCDAadxp_deliveryMode xml|element";
    $props["deliveryModeIdentifier"] = "CCDAadxp_deliveryModeIdentifier xml|element";
    $props["buildingNumberSuffix"] = "CCDAadxp_buildingNumberSuffix xml|element";
    $props["postBox"] = "CCDAadxp_postBox xml|element";
    $props["precinct"] = "CCDAadxp_precinct xml|element xml|element";
    $props["useablePeriod"] = "CCDASXCM_TS xml|element";
    $props["use"] = "CCDAset_PostalAddressUse xml|attribute";
    $props["isNotOrdered"] = "CCDA_base_bl xml|attribute";
    $props["data"] = "str xml|data";
    return $props;
  }

  /**
   * Fonction permettant de tester la validité de la classe
   *
   * @return array()
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec des données
     */
    $this->setData("test");
    $tabTest[] = $this->sample("Test avec des données", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec use incorrect
     */
    $this->setUse(array("TESTTEST"));

    $tabTest[] = $this->sample("Test avec un use incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec use correct
     */
    $this->setUse(array("TMP"));

    $tabTest[] = $this->sample("Test avec un use correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec isNotOrdered incorrecte
     */

    $this->setIsNotOrdered("TESTTEST");

    $tabTest[] = $this->sample("Test avec un isNotOrdered incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec isNotOrdered correcte
     */

    $this->setIsNotOrdered("true");

    $tabTest[] = $this->sample("Test avec un isNotOrdered correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec delimiter correcte
     */

    $adxp = new CCDA_adxp_delimiter();
    $this->append("delimiter", $adxp);
    $tabTest[] = $this->sample("Test avec un delimiter correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec delimiter correcte
     */

    $adxp = new CCDA_adxp_delimiter();
    $this->append("delimiter", $adxp);
    $tabTest[] = $this->sample("Test avec deux delimiter correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec country correcte
     */

    $adxp = new CCDA_adxp_country();
    $this->append("country", $adxp);
    $tabTest[] = $this->sample("Test avec un country correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec state correcte
     */

    $adxp = new CCDA_adxp_state();
    $this->append("state", $adxp);
    $tabTest[] = $this->sample("Test avec un state correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec county correcte
     */

    $adxp = new CCDA_adxp_county();
    $this->append("county", $adxp);
    $tabTest[] = $this->sample("Test avec un county correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec city correcte
     */

    $adxp = new CCDA_adxp_city();
    $this->append("city", $adxp);
    $tabTest[] = $this->sample("Test avec un city correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec postalCode correcte
     */

    $adxp = new CCDA_adxp_postalCode();
    $this->append("postalCode", $adxp);
    $tabTest[] = $this->sample("Test avec un postalCode correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec streetAddressLine correcte
     */

    $adxp = new CCDA_adxp_streetAddressLine();
    $this->append("streetAddressLine", $adxp);
    $tabTest[] = $this->sample("Test avec un streetAddressLine correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec houseNumber correcte
     */

    $adxp = new CCDA_adxp_houseNumber();
    $this->append("houseNumber", $adxp);
    $tabTest[] = $this->sample("Test avec un houseNumber correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec houseNumberNumeric correcte
     */

    $adxp = new CCDA_adxp_houseNumberNumeric();
    $this->append("houseNumberNumeric", $adxp);
    $tabTest[] = $this->sample("Test avec un houseNumberNumeric correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec direction correcte
     */

    $adxp = new CCDA_adxp_direction();
    $this->append("direction", $adxp);
    $tabTest[] = $this->sample("Test avec un direction correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec streetName correcte
     */

    $adxp = new CCDA_adxp_streetName();
    $this->append("streetName", $adxp);
    $tabTest[] = $this->sample("Test avec un streetName correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec streetNameBase correcte
     */

    $adxp = new CCDA_adxp_streetNameBase();
    $this->append("streetNameBase", $adxp);
    $tabTest[] = $this->sample("Test avec un streetNameBase correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec streetNameType correcte
     */

    $adxp = new CCDA_adxp_streetNameType();
    $this->append("streetNameType", $adxp);
    $tabTest[] = $this->sample("Test avec un streetNameType correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec additionalLocator correcte
     */

    $adxp = new CCDA_adxp_additionalLocator();
    $this->append("additionalLocator", $adxp);
    $tabTest[] = $this->sample("Test avec un additionalLocator correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec unitID correcte
     */

    $adxp = new CCDA_adxp_unitID();
    $this->append("unitID", $adxp);
    $tabTest[] = $this->sample("Test avec un unitID correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec unitType correcte
     */

    $adxp = new CCDA_adxp_unitType();
    $this->append("unitType", $adxp);
    $tabTest[] = $this->sample("Test avec un unitType correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec careOf correcte
     */

    $adxp = new CCDA_adxp_careOf();
    $this->append("careOf", $adxp);
    $tabTest[] = $this->sample("Test avec un careOf correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec censusTract correcte
     */

    $adxp = new CCDA_adxp_censusTract();
    $this->append("censusTract", $adxp);
    $tabTest[] = $this->sample("Test avec un censusTract correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryAddressLine correcte
     */

    $adxp = new CCDA_adxp_deliveryAddressLine();
    $this->append("deliveryAddressLine", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryAddressLine correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryInstallationType correcte
     */

    $adxp = new CCDA_adxp_deliveryInstallationType();
    $this->append("deliveryInstallationType", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryInstallationType correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryInstallationArea correcte
     */

    $adxp = new CCDA_adxp_deliveryInstallationArea();
    $this->append("deliveryInstallationArea", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryInstallationArea correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryInstallationQualifier correcte
     */

    $adxp = new CCDA_adxp_deliveryInstallationQualifier();
    $this->append("deliveryInstallationQualifier", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryInstallationQualifier correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryMode correcte
     */

    $adxp = new CCDA_adxp_deliveryMode();
    $this->append("deliveryMode", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryMode correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deliveryModeIdentifier correcte
     */

    $adxp = new CCDA_adxp_deliveryModeIdentifier();
    $this->append("deliveryModeIdentifier", $adxp);
    $tabTest[] = $this->sample("Test avec un deliveryModeIdentifier correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec buildingNumberSuffix correcte
     */

    $adxp = new CCDA_adxp_buildingNumberSuffix();
    $this->append("buildingNumberSuffix", $adxp);
    $tabTest[] = $this->sample("Test avec un buildingNumberSuffix correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec postBox correcte
     */

    $adxp = new CCDA_adxp_postBox();
    $this->append("postBox", $adxp);
    $tabTest[] = $this->sample("Test avec un postBox correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec precinct correcte
     */

    $adxp = new CCDA_adxp_precinct();
    $this->append("precinct", $adxp);
    $tabTest[] = $this->sample("Test avec un precinct correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec useablePeriod incorrecte
     */

    $useable = new CCDASXCM_TS();
    $useable->setValue("TESTEST");
    $this->append("useablePeriod", $useable);
    $tabTest[] = $this->sample("Test avec un useablePeriod incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec useablePeriod correcte
     */

    $useable->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->resetListdata("useablePeriod");
    $this->append("useablePeriod", $useable);
    $tabTest[] = $this->sample("Test avec un useablePeriod correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
