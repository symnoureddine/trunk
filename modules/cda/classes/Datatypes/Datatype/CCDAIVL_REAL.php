<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Datatype;

use Ox\Interop\Cda\Datatypes\Base\CCDAREAL;

/**
 * CCDAIVL_REAL class
 * Choix entre une s�quence(low(1.1), [width(0.1)|high(0.1)]), element high(1.1), s�quence(width(1.1), high(0.1)),
 * s�quence(center(1.1), width(0.1))
 */
class CCDAIVL_REAL extends CCDASXCM_REAL {

  private $propsHigh   = "CCDAIVXB_REAL xml|element max|1";
  private $propsWidth  = "CCDAREAL xml|element max|1";
  private $propsLow    = "CCDAIVXB_REAL xml|element max|1";
  private $propsCenter = "CCDAREAL xml|element max|1";
  private $_order      = null;

  /**
   * The low limit of the interval.
   *
   * @var CCDAIVXB_REAL
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
   * @var CCDAREAL
   */
  public $width;

  /**
   * The high limit of the interval.
   *
   * @var CCDAIVXB_REAL
   */
  public $high;

  /**
   * The arithmetic mean of the interval (low plus high
   * divided by 2). The purpose of distinguishing the center
   * as a semantic property is for conversions of intervals
   * from and to point values.
   *
   * @var CCDAREAL
   */
  public $center;

  /**
   * Setter center
   *
   * @param CCDAREAL $center \CCDAREAL
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
   * @return CCDAREAL
   */
  public function getCenter() {
    return $this->center;
  }

  /**
   * Setter High
   *
   * @param CCDAIVXB_REAL $high \CCDAIVXB_REAL
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
   * @return CCDAIVXB_REAL
   */
  public function getHigh() {
    return $this->high;
  }

  /**
   * Setter low
   *
   * @param CCDAIVXB_REAL $low \CCDAIVXB_REAL
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
   * @return CCDAIVXB_REAL
   */
  public function getLow() {
    return $this->low;
  }

  /**
   * Setter width
   *
   * @param CCDAREAL $width \CCDAREAL
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
   * @return CCDAREAL
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Affecte la s�quence choisi
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
     * Test avec element low incorrecte, s�quence low
     */

    $xbts = new CCDAIVXB_REAL();
    $xbts->setInclusive("TESTTEST");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element low correcte, s�quence low
     */

    $xbts->setInclusive("true");
    $this->setLow($xbts);
    $tabTest[] = $this->sample("Test avec un low correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte, s�quence low
     */

    $hi = new CCDAIVXB_REAL();
    $hi->setInclusive("TESTTEST");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte, s�quence low
     */

    $hi->setInclusive("true");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, s�quence low incorrecte
     */

    $wid = new CCDAREAL();
    $wid->setValue("test");
    $this->setWidth($wid);
    $tabTest[] = $this->sample("Test avec un width incorrecte, s�quence incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, s�quence low incorrecte
     */

    $wid->setValue("10.25");
    $this->setWidth($wid);
    $tabTest[] = $this->sample("Test avec un width correcte, s�quence incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte
     */
    $this->setOrder(null);
    $this->low = null;
    $this->width = null;
    $this->center = null;
    $hi = new CCDAIVXB_REAL();
    $hi->setInclusive("TESTTEST");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte
     */

    $hi->setInclusive("true");
    $this->setHigh($hi);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, s�quence width
     */

    $this->high = null;
    $this->setOrder(null);
    $wid = new CCDAREAL();
    $wid->setValue("test");
    $this->setWidth($wid);
    $tabTest[] = $this->sample("Test avec un width incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, s�quence width
     */

    $wid->setValue("10.25");
    $this->setWidth($wid);
    $tabTest[] = $this->sample("Test avec un width correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high incorrecte, s�quence width
     */

    $hi2 = new CCDAIVXB_REAL();
    $hi2->setInclusive("TESTTEST");
    $this->setHigh($hi2);
    $tabTest[] = $this->sample("Test avec un high incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element high correcte, s�quence width
     */

    $hi2->setInclusive("true");
    $this->setHigh($hi2);
    $tabTest[] = $this->sample("Test avec un high correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element center incorrecte, s�quence center
     */
    $this->setOrder(null);
    $this->width = null;
    $this->high = null;
    $cen = new CCDAREAL();
    $cen->setValue("test");
    $this->setCenter($cen);
    $tabTest[] = $this->sample("Test avec un center incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element center correcte, s�quence center
     */

    $cen->setValue("10.25");
    $this->setCenter($cen);
    $tabTest[] = $this->sample("Test avec un center correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width incorrecte, s�quence center
     */

    $cenW = new CCDAREAL();
    $cenW->setValue("test");
    $this->setCenter($cenW);
    $tabTest[] = $this->sample("Test avec un width incorrecte", "Document invalide");

    /*-------------------------------------------------------------------------------------------*/

    /**
     * Test avec element width correcte, s�quence center
     */

    $cenW->setValue("10.25");
    $this->setCenter($cenW);
    $tabTest[] = $this->sample("Test avec un width correcte", "Document valide");

    /*-------------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
