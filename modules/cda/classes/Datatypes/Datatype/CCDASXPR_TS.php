<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Datatype;

use Ox\Interop\Cda\Datatypes\Base\CCDASXCM_TS;

/**
 * CCDASXPR_TS class
 */
class CCDASXPR_TS extends CCDASXCM_TS {

  /**
   * @var CCDASXCM_TS
   */
  public $comp = array();

  /**
   * ADD a class
   *
   * @param CCDASXCM_TS $listData \CCDASXCM_TS
   *
   * @return void
   */
  function addData($listData) {
    $this->comp[] = $listData;
  }

  /**
   * Reinitialise la variable
   *
   * @return void
   */
  function resetListData () {
    $this->comp = array();
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["comp"] = "CCDASXCM_TS xml|element min|2";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = array();

    /**
     * Test avec une comp incorrecte
     */

    $sx = new CCDASXCM_TS();
    $sx->setOperator("TESTTEST");
    $this->addData($sx);
    $tabTest[] = $this->sample("Test avec une comp incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une comp correcte, minimum non atteint
     */

    $sx->setOperator("E");
    $this->resetListData();
    $this->addData($sx);
    $tabTest[] = $this->sample("Test avec une comp correcte, minimum non atteint", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une comp incorrecte, minimum atteint
     */

    $sx2 = new CCDASXCM_TS();
    $sx2->setOperator("TESTTEST");
    $this->addData($sx2);
    $tabTest[] = $this->sample("Test avec une comp correcte et une incorrecte, minimum atteint", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une comp incorrecte, minimum atteint
     */


    $sx2->setOperator("P");
    $this->resetListData();
    $this->addData($sx);
    $this->addData($sx2);
    $tabTest[] = $this->sample("Test avec deux comp correcte, minimum atteint", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
