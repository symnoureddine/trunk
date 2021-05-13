<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * An identifier that uniquely identifies a thing or object.
 * Examples are object identifier for HL7 RIM objects,
 * medical record number, order id, service catalog item id,
 * Vehicle Identification Number (VIN), etc. Instance
 * identifiers are defined based on ISO object identifiers.
 */
class CCDAII extends CCDAANY {

  /**
   * A unique identifier that guarantees the global uniqueness
   * of the instance identifier. The root alone may be the
   * entire instance identifier.
   *
   * @var CCDA_base_uid
   */
  public $root;

  /**
   * A character string as a unique identifier within the
   * scope of the identifier root.
   *
   * @var CCDA_base_st
   */
  public $extension;

  /**
   * A human readable name or mnemonic for the assigning
   * authority. This name may be provided solely for the
   * convenience of unaided humans interpreting an II value
   * and can have no computational meaning. Note: no
   * automated processing must depend on the assigning
   * authority name to be present in any form.
   *
   * @var CCDA_base_st
   */
  public $assigningAuthorityName;

  /**
   * Specifies if the identifier is intended for human
   * display and data entry (displayable = true) as
   * opposed to pure machine interoperation (displayable
   * = false).
   *
   * @var CCDA_base_bl
   */
  public $displayable;

  /** @var CCDAII[]  */
  public $_sous_section;
  /** @var string */
  public $_code_loinc;
  /** @var string */
  public $_title;

  /**
   * Setter assigningAuthorityName
   *
   * @param String $assigningAuthorityName String
   *
   * @return void
   */
  public function setAssigningAuthorityName($assigningAuthorityName) {
    if (!$assigningAuthorityName) {
      $this->assigningAuthorityName = null;
      return;
    }
    $ts = new CCDA_base_ts();
    $ts->setData($assigningAuthorityName);
    $this->assigningAuthorityName = $ts;
  }

  /**
   * Getter assigningAuthorityName
   *
   * @return CCDA_base_st
   */
  public function getAssigningAuthorityName() {
    return $this->assigningAuthorityName;
  }

  /**
   * Setter displayable
   *
   * @param String $displayable String
   *
   * @return void
   */
  public function setDisplayable($displayable) {
    if (!$displayable) {
      $this->displayable = null;
      return;
    }
    $bl = new CCDA_base_bl();
    $bl->setData($displayable);
    $this->displayable = $bl;
  }

  /**
   * Getter displayable
   *
   * @return CCDA_base_bl
   */
  public function getDisplayable() {
    return $this->displayable;
  }

  /**
   * Setter extension
   *
   * @param String $extension String
   *
   * @return void
   */
  public function setExtension($extension) {
    if (!$extension) {
      $this->extension = null;
      return;
    }
    $ts = new CCDA_base_ts();
    $ts->setData($extension);
    $this->extension = $ts;
  }

  /**
   * Getter extension
   *
   * @return CCDA_base_st
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * Setter root
   *
   * @param String $root String
   *
   * @return void
   */
  public function setRoot($root) {
    if (!$root) {
      $this->root = null;
      return;
    }
    $uid = new CCDA_base_uid();
    $uid->setData($root);
    $this->root = $uid;
  }

  /**
   * Getter root
   *
   * @return CCDA_base_uid
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["root"] = "CCDA_base_uid xml|attribute";
    $props["extension"] = "CCDA_base_st xml|attribute";
    $props["assigningAuthorityName"] = "CCDA_base_st xml|attribute";
    $props["displayable"] = "CCDA_base_bl xml|attribute";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec un uid incorrect
     */

    $this->setRoot("4TESTTEST");
    $tabTest[] = $this->sample("Test avec un root incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un uid correct
     */

    $this->setRoot("HL7");
    $tabTest[] = $this->sample("Test avec un root correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un extension incorrect
     */

    $this->setExtension("");
    $tabTest[] = $this->sample("Test avec un extension incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un extension correct
     */

    $this->setExtension("HL7");
    $tabTest[] = $this->sample("Test avec un extension correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un assigningAuthorityName incorrect
     */

    $this->setAssigningAuthorityName("");
    $tabTest[] = $this->sample("Test avec un assigningAuthorityName incorrecte, null par défaut", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un assigningAuthorityName correct
     */

    $this->setAssigningAuthorityName("HL7");
    $tabTest[] = $this->sample("Test avec un assigningAuthorityName correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un displayable incorrect
     */

    $this->setDisplayable("TESTTEST");
    $tabTest[] = $this->sample("Test avec un displayable incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un displayable correct
     */

    $this->setDisplayable("true");
    $tabTest[] = $this->sample("Test avec un displayable correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
