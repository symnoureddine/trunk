<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Plateau technique Representation
 */
class PlateauTechniquePage extends HomePage
{
  protected $module_name = "ssr";
  protected $tab_name = "vw_idx_plateau";

  /**
   * Création d'un plateau technique
   *
   * @param string $namePlateau Nom du plateau
   *
   * @return void
   */
  public function createPlateauTechnique($namePlateau) {
    $driver = $this->driver;

    $name_form = "Edit-CPlateauTechnique";
    //Click sur le bouton de création d'un nouveau plateau
    $driver->byXPath("//form[@name='$name_form']/a[@class='button new']")->click();

    //Renseignement du nom du plateau
    $driver->byId($name_form . "_nom")->sendKeys($namePlateau);

    //Enregistrement
    $driver->byCss("button.submit")->click();
  }

  /**
   * Test d'ajout de technicien pour un plateau technique
   *
   * @param string $namePlateau Nom du plateau
   *
   * @return void
   */
  public function addTechnicienPlateau($namePlateau) {
    $driver = $this->driver;

    //Sélection du plateau technique
    $driver->byXPath("//a[contains(text(),'$namePlateau')]")->click();

    $this->accessControlTab("techniciens");

    //Click sur le bouton d'ajout de technicien
    $driver->byXPath("//div[@id='techniciens']/a[@class='button new']")->click();

    //Sélection du technicien
    $driver->byXPath("//select[@id='Edit-CTechnicien_kine_id']/option[2]")->click();

    //Enregistrement
    $driver->byXPath("//div[@id='edit-techniciens']//button[@class='submit']")->click();
  }

  /**
   * Test d'ajout d'équipement pour un plateau technique
   *
   * @param string $namePlateau    Nom du plateau
   * @param string $nameEquipement Nom de l'équipement
   *
   * @return void
   */
  public function addEquipementPlateau($namePlateau, $nameEquipement) {
    $driver = $this->driver;

    //Sélection du plateau technique
    $driver->byXPath("//a[contains(text(),'$namePlateau')]")->click();

    $this->accessControlTab("equipements");

    //Click sur le bouton d'ajout d'équipeùent
    $driver->byXPath("//div[@id='equipements']/a[@class='button new']")->click();

    //Ajout du nom de l'équipement
    $driver->getFormField("Edit-CEquipement", "nom")->sendKeys($nameEquipement);

    //Enregistrement
    $driver->byXPath("//div[@id='equipements']//button[@class='submit']")->click();
  }
}