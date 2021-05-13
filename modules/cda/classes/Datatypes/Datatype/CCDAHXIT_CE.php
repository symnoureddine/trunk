<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Datatype;

use Ox\Core\CClassMap;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVXB_TS;

/**
 * CCDAHXIT_CE class
 */
class CCDAHXIT_CE extends CCDACE {

  /**
   * The time interval during which the given information
   * was, is, or is expected to be valid. The interval can
   * be open or closed, as well as infinite or undefined on
   * either side.
   *
   * @var CCDAIVL_TS
   */
  public $validTime;

  /**
   * retourne le nom du type CDA
   *
   * @return string
   */
  function getNameClass() {
    $name = CClassMap::getSN($this);
    $name = substr($name, 4);

    return $name;
  }

  /**
   * Setter validTime
   *
   * @param CCDAIVL_TS $validTime \CCDAIVL_TS
   *
   * @return void
   */
  public function setValidTime($validTime) {
    $this->validTime = $validTime;
  }

  /**
   * Getter validTime
   *
   * @return CCDAIVL_TS
   */
  public function getValidTime() {
    return $this->validTime;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["validTime"] = "CCDAIVL_TS xml|element max|1";
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
     * Test avec un validTime incorrecte
     */

    $ivl = new CCDAIVL_TS();
    $ivbx = new CCDAIVXB_TS();
    $ivbx->setInclusive("TESTTESt");
    $ivl->setLow($ivbx);
    $this->setValidTime($ivl);
    $tabTest[] = $this->sample("Test avec un validTime incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une quantity correcte
     */

    $ivbx->setInclusive("true");
    $ivl->setLow($ivbx);
    $this->setValidTime($ivl);
    $tabTest[] = $this->sample("Test avec un validTime correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
