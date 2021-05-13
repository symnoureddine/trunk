<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\HomePage;

/**
 * InfoChecklist page representation
 */
class InfoChecklistPage extends HomePage {

  protected $module_name = "cabinet";
  protected $tab_name = "vw_info_checklist";

  /**
   * Création d'une info de checklist
   *
   * @param string $name_info nom de l'info
   *
   * @return void
   */
  function testCreateInfoChecklistOk($name_info= "NomInfo") {
    $driver = $this->driver;

    // Click on the create button
    $driver->byCss(".new")->click();

    $form = "Edit-CInfoChecklist";
    // Name
    $driver->byId($form . "_libelle")->sendKeys($name_info);

    // Click on the create button
    $driver->byCss(".submit")->click();
    $driver->waitForAjax('list_info_checklists');
  }
}