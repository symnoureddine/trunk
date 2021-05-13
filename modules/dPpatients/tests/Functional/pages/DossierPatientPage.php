<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Tests\Functional\Pages;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Ox\Tests\HomePage;

/**
 * DossierPatient page representation
 */
class DossierPatientPage extends HomePage {

  protected $module_name = "dPpatients";
  protected $tab_name = "vw_idx_patients";

  /**
   * Search a patient by his name on the Dossier Patient page
   *
   * @param string $name Patient lastname
   *
   * @return void
   */
  public function searchPatientByName($name) {
    $driver = $this->driver;

    // Select the search input, clear it and set value
    $findNomField = $driver->byId("find_nom");
    $findNomField->click();
    $findNomField->clear();

    $findPrenomField = $driver->byId("find_prenom");
    $findPrenomField->click();
    $findPrenomField->clear();

    $driver->valueRetryByID("find_nom", $name);
    // Click on research button
    $driver->byId("ins_list_patient_button_search")->click();
  }

  /**
   * Search a patient by his firstname on the Dossier Patient page
   *
   * @param string $firstname Patient firstname
   *
   * @return void
   */
  public function searchPatientByFirstName($firstname) {
    $driver = $this->driver;

    // Select the search input, clear it and set value
    $findNomField = $driver->byId("find_nom");
    $findNomField->clear();
    $findPrenomField = $driver->byId("find_prenom");
    $findPrenomField->click();
    $driver->valueRetryByID("find_prenom", $firstname);
    // Click on research button
    $driver->byId("ins_list_patient_button_search")->click();
  }

  /**
   * Search a patient by his name and his firstname on the Dossier Patient page
   *
   * @param string $name      Patient lastname
   * @param string $firstname Patient firstname
   *
   * @return void
   */
  public function searchPatientByNameAndFirstname($name, $firstname) {
    $driver = $this->driver;

    // Select the search input, clear it and set value
    $findNomField = $driver->byId("find_nom");
    $findNomField->click();
    $findNomField->clear();

    $findPrenomField = $driver->byId("find_prenom");
    $findPrenomField->click();
    $findPrenomField->clear();

    $driver->valueRetryByID("find_nom", $name);
    $driver->valueRetryByID("find_prenom", $firstname);
    // Click on research button
    $driver->byId("ins_list_patient_button_search")->click();
  }

  /**
   * Try to create a patient with the given params
   *
   * @param string $firstname   Patient firstname
   * @param string $gender      Patient gender with format m|f
   * @param string $birthDate   Patient birth date with format dd/mm/yyyy
   * @param bool   $isAnonymous Create an anonymous patient
   *
   * @return void
   */
  public function createPatient($firstname, $gender, $birthDate, $isAnonymous = false) {
    $driver = $this->driver;

    // Avoid click on a disable button
    $buttonStyle = $this->driver->byId("vw_idx_patient_button_create")->getAttribute("style");
    if ($buttonStyle != "") {
      $driver->wait(30, 1000)->until(
        function () {
          return ($this->driver->byId("vw_idx_patient_button_create")->getAttribute("style") == "");
        }
      );
    }

    // Click on the create patient button
    $driver->byId("vw_idx_patient_button_create")->click();

    // Change the focus on the modal
    $driver->changeFrameFocus();

    if ($isAnonymous) {
      $driver->byXPath("//*[@id='editFrm_nom']/following-sibling::*")->click();
    }
    else {
      // Select, clear and set value of the firstname field
      $firstnameFormField = $driver->getFormField("editFrm", "prenom");
      $firstnameFormField->clear();
      $firstnameFormField->sendKeys($firstname);
    }
    // Set the gender
    if ($gender == "m") {
      $driver->byId("labelFor_editFrm_sexe_m")->click();
    }
    elseif ($gender == "f") {
      $driver->byId("labelFor_editFrm_sexe_f")->click();
    }

    // Select and set birthdate field
    $driver->valueRetryByID("editFrm_naissance", $birthDate);

    // Click on modal to trigger checkDoublon() event
    if ($driver->getBrowserName() == "firefox") {
      // Hack for firefox which fails to click on the submit button
      // if we click on the patient_identite element
      $driver->getFormField("editFrm", "prenom")->click();
    }
    $driver->byId("patient_identite")->click();

    // Submit the form
    $btnSubmit = $driver->byId("submit-patient");
    $btnSubmit->click();

    // Change focus on current window
    $driver->switchTo()->defaultContent();
  }

