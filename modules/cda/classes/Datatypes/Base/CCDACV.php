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
 * Coded data, consists of a code, display name, code system,
 * and original text. Used when a single code value must be sent.
 */
class CCDACV extends CCDACE {

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["translation"] = "CCDACD xml|element prohibited";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    if (CClassMap::getSN($this) === "CCDAPQR") {
      return $tabTest;
    }

    /**
     * Test avec un translation correct avec valeur
     */
    $translation = new CCDACD();
    $translation->setCodeSystemName("test");
    $this->setTranslation($translation);

    $tabTest[] = $this->sample("Test avec une translation correct, interdit dans ce contexte", "Document invalide");
    $this->resetListTranslation();
    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
