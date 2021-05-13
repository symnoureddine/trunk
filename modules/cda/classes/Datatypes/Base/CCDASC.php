<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * A ST that optionally may have a code attached.
 * The text must always be present if a code is present. The
 * code is often a local code.
 */
class CCDASC extends CCDAST {

  /**
   * The plain code symbol defined by the code system.
   * For example, "784.0" is the code symbol of the ICD-9
   * code "784.0" for headache.
   * @var CCDA_base_cs
   */
  public $code;

  /**
   * Specifies the code system that defines the code.
   * @var CCDA_base_uid
   */
  public $codeSystem;

  /**
   * A common name of the coding system.
   * @var CCDA_base_st
   */
  public $codeSystemName;

  /**
   * If applicable, a version descriptor defined
   * specifically for the given code system.
   * @var CCDA_base_st
   */
  public $codeSystemVersion;

  /**
   * A name or title for the code, under which the sending
   * system shows the code value to its users.
   * @var CCDA_base_st
   */
  public $displayName;

  /**
   * Getter code
   *
   * @return CCDA_base_cs CCDA_base_cs code
   */
  public function getCode() {

    return $this->code;
  }

  /**
   * Getter CodeSystem
   *
   * @return CCDA_base_uid
   */
  public function getCodeSystem() {
    return $this->codeSystem;
  }


  /**
   * Getter CodeSystemName
   *
   * @return CCDA_base_st
   */
  public function getCodeSystemName() {
    return $this->codeSystemName;
  }

  /**
   * Getter CodeSystemVersion
   *
   * @return CCDA_base_st
   */
  public function getCodeSystemVersion() {
    return $this->codeSystemVersion;
  }

  /**
   * Getter DisplayName
   *
   * @return CCDA_base_st
   */
  public function getDisplayName() {
    return $this->displayName;
  }

  /**
   * Setter Code
   *
   * @param String $code String
   *
   * @return void
   */
  public function setCode($code) {
    if (!$code) {
      $this->code = null;
      return;
    }
    $cs = new CCDA_base_cs();
    $cs->setData($code);
    $this->code = $cs;
  }

  /**
   * Setter CodeSystem
   *
   * @param String $codeSystem String
   *
   * @return void
   */
  public function setCodeSystem($codeSystem) {
    if (!$codeSystem) {
      $this->codeSystem = null;
      return;
    }
    $uid = new CCDA_base_uid();
    $uid->setData($codeSystem);
    $this->codeSystem = $uid;
  }

  /**
   * Setter codeSystemName
   *
   * @param String $codeSystemName String
   *
   * @return void
   */
  public function setCodeSystemName($codeSystemName) {
    if (!$codeSystemName) {
      $this->codeSystemName = null;
      return;
    }
    $st= new CCDA_base_st();
    $st->setData($codeSystemName);
    $this->codeSystemName = $st;
  }

  /**
   * Setter codeSystemVersion
   *
   * @param String $codeSystemVersion String
   *
   * @return void
   */
  public function setCodeSystemVersion($codeSystemVersion) {
    if (!$codeSystemVersion) {
      $this->codeSystemVersion = null;
      return;
    }
    $st= new CCDA_base_st();
    $st->setData($codeSystemVersion);
    $this->codeSystemVersion = $st;
  }

  /**
   * Setter displayName
   *
   * @param String $displayName String
   *
   * @return void
   */
  public function setDisplayName($displayName) {
    if (!$displayName) {
      $this->displayName = null;
      return;
    }
    $st= new CCDA_base_st();
    $st->setData($displayName);
    $this->displayName = $st;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["code"] = "CCDA_base_cs xml|attribute";
    $props["codeSystem"] = "CCDA_base_uid xml|attribute";
    $props["codeSystemName"] = "CCDA_base_st xml|attribute";
    $props["codeSystemVersion"] = "CCDA_base_st xml|attribute";
    $props["displayName"] = "CCDA_base_st xml|attribute";
    return $props;
  }


  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec code incorrecte
     */

    $this->setCode(" ");

    $tabTest[] = $this->sample("Test avec un code incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec code correct
     */

    $this->setCode("TEST");

    $tabTest[] = $this->sample("Test avec un code correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystem incorrecte
     */

    $this->setCodeSystem("*");

    $tabTest[] = $this->sample("Test avec un codeSystem incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystem correct
     */

    $this->setCodeSystem("HL7");

    $tabTest[] = $this->sample("Test avec un codeSystem correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemName incorrecte
     */

    $this->setCodeSystemName("");

    $tabTest[] = $this->sample("Test avec un codeSystemName incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemName correct
     */

    $this->setCodeSystemName("test");

    $tabTest[] = $this->sample("Test avec un codeSystemName correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemVersion incorrecte
     */

    $this->setCodeSystemVersion("");

    $tabTest[] = $this->sample("Test avec un codeSystemVersion incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemVersion correct
     */

    $this->setCodeSystemVersion("test");

    $tabTest[] = $this->sample("Test avec un codeSystemVersion correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec displayName incorrecte
     */

    $this->setDisplayName("");

    $tabTest[] = $this->sample("Test avec un displayName incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec displayName correct
     */

    $this->setDisplayName("test");

    $tabTest[] = $this->sample("Test avec un displayName correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