  /**
   * Create a patient full and make him anonymous.
   *
   * @param array $patient_datas Patient datas
   * @param bool  $isAnonymous   Make an anonymous patient
   *
   * @throws \Facebook\WebDriver\Exception\NoSuchElementException
   * @throws \Facebook\WebDriver\Exception\TimeOutException
   * @return array
   */
  public function createPatientFull($patient_datas, $isAnonymous = false) {
    $driver  = $this->driver;
    $form    = "editFrm";
    $label   = "labelFor_$form";
    $selects = array(
      "situation_famille",
      "mdv_familiale",
      "condition_hebergement",
      "rang_naissance",
      "niveau_etudes",
      "activite_pro",
      "qual_beneficiaire");
    $values  = array();

    // Avoid click on a disable button
    $buttonStyle = $this->driver->byId("vw_idx_patient_button_create")->getAttribute("style");
    if ($buttonStyle != "") {
      $driver->wait(30, 1000)->until(
        function () {
          return ($this->driver->byId("vw_idx_patient_button_create")->getAttribute("style") == "");
        }
      );
    }

    // Click on the create patient button
    $driver->byId("vw_idx_patient_button_create")->click();

    // Change the focus on the modal
    $driver->changeFrameFocus();

    foreach ($patient_datas as $key_data => $_data) {
      if (in_array($key_data, $selects)) {
        $driver->selectOptionByValue($form . "_" . $key_data, $_data);
      }
      elseif ($_data == null) {
        $driver->getFormField($label, $key_data)->click();
      }
      elseif ($_data == "yes") {
        $driver->getFormField($form, $key_data)->click();
      }
      else {
        $driver->getFormField($form, $key_data)->sendKeys($_data);
      }
    }

    if ($isAnonymous) {
      $driver->byXPath("//*[@id='editFrm_nom']/following-sibling::*")->click();
    }

    // Get the patient name and his first name in the form after anonymization
    $value_name         = $driver->getFormField($form, "nom")->getAttribute("value");
    $value[$value_name] = $driver->getFormField($form, "prenom")->getAttribute("value");

    // Submit the form
    $btnSubmit = $driver->byId("submit-patient");
    $btnSubmit->click();

    // Change focus on current window
    $driver->switchTo()->defaultContent();

    return $value;
  }

  /**
   * Get the patient datas (Sex, birthday, address, email)
   *
   * @return array
   */
  public function getPatientDatas() {
    $driver = $this->driver;
    $datas  = array();

    $datas["sex"]      = $driver->byXPath("//*[@id='vwPatient']//*[@for='sexe']/../following-sibling::td[1]")->getText();
    $datas["birthday"] = $driver->byXPath("//*[@id='vwPatient']//*[@for='naissance']/../following-sibling::td[1]")->getText();
    $datas["address"]  = $driver->byXPath("//*[@id='vwPatient']//*[@for='adresse']/../following-sibling::td[1]")->getText();
    $datas["email"]    = $driver->byXPath("//*[@id='vwPatient']//*[@for='email']/../following-sibling::td[1]")->getText();

    return $datas;
  }

  /**
   * Try to create a "consultation immédiate"
   * vwPatient needs to be open
   *
   * @param string $praticien_name Praticien user lastname and firstname
   *
   * @return void
   */
  public function createConsultationImmediate($praticien_name) {
    $driver = $this->driver;

    // Click on "Consultation immediate" button
    $driver->byXPath("//button[contains(@onclick, 'openConsultImmediate')]")->click();

    $driver->selectOptionByText("addConsultImmediate__prat_id", $praticien_name);
    // Submit the form
    $driver->getFormField("addConsultImmediate", "_prat_id")->submit();
  }

  /**
   * Try to remove a patient using edit button
   *
   * @return void
   */
  public function removePatient() {
    $driver = $this->driver;

    $driver->byCss(".edit")->click();
    $driver->byCss(".trash")->click();
    $driver->acceptAlert();
    $driver->byCss("#patient_identite > tbody:nth-child(1) > tr:nth-child(3) > td:nth-child(2) > button:nth-child(2)");
  }

