<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Modeles page representation
 */
class AideSaisiePage extends HomePage {

  protected $module_name = "compteRendu";
  protected $tab_name = "vw_idx_aides";


  function createAideSaisie($username) {
    $driver = $this->driver;

    $form = "editFrm";

    // Click on the create button
    $driver->byCss(".new")->click();

    // Change context to the iframe
    $driver->changeFrameFocus();

    // Wait iframe to load
    $driver->byId($form . "_user_id_view");

    // Fill the owner
    $driver->byId($form . "_user_id_view")->click();
    $driver->selectAutocompleteByText($form . "_user_id_view", $username)->click();

    // Fill the targetted object_class
    $driver->selectOptionByValue($form . "_class", "CCompteRendu");

    // Fill the targetted field on the object class
    $driver->selectOptionByValue($form . "_field", "_source");

    // Fill the name of the helper
    $driver->byId($form . "_name")->sendKeys("NomAideSaisie");

    // FIll the content of the helper
    $driver->byId($form . "_text")->sendKeys("Contenu aide saisie");

    $driver->byClassName("submit")->click();

    // Wait 1 sec until end of helper creation
    sleep(1);
  }
}