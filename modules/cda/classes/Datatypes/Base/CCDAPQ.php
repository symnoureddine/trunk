<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * A dimensioned quantity expressing the result of a
 * measurement act.
 */
class CCDAPQ extends CCDAQTY {

  /**
   * An alternative representation of the same physical
   * quantity expressed in a different unit, of a different
   * unit code system and possibly with a different value.
   *
   * @var array
   */
  public $translation = array();

  /**
   * The unit of measure specified in the Unified Code for
   * Units of Measure (UCUM)
   * [http://aurora.rg.iupui.edu/UCUM].
   *
   * @var CCDA_base_cs
   */
  public $unit;

  /**
   * Ajoute une instance de translation
   *
   * @param CCDAPQR $translation \CCDAPQR
   *
   * @return void
   */
  public function appendTranslation($translation) {
    $this->translation[] = $translation;
  }

  /**
   * Getter translation
   *
   * @return array
   */
  public function getTranslation() {
    return $this->translation;
  }

  /**
   * Setter unit
   *
   * @param String $unit String
   *
   * @return void
   */
  public function setUnit($unit) {
    if (!$unit) {
      $this->unit = null;
      return;
    }
    $uni = new CCDA_base_cs();
    $uni->setData($unit);
    $this->unit = $uni;
  }

  /**
   * Getter unit
   *
   * @return CCDA_base_cs
   */
  public function getUnit() {
    return $this->unit;
  }

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
    $val = new CCDA_base_real();
    $val->setData($value);
    $this->value = $val;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["translation"] = "CCDAPQR xml|element";
    $props["value"] = "CCDA_base_real xml|attribute";
    $props["unit"] = "CCDA_base_cs xml|attribute default|1";
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
     * Test avec une valeur incorrecte
     */

    $this->setValue("test");
    $tabTest[] = $this->sample("Test avec une valeur incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une valeur correcte
     */

    $this->setValue("10.25");
    $tabTest[] = $this->sample("Test avec une valeur correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une unit incorrecte
     */

    $this->setUnit(" ");
    $tabTest[] = $this->sample("Test avec une unit incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une unit correcte
     */

    $this->setUnit("test");
    $tabTest[] = $this->sample("Test avec une unit correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une translation incorrecte
     */

    $pqr = new CCDAPQR();
    $pqr->setValue("test");
    $this->appendTranslation($pqr);
    $tabTest[] = $this->sample("Test avec une translation incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une translation correcte
     */

    $pqr->setValue("10.25");
    $this->appendTranslation($pqr);
    $tabTest[] = $this->sample("Test avec une translation correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
