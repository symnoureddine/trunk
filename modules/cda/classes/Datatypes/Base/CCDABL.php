<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * The Boolean type stands for the values of two-valued logic.
 * A Boolean value can be either true or
 * false, or, as any other value may be NULL.
 */
class CCDABL extends CCDAANY {

  public $value;

  /**
   * Setter value
   *
   * @param String $value String
   *
   * @return void
   */
  function setValue($value) {
    if (!$value) {
      $this->value = null;
      return;
    }
    $val = new CCDA_base_bl();
    $val->setData($value);
    $this->value = $val;
  }

  /**
   * Getter value
   *
   * @return CCDA_base_bl
   */
  function getValue() {
    return $this->value;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["value"] = "CCDA_base_bl xml|attribute notNullFlavor";
    return $props;
  }

  /**
   * Fonction qui permet de vérifier que la classe fonctionne
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec une valeur incorrecte
     */

    $this->setValue("TESTTEST");

    $tabTest[] = $this->sample("Test avec une valeur incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une valeur correcte
     */

    $this->setValue("true");

    $tabTest[] = $this->sample("Test avec une valeur correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une valeur correcte et avec un nullflavor
     */

    $this->setNullFlavor("NP");
    $tabTest[] = $this->sample("Test avec un nullFlavor correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