  /**
   * Try to get the patient name by a CSS selector
   * Usefull for assertion (vwPatient needs to be open)
   *
   * @return string Patient Name
   */
  public function getPatientName() {
    return $this->driver->byXPath("//*[@id='vwPatient']//*[@for='nom']/../following-sibling::td[1]")->text();
  }

  /**
   * Try to get the patient name by a CSS selector
   * Usefull for assertion (vwPatient needs to be open)
   *
   * @return string Patient Fisrtame
   */
  public function getPatientFirstname() {
    return $this->driver->byCss(
      "table.form:nth-child(1) > tbody:nth-child(1) > tr:nth-child(4) > td:nth-child(2)"
    )->text();
  }

  /**
   * Check whether patient search has exact matches or not
   *
   * @return bool
   */
  public function hasExactMatches() {
    $driver = $this->driver;
    $driver->waitForAjax('search_result_patient');
    $elems = $driver->findElementsByCss("#search_result_patient td.empty");

    return empty($elems);
  }

  /**
   * Get the sejour count by searching for .actionPat class
   * vwPatient needs to be open
   *
   * @return int Sejour count
   */
  public function getSejourCount() {
    $driver = $this->driver;

    $driver->waitForAjax('vwPatient');

    return count($driver->byCss(".actionPat"));
  }

  /**
   * Click on the edit sejour button
   * vwPatient needs to be open
   *
   * @return PlanifSejourPlanningPage Page without url connection
   */
  public function editSejour() {
    $driver = $this->driver;
    $driver->byCss(".actionPat")->click();

    return new PlanifSejourPlanningPage($driver, false);
  }

  /**
   * Search and select the first two patients on the vwPatient
   *
   * @return void
   */
  public function selectMergePatients() {
    $driver = $this->driver;

    sleep(2);
    $elements = $driver->findElementsById('fusion_objects_id[]');
    $elements[0]->click();
    $elements[1]->click();
    $driver->byCss("button.merge")->click();
    $driver->window('merge_patients');
  }

  /**
   * Test upload file on a patient
   * The file to upload must be present either on /var/tmp or C:\
   *
   * @param string $file_name File name
   *
   * @return string
   */
  public function testUploadFilePatient($file_name) {
    $driver = $this->driver;

    // Search the patient
    $this->searchPatientByFirstName("Patientfirstname");

    // Go to the complete folder
    $driver->byCss("#search_result_patient a.search")->click();

    // Open the file upload modal
    $driver->byCss("button.rtl")->click();

    $driver->byCss("#button_toolbar > button")->click();

    // Select the file to upload
    $form = "uploadFrm";

    // Only works for Windows and Linux
    $path = PHP_OS == 'WINNT' ? "C:\\tests\\" : "/var/tmp/";
    $driver->getFormField($form, "formfile[]")->value($path . $file_name);

    // Submit the file
    $driver->byCss("button.submit")->click();

    // Return the name of the uploaded file
    return trim($driver->byCss("#Category-0 > div > table > tbody > tr > td.text > span")->text());
  }

  public function testPrintDocPatient() {
    $driver = $this->driver;

    $window = $driver->getWindowHandles();

    $this->searchPatientByFirstName("Patientfirstname");

    // Go to the complete folder
    $driver->byCss("#search_result_patient a.search")->click();

    $driver->byCss("button.rtl")->click();

    $driver->byCss("#button_toolbar input[type='text']")->click();
    $driver->byXPath("//div[@id='button_toolbar']//div[contains(text(), 'Mod')]")->click();

    // Save the doc
    $driver->switchNewWindow();

    $driver->byCss("button.save")->click();

    $driver->switchTo()->window($window[0]);

    // Try to print
    $driver->byCss("#Category-0 button.print")->click();

    // Remove focus on button
    $driver->byCss("body")->click();

    sleep(2);
    // Type to open info alert of the navigator (if print popup displayed, it will do nothing)
    $driver->getKeyboard()->sendKeys(
      array(WebDriverKeys::ALT, 'y')
    );

    try {
      $alert = $driver->switchTo()->alert();
      $alert->getText();

      return false;
    }
    catch (Exception $e) {
      return true;
    }
  }

  /**
   * Select a patient and his consultation
   *
   * @return void
   */
  public function selectPatientAndConsultation() {
    $driver = $this->driver;

    $driver->byCss("div.noted a")->click();
    $driver->byXPath("(//span[contains(text(), 'Consultation ')])[1]")->click();
  }

