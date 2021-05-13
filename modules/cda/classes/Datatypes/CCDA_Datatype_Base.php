<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes;

use Ox\Core\CClassMap;

/**
 * Classe dont hériteront les classes de base (real, int...)
 */
class CCDA_Datatype_Base extends CCDA_Datatype {
  
  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    
    return $props;
  }

  /**
   * Retourne le nom du type utilisé dans le XSD
   *
   * @return string
   */
  function getNameClass() {
    $name = CClassMap::getSN($this);

    $name = substr($name, strrpos($name, "_")+1);

    return $name;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array()
   */
  function test() {
    $tabTest = array();

    if (CClassMap::getSN($this) === "CCDA_base_bin" || CClassMap::getSN($this) === "CCDA_base_url") {
      return $tabTest;
    }
    /**
     * Test avec une valeur null
     */

    $tabTest[] = $this->sample("Test avec une valeur null", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
