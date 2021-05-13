<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * Horaires Affectation page representation
 */
class HorairesAffectationPage extends HomePage {
  protected $module_name = "soins";
  protected $tab_name = "vw_timings_affectation_sejour";

  /**
   * Ajout d'un horaire
   *
   * @param string $nom   Nom de l'horaire
   * @param string $debut Heure de début
   * @param string $fin   Heure de fin
   *
   * @return int
   */
  public function testAddHoraire($nom, $debut, $fin) {
    $driver = $this->driver;

    //Click sur le bouton d'ajout
    $driver->byCss('button.new')->click();

    $name_form = "Edit-CTimeUserSejour-none_";
    // Renseignement des champs
    $driver->byId($name_form."name")->sendKeys($nom);
    $driver->byId($name_form."time_debut_da")->click();
    $driver->byXPath("//div[@class='datepickerControl']//td[contains(@class, 'hour')][text()='$debut']")->click();
    $driver->byCss("div.datepickerControl button.tick")->click();
    $driver->byId($name_form."time_fin_da")->click();
    $driver->byXPath("//div[@class='datepickerControl']//td[contains(@class, 'hour')][text()='$fin']")->click();
    $driver->byCss("div.datepickerControl button.tick")->click();

    // Enregistrement
    $driver->byCss("button.submit")->click();

    $elements = $driver->findElements(WebDriverBy::xpath("//td[contains(text(),'$nom')]"));
    return count($elements) > 0;
  }
}