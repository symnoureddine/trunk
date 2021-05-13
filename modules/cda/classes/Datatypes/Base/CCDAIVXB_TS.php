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
 * CCDAIVXB_TS class
 */
class CCDAIVXB_TS extends CCDATS {

  /**
   * Specifies whether the limit is included in the
   * interval (interval is closed) or excluded from the
   * interval (interval is open).
   *
   * @var CCDA_base_bl
   */
  public $inclusive;

  /**
   * Setter inclusive
   *
   * @param String $inclusive String
   *
   * @return void
   */
  public function setInclusive($inclusive) {
    if (!$inclusive) {
      $this->inclusive = null;
      return;
    }
    $bl = new CCDA_base_bl();
    $bl->setData($inclusive);
    $this->inclusive = $bl;
  }

  /**
   * Getter inclusive
   *
   * @return CCDA_base_bl
   */
  public function getInclusive() {
    return $this->inclusive;
  }

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
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["inclusive"] = "CCDA_base_bl xml|attribute default|true";
    return $props;
  }

  /**
   * Fonction permettant de tester la validité de la classe
   *
   * @return array()
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec inclusive incorrecte
     */

    $this->setInclusive("test");
    $tabTest[] = $this->sample("Test avec un inclusive incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec inclusive correcte
     */

    $this->setInclusive("true");
    $tabTest[] = $this->sample("Test avec un inclusive correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }

}
