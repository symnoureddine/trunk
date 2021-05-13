<?php
/**
 * @package Mediboard\Eai\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * EAI page representation
 */
class EaiPage extends HomePage {

  protected $module_name =  "eai";
  protected $tab_name    = "vw_idx_interop_actors";

  /**
   * Create destinataire HL7v2
   *
   * @param string $nom_destinataire Nom du destinataire
   *
   * @return void
   */
  public function createDestinataireHL7v2($nom_destinataire) {

    $driver = $this->driver;
    $driver->selectElementByText("HL7v2")->click();
    $driver->changeFrameFocus();
    // Virer le parametre et le mettre cote du test
    $driver->byId("editCReceiverHL7v2-_nom")->sendKeys($nom_destinataire);
    $driver->byId("editCReceiverHL7v2-_group_id_autocomplete_view")->click();
    $driver->selectAutocompleteByText("editCReceiverHL7v2-_group_id_autocomplete_view", "Etablissement")->click();
    $element = $driver->byId("editCReceiverHL7v2-_actif_1");
    $element = $driver->byId("labelFor_editCReceiverHL7v2-_role_qualif");
    $element->click();
    // on submit le form a partir d'un élement du form
    $element->submit();

  }

  /**
   * Add exchange HL7v2
   *
   * @param string $nom_destinataire Nom du destinataire
   *
   * @return void
   */
  public function addExchangeHL7v2($nom_destinataire) {
    $driver = $this->driver;
    $driver->byXPath("//*/span[contains(text(), '$nom_destinataire')]")->click();
    $driver->byCss("#actor fieldset button")->click();
    $driver->changeFrameFocus();

    // Récupère ADT^A28
    $driver->byXPath("//*[@id='CPAM']//*[contains(text(), 'ADT^A28')]/../preceding-sibling::td//a", 40000)->click();
    $driver->byCss(".close.notext")->click();
  }

  /**
   * Add configuration on receiver
   *
   * @return void
   */
  public function addConfigurationReceiver() {
    $driver = $this->driver;
    $driver->byCss("#actor_tools .control_tabs li:nth-child(4) a")->click();
    $driver->byCss('a[href="#object-config-version"]')->click();
    $driver->selectOptionByText("editObjectConfig-_ITI30_HL7_version", "2.5");

    $driver->byCss('a[href="#object-config-send"]')->click();
    $driver->selectOptionByText("editObjectConfig-_send_all_patients", "Oui");
    $driver->byCss("form[name=editObjectConfig-]")->submit();
  }

  /**
   * Get name of destinataire
   *
   * @return string
   */
  public function getDestinataireName() {
    $driver = $this->driver;
    $driver->byCss("a[title$='HL7v2']")->click();
    return $driver->byCss("#exchangesList tbody:nth-child(2) .exchange-receiver")->getText();
  }

  /**
   * Check if the HL7 message code equals A28
   *
   * @return bool
   */
  public function isCodeA28() {
    $driver = $this->driver;
    $elements = $driver->findElements(WebDriverBy::xpath("//*[@id='exchangesList']//*[contains (text(), 'A28')]"));
    return count($elements) > 0;
  }

  /**
   * Check if patient's name equals the name of the created patient
   *
   * @param string $nom patient's name
   *
   * @return bool
   */
  public function isRightPatient($nom) {
    $driver = $this->driver;
    $driver->action()->moveToElement($driver->byCss("#exchangesList tbody:nth-child(2) td:nth-child(6) span"))->perform();
    $driver->byXPath("//*[contains (text(), '$nom')]");
    return true;
  }
}