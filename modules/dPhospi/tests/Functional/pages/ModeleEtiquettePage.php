<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Modèle d'étiquette page representation
 */
class ModeleEtiquettePage extends HospiAbstractPage {

  protected $tab_name = "vw_etiquettes";

  function createModeleEtiquette() {
    $driver = $this->driver;

    $form = "edit_etiq";

    // Click on the create button
    $driver->byClassName("new")->click();

    // Fill the model's name
    $driver->getFormField($form, "nom")->sendKeys("NomModeleEtiquette");

    // Fill the targetted object_class
    $driver->selectOptionByValue($form . "_object_class", "CSejour");

    // Fill the firt cell of text content
    $driver->getFormField($form, "texte")->sendKeys("Texte");

    // Click on the create button
    $driver->byId($form . "_modify")->click();

    // Wait until end of modele creation
    $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('th.modify')));
  }
}