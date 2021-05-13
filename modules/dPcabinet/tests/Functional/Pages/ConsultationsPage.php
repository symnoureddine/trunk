<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Tests\Functional\Pages;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Ox\Core\CMbDT;
use Ox\Tests\HomePage;

/**
 * Consultation page representation
 */
class ConsultationsPage extends HomePage {

  protected $module_name = "dPcabinet";
  protected $tab_name = "vw_planning";

  /**
   * Open the plage creation modal
   *
   * @param string $date Date to create the consultation plage
   *
   * @return void
   */
  public function openPlageCreationModal($date) {
    $driver = $this->driver;

    $driver->byId("vw_planning_a_semaine");

    // Go to the week which correspond to the $date param
    $driver->setInputValueById('changeDate_debut', $date);

    if ($driver->getBrowserName() != "chrome") {
      sleep(3);
    }

    // Click on the create plage button
    $driver->byId("create_plage_consult_button")->click();
  }

  /**
   * Try to create a "plage de consultation"
   *
   * @param string $praticien_name Praticien user lastname and firstname
   * @param string $date           Date to create the consultation with format yyyy-mm-dd
   *
   * @return void
   */
  public function createPlageConsultation($praticien_name, $date) {
    $driver = $this->driver;

    // Select the right praticien
    $driver->selectOptionByText("editFrm_chir_id", $praticien_name);
    // Set the date of the new plage
    $driver->executeScript("\$V($$('#editFrm_date > option:nth-child(4)'),'$date')");
    // Set begin and end date
    $driver->selectOptionByValue("editFrm_date", $date);
    $date_debut = $driver->getFormField("editFrm", "debut_da");
    $date_debut->click();
    // double click on 8h
    $driver->byCss("tr.calendarRow:nth-child(3) > td:nth-child(3)")->click();
    $driver->byCss(".datepickerControl button.tick")->click();

    $date_debut = $driver->getFormField("editFrm", "fin_da");
    $date_debut->click();
    // double click on 18h
    $driver->byCss("tr.calendarRow:nth-child(5) > td:nth-child(1)")->click();
    $driver->byCss(".datepickerControl button.tick")->click();

    // Set plage frequency
    $driver->selectOptionByValue('editFrm__freq', '60');

    // Click on the create button
    $driver->byId("edit_plage_consult_button_create_new_plage")->click();

    // Wait until end of plage creation
    $driver->wait(30, 1000)->until(
      function () use ($driver) {
        // get the button elem
        $elem = $driver->findElements(WebDriverBy::id("edit_plage_consult_button_create_new_plage"));
        // if $elem is empty, button is no longer present and creation plage modal is closed
        return empty($elem);
      }
    );
  }


  /**
   * Create a new consultation for a patient with the specified praticien
   *
   * @param string $praticien_name  Praticien user last name and firstname
   * @param string $date            Date format yyyy-mm-dd
   * @param string $patientLastname Patient lastname
   *
   * @return void
   */
  public function createConsultation($praticien_name, $date, $patientLastname) {
    $driver = $this->driver;

    // Go to the "rendez-vous" tab
    $this->switchTab("edit_planning");
    // Wait for the page to load by getting an element
    $driver->getFormField("editFrm", "chir_id");
    // Select the right praticien
    $driver->selectOptionByText("editFrm_chir_id", $praticien_name);
    // Click on the patient field to open the modal
    $driver->getFormField("editFrm", "_patient_view")->click();
    // Search and select patient
    $this->patientModalSelector($patientLastname);
    // Reset the focus to the current window
    $driver->switchTo()->defaultContent();
    // Click on the date field to open the modal
    $driver->getFormField("editFrm", "_date")->click();
    // Change focus
    $driver->changeFrameFocus();
    $driver->waitForAjax("listePlages");
    // Select day option to get the right "plage de consultation"
    $driver->executeScript("updatePlage('$date')");
    $driver->waitForAjax("listePlages");
    // Click on the day
    $driver->byCss(
      "#listPlages_month_" .
      CMbDT::date('first day of +0 month', $date) .
      " > tbody > tr:nth-child(2) > td:nth-child(1) > div > div.text > a"
    )->click();
    // Wait for loading of the click's result
    $driver->waitForAjax("listPlaces-0");
    // Click on the 08h00 button
    $driver->byCss("button[data-time='08:00:00']")->click();
    // Change focus on current window
    $driver->switchTo()->defaultContent();
    // Save the rendez-vous
    $driver->byId("addedit_planning_button_submitRDV")->click();
    $driver->byId("print_fiche_consult");
  }

