<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Planification Sejour page representation
 */
class PlanifSejourPlanningPage extends PlanifSejourAbstractPage {

  protected $tab_name    = "vw_idx_planning";

  /**
   * Return editSejour_rques textarea value
   *
   * @return null|string
   */
  public function getSejourRques() {
    return $this->driver->getFormField("editSejour", "rques")->text();
  }
}