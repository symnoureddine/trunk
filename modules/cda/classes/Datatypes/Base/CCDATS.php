<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * A quantity specifying a point on the axis of natural time.
 * A point in time is most often represented as a calendar
 * expression.
 */
class CCDATS extends CCDAQTY{

  /**
   * Setter value
   *
   * @param String $value String
   *
   * @return void
   */
  public function setValue($value) {
    if (!$value) {
      $this->value = null;
      return;
    }
    $ts = new CCDA_base_ts();
    $ts->setData($value);
    $this->value = $ts;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["value"] = "CCDA_base_ts xml|attribute";
    return $props;
  }

  /**
   * Fcontion qui permet de tester si la classe fonctionne
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec une valeur incorrecte
     */

    $this->setValue("TESTTEST");
    $tabTest[] = $this->sample("Test avec une valeur correcte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une valeur correcte
     */

    $this->setValue("24141331462095.812975314545697850652375076363185459409261232419230495159675586");
    $tabTest[] = $this->sample("Test avec une valeur correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