  /**
   * Create a work stopping
   *
   * @return void
   */
  public function createWorkStopping() {
    $driver = $this->driver;

    $this->accessControlTab('facturation');
    $driver->byCss("div#arret_travail button")->click();

    $driver->byId("formArretTravail_duree")->sendKeys(5);

    $driver->byId("formArretTravail_libelle_motif")->sendKeys("gen");
    $driver->byCss("div#motif_autocomplete li.selected")->click();

    $driver->byXPath("//td[@colspan='2']//button[@class='new']")->click();
  }

  /**
   * Create the first Cerfa of the list
   *
   * @param string $cerfa_name Cerfa name
   *
   * @return void
   */
  public function createCerfa($cerfa_name) {
    $driver = $this->driver;

    $this->accessControlTab('fdrConsult');
    $input = $driver->byCss("input.listCerfa");
    $input->clear();
    $input->sendKeys($cerfa_name);

    $driver->byCss("div.autocomplete li.selected")->click();

    // Get base window
    $parentWindow = $driver->getWindowHandles();

    // Changement de fenêtre pour cibler la popup du cerfa
    $driver->switchNewWindow();

    $driver->byXPath("//div[@id='page-1']//button[@class='save'][1]")->click();

    $driver->switchTo()->window($parentWindow[0]);
  }

  /**
   * Get Cerfa name
   *
   * @return string name of the PDF
   */
  public function getCerfaName() {
    return $this->driver->byCss("td.docitem a.action")->getText();
  }

  /**
   * Open dossier soins
   *
   * @param string $patientLastname Patient last name
   *
   * @return void
   */
  public function openModalDossierSoins($patientLastname) {
    $driver = $this->driver;

    $dp_page = new DossierPatientPage($driver, false);
    $dp_page->searchPatientByName($patientLastname);

    $driver->byCss("div#search_result_patient tr.patientFile div.noted a")->click();
    $driver->waitForAjax("vwPatient");

    $elt     = $driver->byXPath("(//span[contains(text(), 'Du ')])[1]");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->perform();
    $driver->byXPath("//button[contains(@onclick, 'showDossierSoinsModal')]")->click();

    $driver->changeFrameFocus();
  }

  /**
   * Create antecedents and antecedents absent
   *
   * @return void
   */
  public function createAntecedentsAndAntecedentsAbsent() {
    // dossier soins
    $this->accessControlTab("antecedents");

    // Atcds
    $this->fillFormAntecedent("Menthe", "alle", "pulmonaire", "important");
    $this->getSystemMessage();
    $this->fillFormAntecedent("Gluten", "alle", null, "majeur");
    $this->getSystemMessage();
    $this->fillFormAntecedent("Choco", "alle", null, null, true);
    $this->getSystemMessage();
    $this->fillFormAntecedent("Iode", "alle", null, null);
    $this->getSystemMessage();
  }

  /**
   * Create antecedents and antecedents absent
   *
   * @param string $name     Antecedent name
   * @param string $type     Antecedent type
   * @param string $appareil Antecedent appareil
   * @param string $level    Antecedent level
   * @param bool   $absent   Antecedent absent
   *
   * @return void
   */
  public function fillFormAntecedent($name, $type = null, $appareil = null, $level = null, $absent = false) {
    $driver = $this->driver;

    $form = "editAntFrm";
    $driver->getFormField($form, "rques")->sendKeys($name);

    if ($type) {
      $driver->selectOptionByValue($form . "_type", $type);
    }

    if ($appareil) {
      $driver->selectOptionByValue($form . "_appareil", $appareil);
    }

    if ($level) {
      $driver->byId($form . "___$level")->click();
    }

    if ($absent) {
      $driver->byId($form . "___absence")->click();
    }

    $driver->byId("inc_ant_consult_trait_button_add_atcd")->click();
  }

  /**
   * Check antecedents number
   *
   * @Param int $choose_tab Choose tab antecedent
   *
   * @return int
   */
  public function checkAntecedentNumber($choose_tab) {
    $driver = $this->driver;
    $atcd   = $driver->findElements(WebDriverBy::xpath("(//ul[@class='tab-container'])[$choose_tab]//li[@class='type_antecedent']//li//li"));

    return count($atcd);
  }

