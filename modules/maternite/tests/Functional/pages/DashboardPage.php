<?php
/**
 * @package Mediboard\Maternite\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\HomePage;

/**
 * DashboardPage page representation
 */
class DashboardPage extends HomePage {

  protected $module_name = "maternite";
  protected $tab_name = "vw_tdb_maternite";

  /**
   * Create a new pregnancy
   *
   * @param string $patientName      patient last name
   * @param string $patientFirstName patient first name
   *
   * @return void
   */
  public function createNewPregnancy($patientName, $patientFirstName) {
    $driver = $this->driver;

    $driver->byCss("button.grossesse_create")->click();
    $driver->byId("editFormGrossesse__patient_view")->click();

    $this->patientModalSelector($patientName);

    $driver->switchTo()->defaultContent();
    $driver->changeFrameFocus();

    $driver->getFormField("editFormGrossesse", "terme_prevu_da")->click();
    $driver->byCss("tr.calendarRow:nth-child(5) > td:nth-child(3)")->click();

    $driver->getFormField("editFormGrossesse", "date_dernieres_regles_da")->click();
    $driver->byCss("tr.calendarRow:nth-child(4) > td:nth-child(5)")->click();

    $driver->byId("button_create_grossesse")->click();
  }

  /**
   * Open modal and create a "plage de consultation"
   *
   * @param string $chir Praticioner name
   * @param string $date Date to create the consultation with format yyyy-mm-dd
   *
   * @return void
   */
  public function openModalAndcreatePlageConsultation($chir, $date) {
    $page = new ConsultationsPage($this->driver, false);
    $page->openPlageCreationModal($date);
    $page->createPlageConsultation($chir, $date);
  }

  /**
   * Create a new consultation
   *
   * @param string $chir        Praticioner name
   * @param string $patientName Patient last name
   *
   * @return void
   */
  public function createNewConsultation($chir, $patientName) {
    $driver = $this->driver;

    $driver->byCss("button.consultation_create")->click();

    $driver->selectOptionByText("editFrm_chir_id", $chir);
    $driver->byCss("button.agenda")->click();

    $driver->changeFrameFocus();
    $driver->byCss("div.progressBar a")->click();

    $driver->byCss("input[data-time='10:00:00']")->click();
    $driver->byId("consult_multiple_button_validate")->click();
    $driver->switchTo()->defaultContent();

    $this->driver->byId('editFrm__seek_patient')->sendKeys($patientName);
    $driver->byCss("div.autocomplete span.view")->click();

    $driver->byId("addedit_planning_button_submitRDV")->click();
  }

  /**
   * Add lots of general information
   *
   * @param string $patientName      patient last name
   * @param string $patientFirstName patient first name
   *
   * @return void
   */
  public function addGeneralInformation($patientName, $patientFirstName) {
    $driver = $this->driver;

    $driver->byCss("td.halfPane li:nth-child(1)")->click();

    //Mother
    $driver->byCss("select[name='situation_famille'] option[value='M']")->click();
    $driver->byCss("select[name='mdv_familiale'] option[value='C']")->click();
    $driver->byCss("select[name='activite_pro'] option[value='cp']")->click();

    //Father
    $driver->byCss("button.search.notext:nth-child(1)")->click();
    $this->getSystemMessage();
    $this->patientModalSelector($patientName);

    $driver->switchTo()->defaultContent();
    $driver->changeFrameFocus();
    $driver->changeFrameFocus();
    sleep(1);

    $selector = "//span[text()='Dossier périnatal - Renseignements généraux']/../..//button[contains(text(),'Enregistrer et fermer')]";
    $this->driver->byXPath(utf8_encode($selector))->click();
  }

  /**
   * Create a new childbirth
   *
   * @param string $patientName patient last name
   *
   * @return void
   */
  public function createNewChildBirth($patientName) {
    $driver = $this->driver;

    $driver->byCss("div#accouchements button.accouchement_create")->click();
    $driver->changeFrameFocus();

    //choose practitioner
    $driver->byId("editOp_chir_id_view")->sendKeys("CHIR");
    $driver->byXPath("//div[@class='autocomplete']//span[contains(text(),'CHIR')]")->click();

    //choose side
    $driver->byId("editOp_libelle")->sendKeys("accouchement");
    $driver->selectOptionByValue("editOp_cote", "bas");

    //operating time
    $driver->byId("editOp__time_op_da")->click();
    $driver->byCss("div.datepickerControl tr:nth-child(2)  td.hour:nth-child(2)")->click();
    $driver->byCss("div.datepickerControl button.tick")->click();

    $driver->byId("didac_button_pat_selector")->click();
    $this->patientModalSelector($patientName);
    $driver->switchTo()->defaultContent();
    $driver->changeFrameFocus();

    $driver->byId("button_grossesse")->click();
    $driver->waitForAjax("list_grossesses");
    $driver->byId("button_select_grossesse")->click();
    $driver->switchTo()->defaultContent();
    $driver->changeFrameFocus();

    //duration stay
    $driver->byId("editSejour__duree_prevue")->clear();
    $driver->byId("editSejour__duree_prevue")->sendKeys(2);

    $driver->selectOptionByText("editSejour_type", "comp");

    $driver->byId("didac_submit_interv")->click();
    $driver->waitForAjax("prescription-CSejour-dhe");
  }

