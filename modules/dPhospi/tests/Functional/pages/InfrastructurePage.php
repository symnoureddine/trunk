<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Création de l'infrastructure page representation
 */
class InfrastructurePage extends HospiAbstractPage {

  protected $tab_name    = "vw_idx_infrastructure";

  /**
   * Création d'un secteur
   *
   * @param string $name_secteur Nom du secteur à créer
   *
   * @return null|string
   */
  public function createSecteur($name_secteur) {
    $driver = $this->driver;
    $driver->byCss("#tabs-chambres > li:nth-child(1) > a")->click();
    $driver->byCss("#secteurs > table > tbody > tr:nth-child(1) > th > button")->click();
    $driver->changeFrameFocus();

    $driver->byId("Edit-CSecteur_nom")->sendKeys($name_secteur);
    $driver->byId("Edit-CSecteur_description")->sendKeys("description secteur");
    $driver->byCss("#anonymous_element_1 > form > table > tbody > tr:nth-child(4) > td > button")->click();
    return $this->getSystemMessage();
  }

  /**
   * Création d'un service
   *
   * @param string $name_service Nom du service à créer
   *
   * @return null|string
   */
  public function createService($name_service) {
    $driver = $this->driver;
    $driver->byCss("#tabs-chambres > li:nth-child(2) > a")->click();
    $driver->byCss("#list_services > tbody:nth-child(1) > tr:nth-child(1) > th button")->click();

    $driver->changeFrameFocus();

    $driver->byId("editCService-none_nom")->sendKeys($name_service);
    $code = $driver->byId("editCService-none_code");
    $code->sendKeys($name_service);
    $code->submit();

    return $driver->selectElementByText($name_service)->getText();
  }

  /**
   * Création d'une chambre
   *
   * @param string $name_service Nom du service à créer
   * @param string $name_chambre Nom de la chambre à créer
   *
   * @return null|string
   */
  public function createChambre($name_service, $name_chambre) {
    $driver = $this->driver;
    $driver->byCss("#tabs-chambres > li:nth-child(2) > a")->click();
    $driver->selectElementByText($name_service)->click();
    $driver->byXPath("//*[contains(text(),'$name_service')]/following::tbody/tr/th/button")->click();
    $driver->changeFrameFocus();

    $nom_chambre = $driver->byId("editCChambre-none_nom");
    $nom_chambre->sendKeys($name_chambre);
    $nom_chambre->submit();
    return $this->getSystemMessage();
  }
  /**
   * Création d'un lit
   *
   * @param string $name_lit Nom du lit
   *
   * @return null|string
   */
  public function createLit($name_lit) {
    $driver = $this->driver;
    $driver->byXPath("//table[@id='lits']/preceding-sibling::button[@class='new']")->click();
    $driver->byId("nom")->sendKeys($name_lit);
    $driver->byXPath("//form[@name='editLitCLit-none']/button")->click();
    return $this->getSystemMessage();
  }

  /**
   * Affectation d'un service à un secteur
   *
   * @param string $name_secteur Nom du secteur
   * @param string $name_service Nom du service
   *
   * @return null|string
   */
  public function addServiceToSecteur($name_secteur, $name_service) {
    $driver = $this->driver;
    $driver->byCss("#tabs-chambres > li:nth-child(1) > a")->click();
    $driver->byXPath("//td[contains(text(),'$name_secteur')]/preceding-sibling::td/button")->click();
    $driver->changeFrameFocus();
    $driver->byId("addService__service_autocomplete")->sendKeys($name_service);
    $driver->byCss("div.autocomplete li:first-child")->click();
    return $driver->byXPath("//table[@id='services_secteur']//span[contains(text(),'$name_service')]")->getText();
  }

  /**
   * Création d'une UF
   *
   * @param string $name_uf Nom de l'uf
   * @param string $type    Type de l'uf
   * @param string $code    Code de l'uf
   *
   * @return null|string
   */
  public function createUF($name_uf, $type, $code) {
    $driver = $this->driver;
    $this->accessControlTab('UF');
    $driver->byCss("#result-ufs a.new")->click();
    $driver->changeFrameFocus();

    $form = "Edit-CUniteFonctionnelle";
    $driver->byId("{$form}_type_{$type}")->click();
    $driver->byId("{$form}_code")->sendKeys($code);
    $driver->byId("{$form}_libelle")->sendKeys($name_uf);

    $driver->byCss("form[name=$form] button.submit")->click();
    return $this->getSystemMessage();
  }

}