<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Modeles page representation
 */
class ListeChoixPage extends HomePage {

  protected $module_name = "compteRendu";
  protected $tab_name = "vw_idx_listes";


  function createListeChoix($username) {
    $driver = $this->driver;

    $form = "Edit";

    // Click on the create button
    $driver->byId("vw_idx_list_create_list_choix")->click();

    // Change context to the iframe
    $driver->changeFrameFocus();

    // Wait iframe to load
    $driver->byId($form . "_user_id_view");

    // Fill the owner
    $driver->byId($form . "_user_id_view")->clear();
    $driver->byId($form . "_user_id_view")->click();

    $driver->selectAutocompleteByText($form . "_user_id_view", $username)->click();

    // Fill the name of the helper
    $driver->valueRetryByID($form . "_nom", "NomListeChoix");

    // Click on the create button
    $driver->byClassName("submit")->click();

    // Wait until the add item button is present
    $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector("button.add")));
  }
}