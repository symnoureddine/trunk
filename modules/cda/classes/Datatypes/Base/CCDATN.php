<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * A restriction of entity name that is effectively a simple string used
 * for a simple name for things and places.
 */
class CCDATN extends CCDAEN {

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["delimiter"] = "CCDA_en_delimiter xml|element prohibited";
    $props["family"] = "CCDA_en_family xml|element prohibited";
    $props["given"] = "CCDA_en_given xml|element prohibited";
    $props["prefix"] = "CCDA_en_prefix xml|element prohibited";
    $props["suffix"] = "CCDA_en_suffix xml|element prohibited";
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
     * Test avec un family correcte
     */

    $enxp = new CCDA_en_family();
    $this->append("family", $enxp);
    $tabTest[] = $this->sample("Test avec un family correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un given correcte
     */

    $this->resetListdata("family");
    $enxp = new CCDA_en_given();
    $this->append("given", $enxp);
    $tabTest[] = $this->sample("Test avec un given correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un prefix correcte
     */

    $this->resetListdata("given");
    $enxp = new CCDA_en_prefix();
    $this->append("prefix", $enxp);
    $tabTest[] = $this->sample("Test avec un prefix correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un sufix correcte
     */

    $this->resetListdata("prefix");
    $enxp = new CCDA_en_suffix();
    $this->append("suffix", $enxp);
    $tabTest[] = $this->sample("Test avec un sufix correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un delimiter correcte
     */

    $this->resetListdata("sufix");
    $enxp = new CCDA_en_delimiter();
    $this->append("delimiter", $enxp);
    $tabTest[] = $this->sample("Test avec un delimiter correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
