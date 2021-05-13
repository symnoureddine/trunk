<?php
/**
 * @package Mediboard\Pmsi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * Pmsi page representation
 */
class PmsiPage extends HomePage {
  protected $module_name = "pmsi";
  protected $tab_name = "vw_dossier_pmsi";

  /**
   * Create a PMSI UM
   *
   * @param string $libelle_um    Label UM
   * @param string $mode_hospi_um Hospitalization mode
   * @param string $nb_lits_um    Total beds UM
   *
   * @return void
   */
  public function createUM($libelle_um, $mode_hospi_um, $nb_lits_um) {
    $driver = $this->driver;
    $form   = "Edit-CUniteMedicaleInfos";

    $driver->byCss("div#List-UniteMedicale button.new")->click();

    $driver->selectOptionByValue($form . "_um_code", $libelle_um);
    $driver->selectOptionByValue($form . "_mode_hospi", $mode_hospi_um);
    $driver->getFormField($form, "nb_lits")->sendKeys($nb_lits_um);
    $driver->getFormField($form, "date_effet_da")->click();
    $this->selectDate("5", null, null, false);

    $driver->byCss("td.button button.save")->click();
  }

  /**
   * Create a UF
   *
   * @param string $type_sejour Type of stay
   * @param string $libelle_um  Label UM
   * @param string $libelle_uf  Label UF
   *
   * @return void
   */
  public function createUF($type_sejour, $libelle_um, $libelle_uf) {
    $driver = $this->driver;
    $form   = "Edit-CUniteFonctionnelle";

    $driver->byCss("div#result-ufs a.new")->click();

    $driver->selectOptionByValue($form . "_type_sejour", $type_sejour);
    $driver->getFormField($form, "code")->sendKeys("UFH" . $libelle_um);
    $driver->selectOptionByText($form . "_type_autorisation_um", $libelle_um);
    $driver->getFormField($form, "libelle")->sendKeys($libelle_uf);
    $driver->getFormField($form, "date_debut_da")->click();
    $this->selectDate("01", null, null, false);

    $driver->byCss("td.button button.submit")->click();
  }

  /**
   * Associate UF to a service
   *
   * @param string $libelle_uf Label UF
   *
   * @return void
   */
  public function associateUFToService($libelle_uf) {
    $driver = $this->driver;
    $form   = "create-CAffectationUniteFonctionnelle";

    $driver->byCss("table#list_services button.edit")->click();
    $driver->byXPath("//a[contains(@onclick, 'AffectationUf')]")->click();
    $driver->changeFrameFocus();

    $driver->selectOptionByText($form . "_uf_id", $libelle_uf);
    $driver->byCss("button.add.notext")->click();
  }

  /**
   * Associate UF to a service
   *
   * @return bool
   */
  public function checkStatUFAndService() {
    $driver = $this->driver;

    $driver->byCss("div#hebergement button.stats")->click();
    $this->accessControlTab("CService");

    $elements = $driver->findElements(WebDriverBy::cssSelector("#CService td"));

    return count($elements) > 0;
  }

  /**
   * Search a acte by his name
   *
   * @param string $name Acte name
   *
   * @return void
   */
  public function addActeByAutocomplete($name) {
    $driver = $this->driver;

    $driver->byXPath("//input[@name='_codes_ccam']")->sendKeys($name);
    $driver->byCss("strong.code")->click();

    $driver->byXPath("(//select[@name='executant_id']//option[contains(text(), 'CHIR')])[1]")->click();
    $driver->byXPath("(//button[@class='add notext compact'])[1]")->click();
    $this->getSystemMessage();
  }

  /**
   * Open the PMSI dossier
   *
   * @param string $patientLastname  Patient lastname
   * @param string $patientFirstname Patient firstname
   *
   * @return void
   */
  public function openDossier($patientLastname, $patientFirstname) {
    $driver = $this->driver;
    $driver->byXPath("//button[contains(@onclick, 'PatSelector')]")->click();
    $this->patientModalSelector($patientLastname, $patientFirstname);
  }

  /**
 * Create RSS file
 *
 * @param string $patientLastname  Patient lastname
 * @param string $patientFirstname Patient firstname
 * @param string $name             Acte name
 *
 * @return void
 */
  public function createRSS($patientLastname, $patientFirstname, $name) {
    $this->openDossier($patientLastname, $patientFirstname);
    $driver = $this->driver;

    // Add acte
    $this->viewActes();
    $this->addActeByAutocomplete($name);

    // RSS
    $this->accessControlTab("tab-rss");
    $driver->byXPath("//select[@name='mode_entree_um']//option[3]")->click();
    $driver->byXPath("//select[@name='mode_sortie_um']//option[4]")->click();
    $driver->byXPath("//select[@name='provenance']//option[2]")->click();
    $driver->byXPath("//select[@name='destination']//option[2]")->click();

    $driver->byCss("button.save")->click();
  }

  /**
   * Open the act view
   *
   * @return void
   */
  public function viewActes() {
    $this->accessControlTab('tab-actes');
  }

  /**
   * DIsplay the operation view
   *
   * @return void
   */
  public function viewActesInterv() {
    $this->viewActes();
    $this->driver->byXPath('//ul[@id="tabs-liste-actes"]/li[2]/a')->click();
  }

  /**
   * Create the act for the given code
   *
   * @param string $code     The CCAM code
   * @param string $activity The activity code
   * @param string $phase    The phase code
   *
   * @return void
   */
  public function codeActe($code, $activity = '1', $phase = '0') {
    $path = "//form[@name='codageActe-PMSI-{$code}-{$activity}-{$phase}-']/button[@class='add notext compact']";
    $this->driver->byXPath($path)->click();
  }