  /**
   * Get Count antecedent
   *
   * @Param int $choose_tab Choose tab antecedent
   *
   * @return int
   */
  public function getCountAtcd($choose_tab) {
    $driver = $this->driver;
    $driver->byXPath("((//ul[@class='control_tabs small'])[3]//li//a)[$choose_tab]")->click();

    $elt = $driver->byXPath("((//ul[@class='control_tabs small'])[3]//li//a//small)[$choose_tab]")->getText();
    $elt = str_replace(array("(", ")"), '', $elt);

    return intval($elt);
  }

  /**
   * Check antecedent level flags in patient banner
   *
   * @return bool
   */
  public function checkFlagsAtcdLevel() {
    $driver = $this->driver;
    $driver->byCss("span#atcd_majeur span")->click();

    $flags = $driver->findElements(WebDriverBy::cssSelector("span#atcd_majeur span"));

    return count($flags) > 1;
  }

  /**
   * Check allergie flag
   *
   * @return int
   */
  public function checkAllergieFlag() {
    $driver   = $this->driver;
    $allergie = $driver->findElements(WebDriverBy::cssSelector("span.texticon.texticon-allergies-warning"));

    return count($allergie);
  }

  /**
   * Create correspondence
   *
   * @return void
   */
  public function createCorrespondence() {
    $driver = $this->driver;
    $driver->byCss("td#vwPatient button.edit")->click();

    $this->accessControlTab("correspondance");
    $driver->byCss("div#correspondance button.add")->click();
    $driver->changeFrameFocus();

    $form = "editCorrespondant";
    $driver->selectOptionByValue($form . "_parente", "ami");
    $driver->getFormField($form, "nom")->sendKeys('Pennyworth');
    $driver->getFormField($form, "prenom")->sendKeys('Alfred');

    $driver->byCss("button.save")->click();
  }

  /**
   * Create medical correspondents
   *
   * @param bool  $direct_access Direct access to medical correspondents
   * @param array $names         Correspondents name
   * @param array $firstnames    Correspondents first name
   *
   * @return void
   */
  public function createMedicalCorrespondents($direct_access = false, $names, $firstnames) {
    $driver = $this->driver;

    if ($direct_access) {
      $driver->byCss("td#vwPatient button.edit")->click();
    }

    $this->accessControlTab("medecins");
    $driver->byCss("div#medecins div tr:nth-child(3) button.search")->click();
    $driver->changeFrameFocus();

    $form = "editMedecin_";

    // Médecins
    for ($i = 0; $i < 2; $i++) {
      sleep(1);
      $driver->byCss("div#medicaux button.new")->click();
      $driver->changeFrameFocus();
      $driver->getFormField($form, "nom")->value($names[$i]);
      $driver->getFormField($form, "prenom")->value($firstnames[$i]);

      $sexe      = ($i == 1) ? "f" : "m";
      $spec_cpam = ($i == 1) ? "1" : "4";

      $driver->selectOptionByValue($form . "_sexe", $sexe);
      $driver->selectOptionByValue($form . "_titre", "dr");
      $driver->selectOptionByValue($form . "_spec_cpam_id", $spec_cpam);
      $driver->byCss("td.button button.save")->click();
    }
  }

  /**
   * Select medical correspondents
   *
   * @Param int $position             Position to click on button
   * @Param int $select_correspondent Select correspondent button
   *
   * @return void
   */
  public function selectMedicalCorrespondents($position, $select_correspondent) {
    $driver = $this->driver;

    $driver->byCss("div#medecins div tr:nth-child($position) button.search")->click();
    $driver->byXPath("(//div[@id='medicaux_result']//button[@class='tick'])[$select_correspondent]")->click();
  }

  /**
   * Create directives anticipees
   *
   * @Param int $position             Position to click on button
   * @Param int $select_correspondent Select correspondent button
   *
   * @return void
   */
  public function createDirectivesAnticipees() {
    $driver = $this->driver;

    $this->accessControlTab("identite");

    $form_patient = "editFrm";
    $driver->getFormField($form_patient, "directives_anticipees_1")->click();
    $driver->changeFrameFocus();
    $driver->byXPath("//button[contains(@onclick, 'editDirective')]")->click();
    $driver->changeFrameFocus();

    $this->fillFormDirectives("Test directive correspondant", 2);
    $driver->byXPath("//button[contains(@onclick, 'editDirective')]")->click();
    $this->fillFormDirectives("Test directive correspondant medicaux", 3);
    $this->closeModal();
    $driver->byId("submit-patient")->click();
    $this->getSystemMessage();
  }

