<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use Ox\Core\CClassMap;
use Ox\Interop\Cda\Datatypes\Base\CCDACS;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Voc\CCDANullFlavor;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_InfrastructureRoot_typeId;

/**
 * CCDAClasseBase Class
 */
class CCDAClasseCda extends CCDAClasseBase {

  /**
   * @var CCDAII
   */
  public $typeId;

  /**
   * @var CCDANullFlavor
   */
  public $nullFlavor;

  /**
   * @var CCDACS[]
   */
  public $realmCode = array();

  /**
   * @var CCDAII
   */
  public $templateId = array();

  /**
   * Assigne typeID au valeurs par défaut
   *
   * @return void
   */
  public function setTypeId() {
    $typeID = new CCDAPOCD_MT000040_InfrastructureRoot_typeId();
    $this->typeId = $typeID;
  }

  /**
   * Getter typeID
   *
   * @return CCDAII
   */
  public function getTypeId() {
    return $this->typeId;
  }

  /**
   * Ajoute un realmCode dans le tableau
   *
   * @param CCDACS $realmCode CCDACS
   *
   * @return void
   */
  function appendRealmCode($realmCode) {
    array_push($this->realmCode, $realmCode);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListRealmCode() {
    $this->realmCode = array();
  }

  /**
   * Getter realmCode
   *
   * @return CCDACS[]
   */
  function getRealmCode() {
    return $this->realmCode;
  }

  /**
   * Ajoute un templateId dans le tableau
   *
   * @param CCDAII $templateId CCDAII
   *
   * @return void
   */
  function appendTemplateId($templateId) {
    array_push($this->templateId, $templateId);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListTemplateId() {
    $this->templateId = array();
  }

  /**
   * Getter templateId
   *
   * @return CCDAII[]
   */
  function getTemplateID() {
    return $this->templateId;
  }

  /**
   * Setter nullFlavor
   *
   * @param String $nullFlavor String
   *
   * @return void
   */
  public function setNullFlavor($nullFlavor) {
    if (!$nullFlavor) {
      $this->nullFlavor = null;
      return;
    }
    $null = new CCDANullFlavor();
    $null->setData($nullFlavor);
    $this->nullFlavor = $null;
  }

  /**
   * Getter nullFlavor
   *
   * @return CCDANullFlavor
   */
  public function getNullFlavor() {
    return $this->nullFlavor;
  }

  /**
   * Retourne le nom de la classe
   *
   * @return String
   */
  function getNameClass() {
    $name = CClassMap::getSN($this);
    $part = substr($name, strpos($name, "_")+1);
    $part = str_replace("_", ".", $part);
    $name = substr_replace($name, $part, strpos($name, "_")+1);
    $name = substr($name, 4);
    return $name;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = array();
    $props["realmCode"]   = "CCDACS xml|element";
    $props["typeId"]      = "CCDAPOCD_MT00040_InfrastructureRoot_typeId xml|element max|1";
    $props["templateId"]  = "CCDAII xml|element";
    $props["nullFlavor"]  = "CCDANullFlavor xml|attribute";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = array();

    if (CClassMap::getSN($this) === "CCDAPOCD_MT000040_AuthoringDevice"
        || CClassMap::getSN($this) === "CCDAPOCD_MT000040_Criterion"
        || CClassMap::getSN($this) === "CCDAPOCD_MT000040_Device"
        || CClassMap::getSN($this) === "CCDAPOCD_MT000040_ServiceEvent"
    ) {
      return $tabTest;
    }

    /**
     * Test avec les valeurs null
     */

    $tabTest[] = $this->sample("Test avec les valeurs null", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un typeId correct
     */

    $this->setTypeId();
    $tabTest[] = $this->sample("Test avec un typeId correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un realmCode incorrect
     */

    $cs = new CCDACS();
    $cs->setCode(" ");
    $this->appendRealmCode($cs);
    $tabTest[] = $this->sample("Test avec un realmCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un realmCode correct
     */

    $cs->setCode("FR");
    $this->resetListRealmCode();
    $this->appendRealmCode($cs);
    $tabTest[] = $this->sample("Test avec un realmCode correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un templateId incorrect
     */

    $ii = new CCDAII();
    $ii->setRoot("4TESTTEST");
    $this->appendTemplateId($ii);
    $tabTest[] = $this->sample("Test avec un templateId incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un templateId correct
     */

    $ii->setRoot("1.2.250.1.213.1.1.1.1");
    $this->resetListTemplateId();
    $this->appendTemplateId($ii);
    $tabTest[] = $this->sample("Test avec un templateId correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}