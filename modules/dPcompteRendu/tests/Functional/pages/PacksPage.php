<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Packs page representation
 */
class PacksPage extends HomePage {

  protected $module_name = "compteRendu";
  protected $tab_name = "vw_idx_packs";

  function testCreatePackModele($pack) {
    $driver = $this->driver;

    $messages = array();

    $driver->byCss("button.new")->click();

    // Create the pack
    $frm = "Edit-CPack";

    $driver->byId($frm . "_" . "group_id_view")->click();
    $driver->selectAutocompleteByText($frm . "_" . "group_id_view", "Etablissement")->click();

    $driver->getFormField($frm, "nom")->sendKeys($pack);

    $driver->selectOptionByValue($frm . "_" . "object_class", "CSejour");

    $driver->byCss("button.submit")->click();

    $messages[] = $this->getSystemMessage();

    // Add model to the pack created
    $frm = "Add-CPackToModele";

    $driver->selectOptionByText($frm . "_" . "modele_id", "Mod");

    $messages[] = utf8_decode($driver->byCss("#list-modeles-links > table > tbody > tr > td > span")->getText());

    return $messages;
  }
}