  /**
   * Select a plage on a specified date and click on the edit button
   *
   * @param string $date format yyyy-mm-dd
   * @param string $hour format H
   *
   * @return void
   */
  public function selectPlage($date, $hour = "08") {
    $driver = $this->driver;

    // Go to the week which correspond to the $date param
    $driver->setInputValueById("changeDate_debut", $date);
    try {
       // Select the plage by clicking on it
       $this->driver->byCss("td.segment-$date-$hour div > span > a:nth-child(1)")->click();
       // Click on edit button
       $driver->byCss("td.segment-$date-$hour > div > div > div.event.consultation > div > a.button.edit")->click();
    }
    catch (Exception $e) {
      // Problem with hover event, click with js
      $driver->executeScript(
        "$$('td.segment-$date-$hour > div > div > div.event.consultation > div > a.button.edit').each(
          function(elt) {
            elt.click();
          }
        )"
      );
    }
  }


  /**
   * Change the praticien on the "semainier" view
   *
   * @param string $praticien_id Praticien id
   *
   * @return void
   */
  public function changePraticien($praticien_id) {
    $driver = $this->driver;

    // Select the practicien with $praticien_id
    $driver->byId("changePrat_chirSel");
    $driver->setInputValueById("changePrat_chirSel", $praticien_id);

    // Chrome is too fast, wait for it...
    if ($driver->getBrowserName() == "chrome") {
      $driver->wait()->until(
        WebDriverExpectedCondition::urlContains("=$praticien_id&")
      );
    }
  }

  /**
   * Open actes view
   *
   * @return void
   */
  public function openViewActes() {
    $this->accessControlTab('facturation');
    $this->driver->byId('edit_actes')->click();
  }

  /**
   * Create a NGAP act
   *
   * @param string $code  The code to add
   * @param int    $price The price of the act
   *
   * @return void
   */
  public function createNGAPact($code, $price) {
    $this->openViewActes();
    $this->accessControlTab('ngap');
    $this->driver->byId('editActeNGAP-code_code')->value($code);
    $this->driver->byId('editActeNGAP-montant_base_montant_base')->value($price);
    $this->driver->byId('inc_codage_ngap_button_create')->click();
  }

  /**
   * Create a Tarmed act
   *
   * @param string $code  The code to add
   *
   * @return void
   */
  public function createTarmedact($code) {
    $this->openViewActes();
    $this->accessControlTab('tarmed_tab');
    $this->driver->byId('editTarmed_code')->value($code);
    $this->driver->selectAutocompleteByText('editTarmed_code', $code)->click();
    $this->driver->byId('button_add_CActeTarmed')->click();
  }

  /**
   * Close the cotation of a consultation
   *
   * @return void
   */
  public function closeCotation() {
    $this->accessControlTab('facturation');
    sleep(1);
    $this->driver->byId('reglements_button_cloturer_cotation')->click();
  }

  /**
   * Create a CTarif
   * There must be at least one act created on the consultation in order to create a CTarif,
   * and the cotation must be closed
   *
   * @return void
   */
  public function createTarifConsult() {
    $this->driver->byId('inc_vw_reglement_button_create_tarif')->click();
    $this->driver->changeFrameFocus();
    $this->driver->byId('vw_edit_tarif_button_save')->click();
  }

  /**
   * Add a CCAM code to the consultation
   *
   * @param string $code The CCAM code to add
   *
   * @return void
   */
  public function addCodeCCAM($code) {
    $this->openViewActes();
    $this->accessControlTab('ccam');
    $this->driver->byId('manageCodes__codes_ccam')->sendKeys($code);
    $this->driver->selectAutocompleteByText('manageCodes__codes_ccam', $code)->click();
  }

  /**
   * Create an act CCAM with the given code, activity and phase
   *
   * @param string $code     CCAM code
   * @param string $activity Activity
   * @param string $phase    Phase
   *
   * @return void
   */
  public function createCCAMAct($code, $activity, $phase) {
    $this->driver->byCss('#didac_inc_manage_codes_fieldset_executant button.edit')->click();
    $this->driver->changeFrameFocus();
    $xpath = "//form[contains(@name, 'codageActe-$code-$activity-$phase-')]//button[contains(@class, 'add')]";
    $this->driver->byXPath($xpath)->click();
    $this->closeModal();
  }

  /**
   * Validate the CCodageCCAM
   *
   * @return void
   */
  public function validateCCAMCodage() {
    $this->driver->byCss('#didac_inc_manage_codes_fieldset_executant button.tick')->click();
  }

  /**
   * Create an LPP act
   *
   * @param string $code The LPP code
   *
   * @return void
   */
  public function createLPPAct($code) {
    $this->openViewActes();
    $this->accessControlTab('lpp');
    $this->driver->byId('createActeLPP-code_code')->sendKeys($code);
    $this->driver->byId('createActeLPP-code_code')->click();
    $this->driver->byCss("input#createActeLPP-code_code + div ul li[data-code=\"$code\"")->click();
    $this->driver->byId('addActeLPP')->click();
  }

  /**
   * Open The view for creating the tarif
   *
   * @return void
   */
  public function openViewCreateTarif() {
    $this->switchTab('vw_edit_tarifs');

    /* Select the chir */
    $this->driver->selectOptionByText('selectPrat_prat_id', 'CHIR Test');

    /* Click on the new tarif button */
    $this->driver->byId('btn_new_tarif')->click();
  }

  /**
   * Set the acts of the tarif
   *
   * @param string  $ccam          The CCAM code
   * @param integer $ccam_activity The CCAM activity code
   * @param integer $ccam_phase    The CCAM phase code
   * @param string  $ngap          The NGAP act code
   * @param integer $ngap_price    The NGAP code's price
   *
   * @return void
   */
  public function setTarifActs($ccam, $ccam_activity, $ccam_phase, $ngap, $ngap_price) {
    /* Click on the acts button */
    $this->driver->byId('edit_actes_tarif')->click();

    $this->driver->byId('manageCodes__codes_ccam');

    /* Add the CCAM code */
    $this->driver->byId('manageCodes__codes_ccam')->sendKeys($ccam);
    $this->driver->selectAutocompleteByText('manageCodes__codes_ccam', $ccam)->click();

    $this->createCCAMAct($ccam, $ccam_activity, $ccam_phase);

    $this->accessControlTab('ngap');

    /* Set the code */
    $this->driver->byId('editActeNGAP-code_code')->sendKeys($ngap);
    /* Set the base price */
    $this->driver->byId('editActeNGAP-montant_base_montant_base')->sendKeys($ngap_price);
    /* Create the NGAP act */
    $this->driver->byId('inc_codage_ngap_button_create')->click();

    $this->driver->waitForAjax("listActesNGAP");

    /* Apply the modification to the tarif */
    $this->driver->byCss('form[name="editModelCodage"] button.tick')->click();
  }

  /**
   * Click on the button for creating the tarif
   *
   * @return void
   */
  public function createTarif() {
    /* Set the name of the tarif */
    $this->driver->byId('editFrm_description')->sendKeys('Test');

    /* Save the tarif */
    $this->driver->byId('vw_edit_tarif_button_save')->click();
  }

  /**
   * Return the value of the tarif field codes ccam
   *
   * @return string
   */
  public function getTarifCCAMActValue() {
    return $this->driver->byXPath("//input[@id='editFrm_codes_ccam']/following-sibling::div")->getText();
  }

  /**
   * Return the value of the tarif field codes ngap
   *
   * @return string
   */
  public function getTarifNGAPActValue() {
    return $this->driver->byXPath("//input[@id='editFrm_codes_ngap']/following-sibling::div")->getText();
  }

  /**
   * Test le click sur une info dans la checklist de la consultation
   *
   * @param string $name_info nom de l'info
   *
   * @return void
   */
  public function checkedInfosChecklist($name_info) {
    $this->accessControlTab('Examens');
    $this->driver->byXPath("//label[contains(text(),'$name_info')]/../input[@type='checkbox']")->click();
  }

  public function createPrescription() {
    $this->accessControlTab("fdrConsult");
    $this->driver->byXPath("//form[@name='addPrescriptionSejourfdr']//button[@class='new']")->click();
  }

  /**
   * Fill in the type of anesthesia
   *
   * @return array
   */
  public function selectAnesthesiaType() {
    $driver = $this->driver;
    $msg = array();
    // Reset default content
    $driver->switchTo()->defaultContent();

    $this->accessControlTab("InfoAnesth");
    $form = "editOpAnesthFrm";

    // Select type of anesthesia planned
    $driver->byXPath("//select[@id='". $form ."_type_anesth']//option[4]")->click();
    $msg[] = $this->getSystemMessage();

    // Link intervention to the consultation
    $driver->byCss("div#dossiers_anesth_area button.edit")->click();
    $driver->byCss("td.button button.link")->click();

    // Select type of anesthesia performed
    $this->accessControlTab("InfoAnesth");
    $driver->byXPath("//select[@id='". $form ."_type_anesth']//option[2]")->click();
    $msg[] = $this->getSystemMessage();

    return $msg;
  }

  /**
   * Open Pre-anesthetic consultation block sheet
   *
   * @return array
   */
  public function openBlockSheet() {
    $driver = $this->driver;

    $elt     = $driver->byXPath("//div[@id='dossiers_anesth_area']//span[2]");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->perform();
    $driver->byXPath("//button[contains(@onclick, 'Operation.dossierBloc')]")->click();

    $driver->changeFrameFocus();

    $driver->byXPath("(//ul[@id='main_tab_group']//button[@class='print'])[1]")->click();
    $driver->switchNewWindow();
    $types = array();

    $types[] = utf8_decode($driver->byXPath("//table[@class='print'][2]//td[@class='halfPane']//table[2]//tr[10]//th")->getText());
    $types[] = utf8_decode($driver->byXPath("//table[@class='print'][2]//td[@class='halfPane']//table[2]//tr[10]//td")->getText());
    $types[] = utf8_decode($driver->byXPath("//table[@class='print'][2]//td[@class='halfPane']//table[2]//tr[11]//th")->getText());
    $types[] = utf8_decode($driver->byXPath("//table[@class='print'][2]//td[@class='halfPane']//table[2]//tr[11]//td")->getText());

    return $types;
  }
}