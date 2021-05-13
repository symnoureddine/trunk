<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Planning collectif Representation
 */
class PlanningCollectifPage extends HomePage
{
  protected $module_name = "ssr";
  protected $tab_name = "vw_planning_collectif";

  /**
   * Création d'une trame
   *
   * @param string $nameTrame Nom de la trame
   *
   * @return void
   */
  public function createTrame($nameTrame) {
    $driver = $this->driver;

    //Sélection de la fonction
    $driver->byXPath("//select[@id='planning_collectif_filter_function_id']/option[2]")->click();

    //Click sur le bouton de création d'une nouvelle trame
    $driver->byXPath("//button[@class='new'][1]")->click();
    $driver->changeFrameFocus();

    $name_form = "Edit-CTrameSeanceCollective-none";
    //Renseignement du nom du plateau
    $driver->byId($name_form . "_nom")->sendKeys($nameTrame);

    //Enregistrement
    $driver->byCss("button.submit")->click();
  }

  /**
   * Création d'une plage pour la trame
   *
   * @param string $nameTrame Nom de la trame
   *
   * @return void
   */
  public function createPlage($nameTrame) {
    $driver = $this->driver;

    //Sélection de la fonction
    $driver->byXPath("//select[@id='planning_collectif_filter_function_id']/option[2]")->click();

    //Click sur le bouton de création d'une nouvelle plage collective
    $driver->byXPath("//button[@class='new'][2]")->click();

    $name_form = "Edit-CPlageSeanceCollective-none";
    //Choix de la Trame
    $driver->selectOptionByText($name_form."_trame_id", $nameTrame);
    //Choix du praticien
    $driver->selectOptionByText($name_form."_user_id", 'CHIR Test');

    //Choix de l'élément de prescription
    $libelle_elt = $driver->byId($name_form."_libelle");
    $libelle_elt->sendKeys('Test element');
    $libelle_elt->click();
    $driver->byCss("#element_prescription_id_autocomplete li:first-child")->click();

    //Choix du jour de la plage
    $driver->byXPath("//*[@id='Edit-CPlageSeanceCollective-none__days_week']/option[2]")->click();

    //Choix de l'heure de début
    $driver->byId($name_form."_debut_da")->click();
    $driver->byCss("tr.calendarRow:nth-child(3) > td:nth-child(3)")->click();
    $driver->byCss(".datepickerControl button.tick")->click();

    //Choix de la durée
    $driver->valueRetryByID($name_form."_duree", "60");

    //Enregistrement
    $driver->byCss("button.submit")->click();
  }

  /**
   * Liaison d'un patient à la plage collective
   *
   * @return void
   */
  public function addPatientPlage() {
    $driver = $this->driver;

    //Survol de la plage collective
    $path_plage = "//table[@id='planningWeek']//div[@class='event-container']/div/div";
    $driver->action()->moveToElement($driver->byXPath($path_plage))->perform();

    //Click sur le bouton "Gestion des patients"
    $driver->byCss("button.list")->click();

    //Choix du patient
    $driver->byCss(".patients_planning_collectif")->click();

    //Validation des choix
    $driver->byCss("#form_patients_planning_collectif button.tick")->click();
    $driver->acceptAlert();
  }
}