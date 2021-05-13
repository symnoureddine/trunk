<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

use Ox\Core\CClassMap;

/**
 * A concept descriptor represents any kind of concept usually
 * by giving a code defined in a code system.  A concept
 * descriptor can contain the original text or phrase that
 * served as the basis of the coding and one or more
 * translations into different coding systems. A concept
 * descriptor can also contain qualifiers to describe, e.g.,
 * the concept of a "left foot" as a postcoordinated term built
 * from the primary code "FOOT" and the qualifier "LEFT".
 * In exceptional cases, the concept descriptor need not
 * contain a code but only the original text describing
 * that concept.
 */
class CCDACD extends CCDAANY {

  /**
   * The text or phrase used as the basis for the coding.
   * @var CCDAED
   */
  public $originalText;

  /**
   * Specifies additional codes that increase the
   * specificity of the primary code.
   * @var CCDACR
   */
  public $qualifier = array();

  /**
   * A set of other concept descriptors that translate
   * this concept descriptor into other code systems.
   * @var CCDACD
   */
  public $translation = array();

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
   * Getter OriginalText
   *
   * @return CCDAED
   */
  public function getOriginalText() {
    return $this->originalText;
  }

  /**
   * Getter Qualifier
   *
   * @return CCDACR
   */
  public function getQualifier() {
    return $this->qualifier;
  }

    /**
   * Getter Translation
   *
   * @return CCDACD
   */
  public function getTranslation() {
    return $this->translation;
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
    $cod = new CCDA_base_cs();
    $cod->setData($code);
    $this->code = $cod;
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
    $codeSys = new CCDA_base_uid();
    $codeSys->setData($codeSystem);
    $this->codeSystem = $codeSys;
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
    $codeSysN = new CCDA_base_st();
    $codeSysN->setData($codeSystemName);
    $this->codeSystemName = $codeSysN;
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
    $codeSysV = new CCDA_base_st();
    $codeSysV->setData($codeSystemVersion);
    $this->codeSystemVersion = $codeSysV;
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
    $diplay = new CCDA_base_st();
    $diplay->setData($displayName);
    $this->displayName = $diplay;
  }

  /**
   * Setter originalText
   *
   * @param CCDAED $originalText CCDAED
   *
   * @return void
   */
  public function setOriginalText($originalText) {
    $this->originalText = $originalText;
  }

  /**
   * Setter qualifier
   *
   * @param CCDACR $qualifier CCDACR
   *
   * @return void
   */
  public function setQualifier($qualifier) {
    array_push($this->qualifier, $qualifier);
  }

  /**
   * Setter translation
   *
   * @param CCDACD $translation CCDACD
   *
   * @return void
   */
  public function setTranslation($translation) {
    array_push($this->translation, $translation);
  }

  /**
   * Efface le tableau translation
   *
   * @return void
   */
  public function resetListTranslation() {
    $this->translation = array();
  }

  /**
   * Efface le tableau qualifier
   *
   * @return void
   */
  public function resetListQualifier() {
    $this->qualifier = array();
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["originalText"] = "CCDAED xml|element max|1";
    $props["qualifier"] = "CCDACR xml|element";
    $props["translation"] = "CCDACD xml|element";
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

    if (CClassMap::getSN($this) === "CCDAEIVL_event" || CClassMap::getSN($this) === "CCDACS") {
      return $tabTest;
    }
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

    if (CClassMap::getSN($this) !== "CCDACD") {
      return $tabTest;
    }

    /**
     * Test avec un translation correct sans valeur
     */
    $translation = new CCDACD();
    $this->setTranslation($translation);

    $tabTest[] = $this->sample("Test avec une translation correct sans valeur", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux translation correct sans valeur
     */
    $translation2 = new CCDACD();
    $this->setTranslation($translation2);

    $tabTest[] = $this->sample("Test avec deux translation correct sans valeur", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un qualifier incorrect
     */

    $cr = new CCDACR();
    $cr->setInverted("TESTTEST");
    $this->setQualifier($cr);

    $tabTest[] = $this->sample("Test avec un qualifier incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un qualifier correct
     */

    $cr->setInverted("true");
    $this->setQualifier($cr);

    $tabTest[] = $this->sample("Test avec un qualifier correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux qualifier correct
     */

    $cr2 = new CCDACR();
    $cr2->setInverted("true");
    $this->setQualifier($cr2);

    $tabTest[] = $this->sample("Test avec deux qualifier correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un originalText incorrect
     */

    $ed = new CCDAED();
    $ed->setLanguage(" ");
    $this->setOriginalText($ed);

    $tabTest[] = $this->sample("Test avec un originalText incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un originalText correct
     */

    $ed->setLanguage("TEST");
    $this->setOriginalText($ed);

    $tabTest[] = $this->sample("Test avec un originalText correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
