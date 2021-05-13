<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * The BooleanNonNull type is used where a Boolean cannot
 * have a null value. A Boolean value can be either
 * true or false.
 */
class CCDABN extends CCDAAnyNonNull {

  public $value;

  /**
   * Setter value
   *
   * @param String $value String
   *
   * @return CCDA_base_bn
   */
  function setValue($value) {
    if (!$value) {
      $this->value = null;
      return;
    }
    $val = new CCDA_base_bn();
    $val->setData($value);
    $this->value = $val;
  }

  /**
   * Getter value
   *
   * @return CCDA_base_bn
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
    $props["value"] = "CCDA_base_bn xml|attribute";
    return $props;
  }

  /**
   * Foncntion permettant de tester la classe
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

    return $tabTest;
  }
}
