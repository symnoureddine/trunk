<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Type Anesth page representation
 */
class PlanifSejourTypeAnesthPage extends HomePage {
  protected $module_name = "planningOp";
  protected $tab_name    = "vw_edit_typeanesth";


  public function createTypeAnesth($nom_typanesth = "NomTypeAnesth") {
    $driver = $this->driver;

    $form = "editType";

    // Click on the creation button
    $driver->byClassName("new")->click();

    // Fill the type anesth's name
    $driver->byId($form . "_name")->sendKeys($nom_typanesth);

    // Click on the creation button
    $driver->byClassName("submit")->click();

    // Return the creation message
    return $this->getSystemMessage();
  }
}