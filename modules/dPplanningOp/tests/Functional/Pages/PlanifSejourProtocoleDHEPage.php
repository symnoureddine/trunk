<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Planification Sejour page representation
 */
class PlanifSejourProtocoleDHEPage extends PlanifSejourAbstractPage {
  protected $tab_name    = "vw_protocoles";

  /**
   * Création d'un protocole de DHE nommé "coucou" d"une durée de 3 jours avec un temps opération d'une heure
   *
   * @param string $name_protocole Nom du protocole
   * @param bool   $interv         If true, create an intervention protocol
   *
   * @return null|string
   */
  public function createProtocoleDHE($name_protocole, $interv = true) {
    $driver = $this->driver;
    $driver->byId("didac_button_create_protocole")->click();
    $driver->changeFrameFocus();

    $driver->selectOptionByText("editProtocole_chir_id", "CHIR Test");

    $driver->byId("editProtocole_duree_hospi")->sendKeys("3");
    if ($interv) {
      $driver->byId("editProtocole_libelle")->sendKeys($name_protocole);
      $driver->byId("editProtocole_temp_operation_da")->click();
      $driver->byCss("div.datepickerControl  tr:nth-child(2) > td:nth-child(2)")->click();
      $driver->byCss("div.datepickerControl button.tick")->click();
      $driver->selectOptionByValue("editProtocole_cote", "droit");
    }
    else {
      $driver->byId('editProtocole_for_sejour_1')->click();
      $driver->byId('editProtocole_libelle_sejour')->sendKeys($name_protocole);
    }

    $driver->byId("didac_button_create_edit_protocole")->click();
    $driver->selectOptionByText("selectFrm_chir_id", "CHIR Test");

    return $this->getSystemMessage();
  }

  /**
   * Recherche de l'existance d'un protocole nommé pour le praticien CHIR TEST
   *
   * @param string $name_protocole Nom du protocole
   *
   * @return null|string
   */
  public function searchProtocole($name_protocole) {
    $driver = $this->driver;
    $driver->byId("selectFrm_search_protocole")->sendKeys($name_protocole);

    $driver->byCss("div.autocomplete li:first-child")->click();
    $driver->changeFrameFocus();

    return $driver->byId("editProtocole_libelle")->getAttribute('value');
  }
}