  /**
   * Create a new birth
   *
   * @return void
   */
  public function createBirth() {
    $driver = $this->driver;

    $this->openModalPregnancy();

    $driver->byXPath("//span[contains(text(),'Naissance')]")->click();

    //selection of the stay
    $driver->byXPath("//input[@name='admission_id']")->click();
    $driver->byXPath("//form[contains(@name,'ChoixSejour-CDossierPerinat')]//button[contains(@class,'save')]")->click();

    // add new birth
    $driver->byCss("button.add")->click();
    $driver->byId("labelFor_newNaissance_sexe_m")->click();
    $driver->byId("newNaissance_prenom")->sendKeys("Bobby");
    $driver->byId("newNaissance__heure_da")->click();
    $driver->byCss("div.datepickerControl tr:nth-child(2)  td.hour:nth-child(2)")->click();
    $driver->byCss("div.datepickerControl button.tick")->click();

    $driver->byId("newNaissance__only_pediatres")->click();
    $driver->byId("newNaissance__prat_autocomplete")->sendKeys("CHIR Test");
    $driver->byXPath("//div[@class='autocomplete']//span[contains(text(),'CHIR')]")->click();

    $driver->byCss("button.singleclick")->click();
  }

  /**
   * Returns the date of the birth
   *
   * @return string the date
   */
  public function getBirthDate() {
    $text  = $this->driver->byCss("table.tbl tr:nth-child(3) td:nth-child(2)")->getText();
    $words = explode(" ", $text);

    return $words[1];
  }

  /**
   * Open modal pregnancy
   *
   * @return void
   */
  public function openModalPregnancy() {
    $driver = $this->driver;

    $driver->byCss("div#grossesses button.change")->click();
    $driver->byCss("button.grossesse")->click();
    $driver->changeFrameFocus();
  }

  /**
   * Add some screening information
   *
   * @return void
   */
  public function addScreeningInformation() {
    $driver = $this->driver;

    $driver->byCss("div#dossier_mater_dossier_perinatal td.halfPane li:nth-child(2)")->click();

    $driver->byCss("button.add")->click();

    $driver->changeFrameFocus();
    $driver->byId("Depistage-CDepistageGrossesse-_date_da")->click();

    //date du jour
    $driver->byCss("div.datepickerControl tr.calendarRow td.today")->click();

    //Immuno-hématologie
    $driver->selectOptionByValue("Depistage-CDepistageGrossesse-_groupe_sanguin", "a");
    $driver->selectOptionByValue("Depistage-CDepistageGrossesse-_rhesus", "pos");

    //Sérologie
    $driver->selectOptionByValue("Depistage-CDepistageGrossesse-_rubeole", "im");
    $driver->selectOptionByValue("Depistage-CDepistageGrossesse-_vih", "in");

    //Biochimie
    $driver->byId("Depistage-CDepistageGrossesse-_depistage_diabete")->sendKeys("Oui");

    //Bactériologie
    $driver->byId("Depistage-CDepistageGrossesse-_pv")->sendKeys("Non");

    //Hémato-hémostase
    $driver->byId("Depistage-CDepistageGrossesse-_tp")->sendKeys(65);

    $selector = "//form[contains(@name, 'Depistage-CDepistageGrossesse-')]//button[contains(text(),'Enregistrer et fermer')]";
    $this->driver->byXPath($selector)->click();
  }

  /**
   * Open modal summary of childbirth
   *
   * @return void
   */
  public function openModalSummaryChildBirth() {
    $driver = $this->driver;

    $driver->byCss("div#dossier_mater_dossier_perinatal tr:nth-child(2) td li:nth-child(1)")->click();
    $driver->changeFrameFocus();

    // choose a stay
    $driver->byXPath("//input[@name='admission_id']")->click();
    $driver->byCss("table.form tr:nth-child(2) td.button button.save")->click();
  }

  /**
   * Check of the calculation of the bishop's score
   *
   * @param string $pos_dilatation   dilatation position
   * @param string $pos_longueur     longueur position
   * @param string $pos_consistance  consistance position
   * @param string $position         position
   * @param string $pos_presentation presentation position
   *
   * @return int the Bishop value
   */
  public function checkBishopScore($pos_dilatation, $pos_longueur, $pos_consistance, $position, $pos_presentation) {
    $driver = $this->driver;

    // selectionner les valeurs dans le tableau
    $driver->byXPath("(//input[@name='score_bishop_dilatation-view'])[$pos_dilatation]")->click();
    $driver->byXPath("(//input[@name='score_bishop_longueur-view'])[$pos_longueur]")->click();
    $driver->byXPath("(//input[@name='score_bishop_consistance-view'])[$pos_consistance]")->click();
    $driver->byXPath("(//input[@name='score_bishop_position-view'])[$position]")->click();
    $driver->byXPath("(//input[@name='score_bishop_presentation-view'])[$pos_presentation]")->click();

    $driver->byCss("table.form button.change")->click();

    return intval($driver->byXPath("//input[@name='exam_entree_indice_bishop']")->getAttribute('value'));
  }
}