<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Base;

/**
 * CCDAIVL_TS class
 * Choix entre une séquence(low(1.1), [width(0.1)|high(0.1)]), element high(1.1), séquence(width(1.1), high(0.1)),
 * séquence(center(1.1), width(0.1))
 */
class CCDAIVL_TS extends CCDASXCM_TS {

  private $propsHigh = "CCDAIVXB_TS xml|element max|1";
  private $propsWidth = "CCDAPQ xml|element max|1";
  private $propsLow = "CCDAIVXB_TS xml|element max|1";
  private $propsCenter = "CCDATS xml|element max|1";
  private $_order = null;

  /**
   * The low limit of the interval.
   *
   * @var CCDAIVXB_TS
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
   * @var CCDAPQ
   */
  public $width;

  /**
   * The high limit of the interval.
   *
   * @var CCDAIVXB_TS
   */
  public $high;

  /**
   * The arithmetic mean of the interval (low plus high
   * divided by 2). The purpose of distinguishing the center
   * as a semantic property is for conversions of intervals
   * from and to point values.
   *
   * @var CCDATS
   */
  public $center;

  /**
   * Setter center
   *
   * @param CCDATS $center \CCDATS
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
   * @return CCDATS
   */
  public function getCenter() {
    return $this->center;
  }

  /**
   * Setter high
   *
   * @param CCDAIVXB_TS $high \CCDAIVXB_TS
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
   * @return CCDAIVXB_TS
   */
  public function getHigh() {
    return $this->high;
  }

  /**
   * Setter low
   *
   * @param CCDAIVXB_TS $low \CCDAIVXB_TS
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
   * @return CCDAIVXB_TS
   */
  public function getLow() {
    return $this->low;
  }

  /**
   * Setter width
   *
   * @param CCDAPQ $width \CCDAPQ
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
   * @return CCDAPQ
   */
  public function getWidth() {
    return $this->width;
  }


  /**
   * Affecte la séquence à utiliser
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

    $xbts = new CCDAIVXB_TS();
    $xbts->setValue("TESTTEST");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element low correcte, séquence low
     */

    $xbts->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte, séquence low
     */

    $hi = new CCDAIVXB_TS();
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

    $pq = new CCDAPQ();
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
    $hi = new CCDAIVXB_TS();
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
    $pq = new CCDAPQ();
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

    $hi = new CCDAIVXB_TS();
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
    $ts = new CCDATS();
    $ts->setValue("TESTTEST");
    $this->setCenter($ts);
    $tabTest[] = $this->sample("Test avec un center incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element center correcte, séquence center
     */

    $ts->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setCenter($ts);
    $tabTest[] = $this->sample("Test avec un center correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, séquence center
     */

    $pq = new CCDAPQ();
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
