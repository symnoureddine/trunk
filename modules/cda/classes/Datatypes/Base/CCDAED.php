<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

use Ox\Core\CClassMap;
use Ox\Interop\Cda\Datatypes\Voc\CCDACompressionAlgorithm;
use Ox\Interop\Cda\Datatypes\Voc\CCDAIntegrityCheckAlgorithm;

/**
 * Data that is primarily intended for human interpretation
 * or for further machine processing is outside the scope of
 * HL7. This includes unformatted or formatted written language,
 * multimedia data, or structured information as defined by a
 * different standard (e.g., XML-signatures.)  Instead of the
 * data itself, an ED may contain
 * only a reference (see TEL.) Note
 * that the ST data type is a
 * specialization of the ED data type
 * when the ED media type is text/plain.
 */
class CCDAED extends CCDABIN {

  /**
   * A telecommunication address (TEL), such as a URL
   * for HTTP or FTP, which will resolve to precisely
   * the same binary data that could as well have been
   * provided as inline data.
   * @var CCDATEL
   */
  public $reference;

  /**
   * @var CCDAthumbnail
   */
  public $thumbnail;

  /**
   * Identifies the type of the encapsulated data and
   * identifies a method to interpret or render the data.
   * @var CCDACS
   */
  public $mediaType;

  /**
   * For character based information the language property
   * specifies the human language of the text.
   * @var CCDACS
   */
  public $language;

  /**
   * Indicates whether the raw byte data is compressed,
   * and what compression algorithm was used.
   * @var CCDACompressionAlgorithm
   */
  public $compression;

  /**
   * The integrity check is a short binary value representing
   * a cryptographically strong checksum that is calculated
   * over the binary data. The purpose of this property, when
   * communicated with a reference is for anyone to validate
   * later whether the reference still resolved to the same
   * data that the reference resolved to when the encapsulated
   * data value with reference was created.
   * @var CCDAbin
   */
  public $integrityCheck;

  /**
   * Specifies the algorithm used to compute the
   * integrityCheck value.
   * @var CCDAIntegrityCheckAlgorithm
   */
  public $integrityCheckAlgorithm;

  /**
   * Setter compressionAlgorithm
   *
   * @param String $compression String
   *
   * @return void
   */
  public function setCompression($compression) {
    if (!$compression) {
      $this->compression = null;
      return;
    }
    $comp = new CCDACompressionAlgorithm();
    $comp->setData($compression);
    $this->compression = $comp;
  }

  /**
   * Getter compressionAlgorithm
   *
   * @return CCDACompressionAlgorithm
   */
  public function getCompression() {
    return $this->compression;
  }

  /**
   * Setter integrityCheck
   *
   * @param String $integrityCheck String
   *
   * @return void
   */
  public function setIntegrityCheck($integrityCheck) {
    if (!$integrityCheck) {
      $this->integrityCheck = null;
      return;
    }
    $integ = new CCDA_base_bin();
    $integ->setData($integrityCheck);
    $this->integrityCheck = $integ;
  }

  /**
   * Getter integrityCheck
   *
   * @return CCDA_base_bin
   */
  public function getIntegrityCheck() {
    return $this->integrityCheck;
  }

  /**
   * Setter integrityCheckAlgorithm
   *
   * @param String $integrityCheckAlgorithm String
   *
   * @return void
   */
  public function setIntegrityCheckAlgorithm($integrityCheckAlgorithm) {
    if (!$integrityCheckAlgorithm) {
      $this->integrityCheckAlgorithm = null;
      return;
    }
    $integ = new CCDAIntegrityCheckAlgorithm();
    $integ->setData($integrityCheckAlgorithm);
    $this->integrityCheckAlgorithm = $integ;
  }

  /**
   * Getter integrityCheckAlgorithm
   *
   * @return CCDAIntegrityCheckAlgorithm
   */
  public function getIntegrityCheckAlgorithm() {
    return $this->integrityCheckAlgorithm;
  }

  /**
   * Setter language
   *
   * @param String $language String
   *
   * @return void
   */
  public function setLanguage($language) {
    if (!$language) {
      $this->language = null;
      return;
    }
    $lang = new CCDA_base_cs();
    $lang->setData($language);
    $this->language = $lang;
  }

