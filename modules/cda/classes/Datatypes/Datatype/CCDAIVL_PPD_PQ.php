<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Datatype;

/**
 * CCDAIVL_PPD_PQ class
 * Choix entre une séquence(low(1.1), [width(0.1)|high(0.1)]), element high(1.1), séquence(width(1.1), high(0.1)),
 * séquence(center(1.1), width(0.1))
 */
class CCDAIVL_PPD_PQ extends CCDASXCM_PPD_PQ {

  private $propsHigh   = "CCDAIVXB_PPD_PQ xml|element max|1";
  private $propsWidth  = "CCDAPPD_PQ xml|element max|1";
  private $propsLow    = "CCDAIVXB_PPD_PQ xml|element max|1";
  private $propsCenter = "CCDAPPD_PQ xml|element max|1";
  private $_order      = null;

  /**
   * The low limit of the interval.
   *
   * @var CCDAIVXB_PPD_PQ
   */
  public $low;

  /**
   * The difference between high and low boundary. The
   * purpose of distinguishing a width property is to
   * handle all cases of incomplete information
   * symmetrically. In any interval representation only
   * two of the three properties high, low, and width need
   * to be stated and the third can be derived.
   *
   * @var CCDAPPD_PQ
   */
  public $width;

  /**
   * The high limit of the interval.
   *
   * @var CCDAIVXB_PPD_PQ
   */
  public $high;

  /**
   * The arithmetic mean of the interval (low plus high
   * divided by 2). The purpose of distinguishing the center
   * as a semantic property is for conversions of intervals
   * from and to point values.
   *
   * @var CCDAPPD_PQ
   */
  public $center;

  /**
   * Setter center
   *
   * @param CCDAPPD_PQ $center \CCDAPPD_PQ
   *
   * @return void
   */
  public function setCenter($center) {
    $this->setOrder("center");
    $this->center = $center;
  }

  /**
   * Getter center
   *
   * @return CCDAPPD_PQ
   */
  public function getCenter() {
    return $this->center;
  }

  /**
   * Setter High
   *
   * @param CCDAIVXB_PPD_PQ $high \CCDAIVXB_PPD_PQ
   *
   * @return void
   */
  public function setHigh($high) {
    $this->setOrder("high");
    $this->high = $high;
  }

  /**
   * Getter high
   *
   * @return CCDAIVXB_PPD_PQ
   */
  public function getHigh() {
    return $this->high;
  }

  /**
   * Setter low
   *
   * @param CCDAIVXB_PPD_PQ $low \CCDAIVXB_PPD_PQ
   *
   * @return void
   */
  public function setLow($low) {
    $this->setOrder("low");
    $this->low = $low;
  }

  /**
   * Getter low
   *
   * @return CCDAIVXB_PPD_PQ
   */
  public function getLow() {
    return $this->low;
  }

  /**
   * Setter width
   *
   * @param CCDAPPD_PQ $width \CCDAPPD_PQ
   *
   * @return void
   */
  public function setWidth($width) {
    $this->setOrder("width");
    $this->width = $width;
  }

  /**
   * Getter width
   *
   * @return CCDAPPD_PQ
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Affecte la séquence choisi
   *
   * @param String $nameVar String
   *
   * @return void
   */
  function setOrder($nameVar) {
    if (empty($this->_order)||empty($nameVar)) {
      $this->_order = $nameVar;
    }
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    switch ($this->_order) {
      case "low":
        $props["low"] = $this->propsLow;
        $props["width"] = $this->propsWidth;
        $props["high"] = $this->propsHigh;
        break;
      case "high":
        $props["high"] = $this->propsHigh;
        break;
      case "width":
        $props["width"] = $this->propsWidth;
        $props["high"] = $this->propsHigh;
        break;
      case "center":
        $props["center"] = $this->propsCenter;
        $props["width"] = $this->propsWidth;
        break;
      default:
        $props["low"] = $this->propsLow;
        $props["width"] = $this->propsWidth;
        $props["high"] = $this->propsHigh;
        $props["center"] = $this->propsCenter;
    }

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
     * Test avec element low incorrecte, séquence low
     */

    $xbts = new CCDAIVXB_PPD_PQ();
    $xbts->setValue("TESTTEST");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element low correcte, séquence low
     */

    $xbts->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $xbts->setInclusive("true");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte, séquence low
     */

    $hi = new CCDAIVXB_PPD_PQ();
    $hi->setValue("TESTTEST");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte, séquence low
     */

    $hi->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, séquence low incorrecte
     */

    $pq = new CCDAPPD_PQ();
    $pq->setValue("test");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width incorrecte, séquence incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, séquence low incorrecte
     */

    $pq->setValue("10.25");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width correcte, séquence incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte
     */
    $this->setOrder(null);
    $this->low = null;
    $this->width = null;
    $this->center = null;
    $hi = new CCDAIVXB_PPD_PQ();
    $hi->setValue("TESTTEST");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte
     */

    $hi->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, séquence width
     */

    $this->high = null;
    $this->setOrder(null);
    $pq = new CCDAPPD_PQ();
    $pq->setValue("test");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, séquence width
     */

    $pq->setValue("10.25");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte, séquence width
     */

    $hi = new CCDAIVXB_PPD_PQ();
    $hi->setValue("TESTTEST");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte, séquence width
     */

    $hi->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element center incorrecte, séquence center
     */
    $this->setOrder(null);
    $this->width = null;
    $this->high = null;
    $pq = new CCDAPPD_PQ();
    $pq->setDistributionType("TESTTEST");
    $this->setCenter($pq);
    $tabTest[] = $this->sample("Test avec un center incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element center correcte, séquence center
     */

    $pq->setDistributionType("F");
    $this->setCenter($pq);
    $tabTest[] = $this->sample("Test avec un center correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, séquence center
     */

    $pq = new CCDAPPD_PQ();
    $pq->setValue("test");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, séquence center
     */

    $pq->setValue("10.25");
    $this->setWidth($pq);
    $tabTest[] = $this->sample("Test avec un width correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
