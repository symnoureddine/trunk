<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 *  Coded data, consists of a code, display name, code system,
 * and original text. Used when a single code value must be sent.
 */
class CCDACS extends CCDACV {

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["originalText"] = "CCDAED xml|element max|1 prohibited";
    $props["qualifier"] = "CCDACR xml|element prohibited";
    $props["translation"] = "CCDACD xml|element prohibited";
    $props["codeSystem"] = "CCDA_base_uid xml|attribute prohibited";
    $props["codeSystemName"] = "CCDA_base_st xml|attribute prohibited";
    $props["codeSystemVersion"] = "CCDA_base_st xml|attribute prohibited";
    $props["displayName"] = "CCDA_base_st xml|attribute prohibited";
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

    $this->setCode(null);
    $this->setCodeSystem("*");

    $tabTest[] = $this->sample("Test avec un codeSystem incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystem correct
     */

    $this->setCodeSystem("HL7");

    $tabTest[] = $this->sample("Test avec un codeSystem correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemName incorrecte
     */
    $this->setCodeSystem(null);
    $this->setCodeSystemName("");

    $tabTest[] = $this->sample("Test avec un codeSystemName incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemName correct
     */

    $this->setCodeSystemName("test");

    $tabTest[] = $this->sample("Test avec un codeSystemName correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemVersion incorrecte
     */

    $this->setCodeSystemName(null);
    $this->setCodeSystemVersion("");

    $tabTest[] = $this->sample("Test avec un codeSystemVersion incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec codeSystemVersion correct
     */

    $this->setCodeSystemVersion("test");

    $tabTest[] = $this->sample("Test avec un codeSystemVersion correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec displayName incorrecte
     */

    $this->setCodeSystemVersion(null);
    $this->setDisplayName("");

    $tabTest[] = $this->sample("Test avec un displayName incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec displayName correct
     */

    $this->setDisplayName("test");

    $tabTest[] = $this->sample("Test avec un displayName correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un originalText incorrect
     */

    $this->resetListQualifier();
    $ed = new CCDAED();
    $ed->setLanguage("test");
    $this->setOriginalText($ed);

    $tabTest[] = $this->sample("Test avec un originalText correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