  /**
   * Getter language
   *
   * @return CCDA_base_cs
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * Setter mediaType
   *
   * @param String $mediaType String
   *
   * @return void
   */
  public function setMediaType($mediaType) {
    if (!$mediaType) {
      $this->mediaType = null;
      return;
    }
    $media = new CCDA_base_cs();
    $media->setData($mediaType);
    $this->mediaType = $media;
  }

  /**
   * Getter mediaType
   *
   * @return CCDA_base_cs
   */
  public function getMediaType() {
    return $this->mediaType;
  }

  /**
   * Setter reference
   *
   * @param CCDATEL $reference \CCDATEL
   *
   * @return void
   */
  public function setReference($reference) {
    $this->reference = $reference;
  }

  /**
   * Getter reference
   *
   * @return CCDATEL
   */
  public function getReference() {
    return $this->reference;
  }

  /**
   * Setter thumbnail
   *
   * @param CCDAthumbnail $thumbnail \CCDAthumbnail
   *
   * @return void
   */
  public function setThumbnail($thumbnail) {
    $this->thumbnail = $thumbnail;
  }

  /**
   * Getter thumbnail
   *
   * @return CCDAthumbnail
   */
  public function getThumbnail() {
    return $this->thumbnail;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["reference"] = "CCDATEL xml|element max|1";
    $props["thumbnail"] = "CCDAthumbnail xml|element max|1";
    $props["mediaType"] = "CCDA_base_cs xml|attribute default|text/plain";
    $props["language"] = "CCDA_base_cs xml|attribute";
    $props["compression"] = "CCDACompressionAlgorithm xml|attribute";
    $props["integrityCheck"] = "CCDA_base_bin xml|attribute";
    $props["integrityCheckAlgorithm"] = "CCDAintegrityCheckAlgorithm xml|attribute default|SHA-1";
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
     * Test avec un language incorrecte
     *
     */

    $this->setLanguage(" ");

    $tabTest[] = $this->sample("Test avec un language incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un language correcte
     *
     */

    $this->setLanguage("TEST");

    $tabTest[] = $this->sample("Test avec un language correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    if (CClassMap::getSN($this) !== "CCDAED") {
      return $tabTest;
    }

    /**
     * Test avec un mediaType incorrecte
     *
     */

    $this->setMediaType(" ");

    $tabTest[] = $this->sample("Test avec un mediaType incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un mediaType correcte
     *
     */


    $this->setMediaType("TEST");

    $tabTest[] = $this->sample("Test avec un mediaType correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un compression incorrecte
     *
     */

    $this->setCompression(" ");

    $tabTest[] = $this->sample("Test avec une compression incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un compression correcte
     *
     */

    $this->setCompression("GZ");

    $tabTest[] = $this->sample("Test avec une compression correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un integrityCheck incorrecte
     *
     */

    $this->setIntegrityCheck("111111111");

    $tabTest[] = $this->sample("Test avec un integrityCheck incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un integrityCheck correcte
     *
     */

    $this->setIntegrityCheck("JVBERi0xLjUNCiW1tbW1DQoxIDAgb2Jq");

    $tabTest[] = $this->sample("Test avec un integrityCheck correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un integrityCheckAlgorithm incorrecte
     *
     */

    $this->setIntegrityCheckAlgorithm("SHA-25");

    $tabTest[] = $this->sample("Test avec un integrityCheck incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un integrityCheckAlgorithm correcte
     *
     */

    $this->setIntegrityCheckAlgorithm("SHA-256");

    $tabTest[] = $this->sample("Test avec un integrityCheck correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une reference incorrecte
     *
     */

    $tel = new CCDATEL();
    $tel->setUse(array("TEST"));
    $this->setReference($tel);

    $tabTest[] = $this->sample("Test avec une reference incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec une reference correcte
     *
     */

    $tel->setUse(array("MC"));
    $this->setReference($tel);

    $tabTest[] = $this->sample("Test avec une reference correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    if (CClassMap::getSN($this) !== "CCDAED") {
      return $tabTest;
    }

    /**
     * Test avec un thumbnail incorrecte
     *
     */

    $thum = new CCDAthumbnail();
    $thum->setIntegrityCheckAlgorithm("SHA-25");
    $this->setThumbnail($thum);

    $tabTest[] = $this->sample("Test avec un thumbnail incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un thumbnail correcte
     *
     */

    $thum->setIntegrityCheckAlgorithm("SHA-256");
    $this->setThumbnail($thum);

    $tabTest[] = $this->sample("Test avec un thumbnail correcte", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
