<?php
/**
 * @package Mediboard\Forms\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Form page representation
 */
class FormPage extends HomePage {
  protected $module_name = "forms";
  protected $tab_name = "view_ex_class";

  /**
   * Créer un formulaire
   *
   * @param float $formName form name
   *
   * @return void
   */
  public function createForm($formName) {
    $driver = $this->driver;

    $driver->byId("edit-CExClass-none_name")->click();
    $driver->byId("edit-CExClass-none_name")->sendKeys($formName);

    //Select a group
    $driver->selectOptionByText("edit-CExClass-none_group_id", "Etablissement");

    //Select a view MB
    $driver->byXPath("//input[@type='checkbox' and @value='atcd']")->click();

    //Save form
    $driver->byCss("button.submit")->click();

    $driver->waitForAjax("object-editor");
  }

  /**
   * Créer une étiquette
   *
   * @param float $tagName tag name
   *
   * @return void
   */
  public function createTag($tagName) {
    $driver = $this->driver;

    // Open tag manager
    $driver->byCss("td#object-editor button.tag-edit")->click();

    //Add a new tag
    $driver->byXPath("//button[@class='cleanup']//following-sibling::button")->click();
    $driver->changeFrameFocus();

    $driver->byId("edit_tag_CTag-none_name")->sendKeys($tagName);

    //Choose a color
    $driver->byCss("div.sp-dd")->click();
    $driver->byCss("div.sp-palette-row-3 span:nth-child(5)")->click();
    $driver->byCss("button.sp-choose")->click();

    //Save tag
    $driver->byCss("td.button button.new")->click();
  }

  /**
   * Fermer la modale
   *
   * @return void
   */
  public function closeModal() {
    $driver = $this->driver;

    $driver->byXPath("(//button[@class='close notext'])[1]")->click();
  }

  /**
   * Associer une étiquette au formulaire créé précédemment
   *
   * @param float $tagName tag name
   *
   * @return void
   */
  public function associateTagWithForm($tagName) {
    $driver = $this->driver;

    // Open tag manager
    $driver->byXPath("//input[@name='_bind_tag_view']")->sendKeys($tagName);

    $driver->byCss("div.autocomplete span.view")->click();
  }
}