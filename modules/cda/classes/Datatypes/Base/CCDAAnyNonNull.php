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
class CCDAAnyNonNull extends CCDAANY {

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["nullFlavor"] = "CCDANullFlavor xml|attribute prohibited";
    return $props;
  }

  /**
   * Fonction qui permet de vérifier si la classe fonctionne
   *
   * @return array
   */
  function test() {

    $tabTest = parent::test();

    /**
     * Test avec un nullFlavor incorrect
     */

    $this->setNullFlavor("TESTEST");
    $tabTest[] = $this->sample("Test avec un nullFlavor incorrect", "Document invalide");
    $this->setNullFlavor(null);

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
