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
class ModelesPage extends HomePage {

  protected $module_name = "compteRendu";
  protected $tab_name = "vw_modeles";


  function createModele($username) {
    $driver = $this->driver;

    $form = "editFrm";

    // Click on the create button
    $driver->byId("didac_button_create")->click();

    // Change context to the iframe
    $driver->changeFrameFocus();

    // Fill the model's name
    $driver->getFormField($form, "nom")->value("NomModele");

    // Fill the owner
    $driver->byId($form . "_user_id_view")->click();
    $driver->selectAutocompleteByText($form . "_user_id_view", $username)->click();

    // Fill the targetted object_class
    $driver->byCss("#{$form}_object_class option[value='CSejour']")->click();

    // Click on the create button
    $driver->byId("button_addedit_modeles_create")->click();

    // Wait until end of modele creation
    $driver->wait(50, 1000)->until(
      // if button is present, the page is reloaded
      WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id("button_addedit_modeles_save_mise_en_page"))
    );
  }

  function openModele() {
    $driver = $this->driver;

    // Go the groups tab
    $this->accessControlTab("owner-etab");

    // Open the document
    $driver->byCss("span.CCompteRendu-view")->click();

    $driver->changeFrameFocus();
  }

  function launchPlugin($plugin, $focus_frame = true) {
    $driver = $this->driver;

    // Click on the plugin button
    $driver->byCss("a.cke_button__$plugin")->click();

    if ($focus_frame) {
      $driver->byId($plugin . "_iframe")->click();

      $driver->frame($plugin . "_iframe");
    }
  }

  function backToTheEditor($plugin) {
    $driver = $this->driver;

    // Hack to return to the editor modal
    $driver->frame(null);
    $driver->changeFrameFocus();
    $driver->byCss("a.cke_button__$plugin");
  }

  function testAddChamp($field) {
    $driver = $this->driver;

    $this->openModele();

    $this->launchPlugin("mbfields");

    // Explode the requested field
    @list($test_section, $test_field) = explode(" - ", $field);

    // Click the section
    $driver->selectOptionByValue("section", utf8_encode($test_section));

    // Double click the item
    $driver->selectOptionByText("item", utf8_encode($test_field));

    // Special case for ie which doesn't move to the correct location
    if ($driver->getBrowserName() == "internet explorer") {
      $driver->executeScript("$('item').ondblclick();");
    }
    else {
      $actions = $driver->action();
      $actions->moveToElement($driver->byXPath("//select[@id='item']/option[contains(text(),'$test_field')]"));
      $actions->doubleClick();
      $actions->perform();
    }

    $this->backToTheEditor("mbfields");

    // Access to the editor area
    $driver->switchTo()->frame($driver->byCss("#cke_1_contents iframe"));

    // Return the inserted field content
    return utf8_decode($driver->byCss("body.cke_editable span")->getText());
  }

  function testAddListeChoix($list) {
    $driver = $this->driver;

    $this->openModele();

    $this->launchPlugin("mblists");

    $driver->selectOptionByText("list", utf8_encode($list));
    $driver->action()->doubleclick()->perform();

    // Hack to return to the editor modal
    $this->backToTheEditor("mblists");

    // Access to the editor area
    $driver->switchTo()->frame($driver->byCss("#cke_1_contents iframe"));

    // Return the inserted field content
    return utf8_decode($driver->byCss("body.cke_editable span")->getText());
  }

  function testAddZoneTexteLibre($zone) {
    $driver = $this->driver;

    $this->openModele();

    $this->launchPlugin("mbfreetext");

    $driver->byId("txtData")->sendKeys($zone);

    $this->backToTheEditor("mbfreetext");

    $driver->byXPath("//span[@class='cke_dialog_ui_button'][contains(text(), 'OK')]")->click();

    // Access to the editor area
    $driver->switchTo()->frame($driver->byCss("#cke_1_contents iframe"));

    // Return the inserted field content
    return utf8_decode($driver->byCss("body.cke_editable span")->getText());
  }

  /**
   * @param string $file_name
   *
   * @return string
   */
  function testAddImage($file_name = "logo.jpg") {
    $driver = $this->driver;

    $this->openModele();

    $this->launchPlugin("image", false);

    // Open the picture explorer
    $driver->byXPath("//span[@class='cke_dialog_ui_button'][contains(text(), 'Explorer le serveur')]")->click();

    $driver->byCss("button.add")->click();

    // Add the image
    $form = "uploadFrm";

    $driver->byId($form . "_" . "formfile[]")->sendKeys("C:\\$file_name");

    // Submit the file
    $driver->byCss("button.submit")->click();

    // Select the image to add in the editor
    $actions = $driver->action();
    $actions->moveToElement($driver->byCss("div.icon_fileview"));
    $actions->doubleClick();
    $actions->perform();

    $driver->byXPath("//span[@class='cke_dialog_ui_button'][contains(text(), 'OK')]")->click();

    // Return the inserted field content
    $this->driver->switchTo()->frame($driver->byCss("#cke_1_contents iframe"));

    return utf8_decode($driver->byCss("body.cke_editable img")->getAttribute("src"));
  }

  /**
   * Add complete field (Section - item - subitem) in model and save it.
   *
   * @param string $field Field to add in a model
   *
   * @return array
   */
  function addCompleteFieldAndSaveIt($field) {
    $driver = $this->driver;

    $this->openModele();

    sleep(3);
    $this->launchPlugin("mbfields");

    // Explode the requested field
    @list($section, $item, $subitem) = explode(" - ", $field);

    // Click the section
    $driver->selectOptionByValue("section", utf8_encode($section));

    // Click the item
    $driver->selectOptionByText("item", utf8_encode($item));

    // Click the subitem
    $driver->selectOptionByText("subitem", utf8_encode($subitem));

    $driver->triggerDoubleClick("subitem");

    $this->backToTheEditor("mbfields");

    // Access to the editor area
    $driver->frame($driver->byCss("#cke_1_contents iframe"));

    $result['field'] = utf8_decode($driver->byCss("body.cke_editable span")->text());

    // Returns to the button area
    $driver->frame(null);
    $driver->changeFrameFocus();

    $driver->byCss("a.cke_button.cke_button__save.cke_button_off")->click();

    $result['msg'] = $this->getSystemMessage();

    $driver->frame(null);

    $this->closeModal();

    // Return the inserted field content
    return $result;
  }
}