  /**
   * Create directives anticipées
   *
   * @param string $description      Description
   * @param int    $select_detenteur Select detenteur (1-Patient, 2-Correspondants, 3-Correspondants médicaux)
   *
   * @return void
   */
  public function fillFormDirectives($description, $select_detenteur = 1) {
    $driver = $this->driver;

    $form_directive = "edit_directive_anticipee";

    // select previous year
    $driver->getFormField($form_directive, "date_recueil_da")->click();
    $driver->byCss("td.navbutton.year.previous")->click();
    $driver->byXPath("(//td[@class='day'])[1]")->click();

    // select next year
    $driver->getFormField($form_directive, "date_validite_da")->click();
    $driver->byCss("td.navbutton.year.next")->click();
    $driver->byXPath("(//td[@class='day'])[1]")->click();

    $driver->getFormField($form_directive, "description")->sendKeys($description);
    $elt = "(//select[@id='edit_directive_anticipee_select_detenteur_id']//optgroup[$select_detenteur]//option)[1]";
    $driver->byXPath($elt)->click();
    $driver->byCss("button.save")->click();
    $this->getSystemMessage();
  }

  /**
   * Check the last directive in synthesis
   *
   * @return bool
   */
  public function checkLastDirectiveInSynthesis() {
    $driver = $this->driver;

    // Synthèse
    $this->accessControlTab("suivi_clinique");

    // checking 1
    $elt    = "//input[@id='editDirectivesPatient_directives_anticipees_1' and @checked]";
    $result = count($driver->findElements(WebDriverBy::xpath($elt)));

    // checking 2
    $elt    = "//button[contains(@onclick, 'showAdvanceDirectives')]";
    $result += count($driver->findElements(WebDriverBy::xpath($elt)));

    // checking 3
    $elt    = "//div[@id='suivi_clinique']//tr//p[contains(text(), 'correspondant medicaux')]";
    $result += count($driver->findElements(WebDriverBy::xpath($elt)));

    return $result > 2;
  }

  /**
   * Create work stopping with the new interface
   *
   * @Param array $datas_aat Some datas of work stopping
   *
   * @return void
   */
  public function createWorkStoppingNewIHM($datas_aat) {
    $driver = $this->driver;

    $contexte  = $datas_aat["contexte"];
    $duree     = $datas_aat["duree"];
    $situation = $datas_aat["situation"];

    $this->accessControlTab("facturation");
    $driver->byCss("div#arret_travail button.new")->click();

    $form_aat = "createAAT";

    // contexte
    $driver->selectOptionByValue($form_aat . "_type", $contexte["type"]);
    $driver->selectOptionByValue($form_aat . "_nature", $contexte["nature"]);
    $driver->getFormField($form_aat, "__ald_temps_complet")->click();
    $driver->getFormField($form_aat, "libelle_motif")->sendKeys($contexte["libelle_motif"]);
    $driver->byCss("div#aat_context button.right")->click();

    // duree
    $driver->getFormField($form_aat, "_duree")->sendKeys($duree["_duree"]);
    $driver->getFormField($form_aat, "__accident_tiers")->click();
    $driver->byId($form_aat . "_date_accident_da")->click();
    $this->selectDate(null, null, null, false);
    $driver->byCss("div#aat_duration button.right")->click();

    // situation
    $driver->selectOptionByValue($form_aat . "_patient_activite", $situation["patient_activite"]);
    $driver->getFormField($form_aat, "patient_employeur_nom")->sendKeys($situation["patient_employeur_nom"]);
    $driver->getFormField($form_aat, "patient_employeur_adresse")->sendKeys($situation["patient_employeur_adresse"]);
    $driver->getFormField($form_aat, "patient_employeur_phone")->sendKeys($situation["patient_employeur_phone"]);
    $driver->byCss("div#aat_patient_situation button.right")->click();

    // sorties
    $driver->getFormField($form_aat, "sorties_autorisees_1")->click();
    $driver->getFormField($form_aat, "__sorties_sans_restriction")->click();
    $driver->byId($form_aat . "_sorties_sans_restriction_date_da")->click();
    $this->selectDate(null, null, null, false);
    $driver->byCss("div#aat_sorties button.right")->click();

    // summary
    $driver->byCss("div#aat_summary button.save")->click();
    $this->getSystemMessage();
  }

