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
 * The character string data type stands for text data,
 * primarily intended for machine processing (e.g.,
 * sorting, querying, indexing, etc.) Used for names,
 * symbols, and formal expressions.
 */
class CCDAST extends CCDAED {

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["reference"] = "CCDATEL xml|element prohibited";
    $props["thumbnail"] = "CCDAthumbnail xml|element prohibited";
    $props["mediaType"] = "CCDACS xml|attribute default|text/plain";
    $props["compression"] = "CCDACompressionAlgorithm xml|attribute prohibited";
    $props["integrityCheck"] = "CCDbin xml|attribute prohibited";
    $props["integrityCheckAlgorithm"] = "CCDAintegrityCheckAlgorithm xml|attribute prohibited";
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
     * Test avec une valeur correcte mais refuser dans ce contexte
     */

    $this->setRepresentation("B64");

    $tabTest[] = $this->sample("Test avec une representation correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une valeur correcte
     */

    $this->setRepresentation("TXT");

    $tabTest[] = $this->sample("Test avec une representation correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un mediaType incorrecte
     *
     */

    $this->setMediaType(" ");

    $tabTest[] = $this->sample("Test avec un mediaType correcte, interdit dans ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un mediaType correcte
     *
     */

    $this->setMediaType("text/plain");

    $tabTest[] = $this->sample("Test avec un mediaType correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    if (CClassMap::getSN($this) !== "CCDAST") {
      return $tabTest;
    }

    /**
     * Test avec un compression incorrecte
     *
     */

    $this->setCompression(" ");

    $tabTest[] = $this->sample("Test avec un compression incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un compression correcte
     *
     */

    $this->setCompression("GZ");

    $tabTest[] = $this->sample("Test avec un compression correcte mais pas de ce contexte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }

}