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
 * A name for a person, organization, place or thing. A
 * sequence of name parts, such as given name or family
 * name, prefix, suffix, etc. Examples for entity name
 * values are "Jim Bob Walton, Jr.", "Health Level Seven,
 * Inc.", "Lake Tahoe", etc. An entity name may be as simple
 * as a character string or may consist of several entity name
 * parts, such as, "Jim", "Bob", "Walton", and "Jr.", "Health
 * Level Seven" and "Inc.", "Lake" and "Tahoe".
 */
class CCDAEN extends CCDAANY {

  public $delimiter = array();
  public $family = array();
  public $given = array();
  public $prefix = array();
  public $suffix = array();

  /**
   * An interval of time specifying the time during which
   * the name is or was used for the entity. This
   * accomodates the fact that people change names for
   * people, places and things.
   *
   * @var CCDAIVL_TS
   */
  public $validTime;

  /**
   * A set of codes advising a system or user which name
   * in a set of like names to select for a given purpose.
   * A name without specific use code might be a default
   * name useful for any purpose, but a name with a specific
   * use code would be preferred for that respective purpose.
   *
   * @var CCDAset_EntityNameUse
   */
  public $use;

  /**
   * Setter use
   *
   * @param String[] $use String[]
   *
   * @return void
   */
  public function setUse($use) {
    $setEn = new CCDAset_EntityNameUse();
    foreach ($use as $_use) {
      $setEn->addData($_use);
    }
    $this->use = $setEn;
  }

  /**
   * Getter use
   *
   * @return CCDAset_EntityNameUse
   */
  public function getUse() {
    return $this->use;
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
   * Ajoute l'instance dans le champ spécifié
   *
   * @param String $name  String
   * @param mixed  $value mixed
   *
   * @return void
   */
  function append($name, $value) {
    array_push($this->$name, $value);
  }

  /**
   * retourne le tableau d'instance du champ spécifié
   *
   * @param String $name String
   *
   * @return mixed
   */
  function get($name) {
    return $this->$name;
  }

  /**
   * Efface le tableau d'instance du champ spécifié
   *
   * @param String $name String
   *
   * @return void
   */
  function resetListdata($name) {
    $this->$name = array();
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["delimiter"] = "CCDA_en_delimiter xml|element";
    $props["family"] = "CCDA_en_family xml|element";
    $props["given"] = "CCDA_en_given xml|element";
    $props["prefix"] = "CCDA_en_prefix xml|element";
    $props["suffix"] = "CCDA_en_suffix xml|element";
    $props["validTime"] = "CCDAIVL_TS xml|element max|1";
    $props["use"] = "CCDAset_EntityNameUse xml|attribute";
    $props["data"] = "str xml|data";
    return $props;
  }

  /**
  * fonction permettant de tester la validité de la classe
  *
  * @return array()
  */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec des données
     */

    $this->setData("test");
    $tabTest[] = $this->sample("Test avec des données", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un use incorrecte
     */

    $this->setUse(array("TESTTEST"));
    $tabTest[] = $this->sample("Test avec un use incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un use correcte
     */

    $this->setUse(array("C"));
    $tabTest[] = $this->sample("Test avec un use correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un validTime incorrecte
     */

    $valid = new CCDAIVL_TS();
    $valid->setValue("TESTTEST");
    $this->setValidTime($valid);
    $tabTest[] = $this->sample("Test avec un validTime incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un validTime correcte
     */

    $valid->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setValidTime($valid);
    $tabTest[] = $this->sample("Test avec un validTime correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    if (CClassMap::getSN($this) === "CCDATN") {
      return $tabTest;
    }

    /**
     * Test avec un delimiter correcte
     */

    $enxp = new CCDA_en_delimiter();
    $this->append("delimiter", $enxp);
    $tabTest[] = $this->sample("Test avec un delimiter correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux delimiter correcte
     */

    $enxp = new CCDA_en_delimiter();
    $this->append("delimiter", $enxp);
    $tabTest[] = $this->sample("Test avec deux delimiter correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un prefix correcte
     */

    $enxp = new CCDA_en_prefix();
    $this->append("prefix", $enxp);
    $tabTest[] = $this->sample("Test avec un prefix correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux prefix correcte
     */

    $enxp = new CCDA_en_prefix();
    $this->append("prefix", $enxp);
    $tabTest[] = $this->sample("Test avec deux prefix correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un suffix correcte
     */

    $enxp = new CCDA_en_suffix();
    $this->append("suffix", $enxp);
    $tabTest[] = $this->sample("Test avec un suffix correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux prefix correcte
     */

    $enxp = new CCDA_en_suffix();
    $this->append("suffix", $enxp);
    $tabTest[] = $this->sample("Test avec deux suffix correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    if (CClassMap::getSN($this) === "CCDAON") {
      return $tabTest;
    }
    /**
     * Test avec un family correcte
     */

    $enxp = new CCDA_en_family();
    $this->append("family", $enxp);
    $tabTest[] = $this->sample("Test avec un family correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux family correcte
     */

    $enxp = new CCDA_en_family();
    $this->append("family", $enxp);
    $tabTest[] = $this->sample("Test avec deux family correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un given correcte
     */

    $enxp = new CCDA_en_given();
    $this->append("given", $enxp);
    $tabTest[] = $this->sample("Test avec un given correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux given correcte
     */

    $enxp = new CCDA_en_given();
    $this->append("given", $enxp);
    $tabTest[] = $this->sample("Test avec deux given correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