  /**
   * Check if the given CCAM code is displayed
   *
   * @param string $code The CCAM code
   *
   * @return bool
   */
  public function isCodeAdded($code) {
    $content = $this->driver->byXPath("//span[contains(@onclick, '{$code}')]")->getText();
    return strpos($content, $code) !== false;
  }

  /**
   * Lock the first unlocked codage CCAM
   *
   * @return void
   */
  public function lockCodage() {
    $this->driver->byXPath("//button[contains(@onclick, 'lockCodages') and contains(@class, 'tick')][1]")->click();
  }

  /**
   * Unlock the first locked codage CCAM
   *
   * @return void
   */
  public function unlockCodage() {
    $this->driver->byXPath("//button[contains(@onclick, 'unlockCodages') and contains(@class, 'cancel')][1]")->click();
  }

  /**
   * Check if the acte of the sejour to rum is ok
   *
   * @param string $name Acte name
   *
   * @return bool
   */
  public function checkIfActeFromSejourToRUM($name) {
    $driver = $this->driver;
    $elements = $driver->findElements(WebDriverBy::xpath("//td[@class='narrow']//strong[contains(text(), '$name')]"));

    return count($elements) > 0;
  }

  /**
   * Check RUM version
   *
   * @return string
   */
  public function checkRUMVersion() {
    $driver = $this->driver;
    $value = $driver->byXPath("//div[@id='tab-rss']//td[@colspan='2']//p")->getText();

    return $value;
  }

  /**
   * Get RUM identifier
   *
   * @return string
   */
  public function getRUMId() {
    $driver = $this->driver;
    $id = $driver->byXPath("(//form[contains(@name,'manualRum')]//fieldset//td)[1]")->getText();

    return $id;
  }

  /**
   * Generate groupage PMSI
   *
   * @return bool
   */
  public function generateGroupage() {
    $driver = $this->driver;
    $driver->byCss("button.save")->click();
    $this->getSystemMessage();
    $driver->byCss("button.change")->click();
    $id_groupage = "groupage_pmsi_" . $this->getRUMId();
    $driver->waitForAjax($id_groupage);

    $elements = $driver->findElements(WebDriverBy::xpath("//td[@class='halfPane text' and contains(text(), 'Erreur non bloquante')]"));

    return count($elements) > 0;
  }

  /**
   * Validate groupage PMSI
   *
   * @return void
   */
  public function validateGroupage() {
    $driver = $this->driver;
    $id_groupage = "groupage_pmsi_" . $this->getRUMId();
    $driver->waitForAjax($id_groupage);
    $driver->byCss("div#div_validate button")->click();
  }

  /**
   * Check if the diagnosis folder (codes CIM) in the SSR is empty.
   *
   * @return array
   */
  public function checkCodesCIMIsEmpty() {
    $driver = $this->driver;
    $messages = array();

    for ($i=1;$i < 6;$i++) {
      $xpath = "//div[@id='diags_dossier']//tr//td[contains(text(), 'SSR')]//following-sibling::td[$i]//span";
      $msg = utf8_encode($driver->byXPath($xpath)->getText());

      if ($msg == "Aucun Code CIM") {
        $messages["msg"] = $msg;
        $messages["count"] = $i;
      }
    }

    return $messages;
  }

  /**
   * Fill in the codes in the diagnoses RHS
   *
   * @param array $codes Codes CIM
   *
   * @return void
   */
  public function fillInCodesDiagnosesRHS($codes) {
    $driver = $this->driver;

    // New RHS
    $driver->byCss("td.button button.new")->click();
    $this->getSystemMessage();

    // Get RHS id
    $value_id = $driver->byXPath("(//td[@class='greedyPane'])[2]")->getAttribute('id');
    $values = explode("-", $value_id);
    $id = $values[1];

    $formFPP = "editFPP-$id";
    $formDAS = "editDAS-$id";
    $formDAD = "editDAD-$id";

    // FPP
    $driver->byId($formFPP . "_keywords_code")->sendKeys($codes[0]);
    $driver->selectAutocompleteByText($formFPP . "_keywords_code", $codes[0])->click();
    $this->getSystemMessage();

    // DAS
    $driver->byId($formDAS . "_keywords_code")->sendKeys($codes[1]);
    $driver->selectAutocompleteByText($formDAS . "_keywords_code", $codes[1])->click();
    $this->getSystemMessage();
    $driver->byId($formDAS . "_keywords_code")->sendKeys($codes[2]);
    $driver->selectAutocompleteByText($formDAS . "_keywords_code", $codes[2])->click();
    $this->getSystemMessage();

    // DAD
    $driver->byId($formDAD . "_keywords_code")->sendKeys($codes[3]);
    $driver->selectAutocompleteByText($formDAD . "_keywords_code", $codes[3])->click();
    $this->getSystemMessage();
  }

  /**
   * Check if the codes CIM are added.
   *
   * @param array $codes Codes CIM
   *
   * @return int
   */
  public function checkCodesCIMAreAdded($codes) {
    $driver = $this->driver;
    $count = 0;

    foreach ($codes as $_code) {
      $xpath = "//div[@id='diags_dossier']//tr//td[contains(text(), 'SSR')]//following-sibling::td//span[contains(text(), '$_code')]";
      $count += count($driver->findElements(WebDriverBy::xpath($xpath)));
    }

    return $count;
  }
}