  /**
   * Check the summary work stopping
   *
   * @return array
   */
  public function checkSummaryWorkStopping() {
    $driver   = $this->driver;
    $form_aat = "createAAT";
    $results  = array();

    $results[] = $driver->getFormField($form_aat, "summary-type")->getAttribute('value');
    $results[] = $driver->getFormField($form_aat, "summary-nature")->getAttribute('value');
    $results[] = $driver->getFormField($form_aat, "summary-accident_tiers")->getAttribute('value');
    $results[] = $driver->getFormField($form_aat, "summary-date_accident")->getAttribute('value');

    return $results;
  }

  /**
   * Check some datas of the Work stopping on the Cerfa 10170-05
   *
   * @return array
   */
  public function checkDatasOnCerfa() {
    $driver = $this->driver;
    $driver->byCss("div#aat_summary button.print")->click();
    $driver->switchNewWindow();

    $results_cerfa = array();

    // check on first page
    $results_cerfa[] = $driver->byXPath("(//input[@name='type_prolongation'])[1]")->getAttribute("checked");
    $results_cerfa[] = $driver->byXPath("//textarea[@name='codification_motif_medical_texte']")->getAttribute("value");
    $results_cerfa[] = $driver->byXPath("(//input[@name='assure_accident_cause_par_tiers_date'])[1]")->getAttribute("value");
    $results_cerfa[] = $driver->byXPath("(//input[@name='employeur_nom_prenom'])[1]")->getAttribute("value");

    return $results_cerfa;
  }

  public function testPrintEtiquettes() {
    $driver = $this->driver;

    $this->searchPatientByFirstName("Patientfirstname");

    // Go to the complete folder
    $driver->byCss("#search_result_patient a.search")->click();

    $driver->byCss("button.rtl")->click();

    $driver->byCss("button.modele_etiquette")->click();

    sleep(2);

    // Type to open info alert of the navigator (if print popup displayed, it will do nothing)
    $driver->getKeyboard()->sendKeys(
      array(WebDriverKeys::ALT, 'y')
    );

    try {
      $alert = $driver->switchTo()->alert();
      $alert->getText();

      return false;
    }
    catch (Exception $e) {
      return true;
    }
  }

  /**
   * Check correspondent medical name in model.
   *
   * @return void
   */
  public function checkCorrespondentNameInModel() {
    $driver = $this->driver;
    $driver->byXPath("((//table[@class='tbl'])[1]//button[contains(@class,'right-disabled')])[1]")->click();
    $driver->waitForAjax("listView");

    $driver->byXPath("//input[@name='keywords_modele']")->click();
    $driver->byXPath("//div[@class='autocomplete']//div[contains(text()[normalize-space()], 'vierge')]")->click();

    $driver->window("Document");
  }

  /**
   * Select field (Section - item - subitem) in model and save it.
   *
   * @param string $field Field to add in a model
   *
   * @return string
   */
  function selectFieldInModel($field) {
    $driver = $this->driver;

    // Focus on the iframe
    $mbfields = "a.cke_button__mbfields";
    $driver->byCss($mbfields)->click();
    $driver->byId("mbfields_iframe")->click();
    $driver->frame("mbfields_iframe");

    // Explode the requested field
    @list($section, $item) = explode(" - ", $field);

    // Click the section
    $driver->selectOptionByValue("section", utf8_encode($section));

    // Click the item
    $driver->selectOptionByText("item", utf8_encode($item));

    $driver->triggerDoubleClick("item");

    // Access to the editor area
    $driver->frame(null);
    $driver->byCss("div#cke_1_contents iframe")->click();
    $driver->frame($driver->byCss("div#cke_1_contents iframe"));

    return utf8_decode($driver->byCss("body.cke_editable span")->text());
  }

  /**
   * Check bacterial patient status
   *
   * @param string $status
   *
   * @return string
   */
  function testBMRBHReStatus($status = "bmr") {
    $driver = $this->driver;

    $driver->byCss("td#vwPatient button.edit")->click();

    $this->accessControlTab("bmr_bhre");

    $driver->byId("editBMRBHRe_" . $status . "_1")->click();

    $driver->byId("submit-patient")->click();

    return trim($driver->byXPath("//td[@id='vwPatient']//span[@class='texticon']")->getText());
  }
}