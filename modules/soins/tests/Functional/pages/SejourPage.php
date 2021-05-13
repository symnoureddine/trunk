<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * Sejour page representation
 */
class SejourPage extends HomePage {
  protected $module_name =  "soins";
  protected $tab_name    = "vw_idx_sejour";

  /**
   * Recherche et prescrit un produit
   *
   * @param string $product_libelle Libellé du produit
   * @param bool   $uncheck_lt      Décocher la case de recherche dans le livret
   */
  public function addProduit($product_libelle, $uncheck_lt = true) {
    $driver = $this->driver;

    $form = "searchProd";

    if ($uncheck_lt) {
      // Uncheck search in livret
      $driver->getFormField($form, "_recherche_livret")->click();
    }

    // Search the product
    $autocomplete_product = $driver->byId($form . "_" . "produit");
    $autocomplete_product->click();
    $autocomplete_product->sendKeys($product_libelle);

    // Wait for the autocomplete to give the results
    $result_autocomplete = $driver->byId("produit_auto_complete");

    $driver->wait()->until(
      function () use ($result_autocomplete) {
        return ($result_autocomplete->getText() !== "");
      }
    );

    $driver->byCss("#produit_auto_complete li")->click();
  }

  /**
   * Recherche et prescrit un élément
   *
   * @param string $chapitre        Chapitre de l'élément à prescrire
   * @param string $element_libelle Libellé de l'élément
   */
  public function addElement($chapitre, $element_libelle) {
    $driver = $this->driver;

    $this->accessControlTab("div_$chapitre");

    $form = "search$chapitre";

    $autocomplete_elt = $driver->byId($form . "_" . "libelle");
    $autocomplete_elt->click();
    $autocomplete_elt->sendKeys($element_libelle);

    // Wait for the autocomplete to give the results
    $result_autocomplete = $driver->byId($chapitre . "_auto_complete");

    $driver->wait()->until(
      function () use ($result_autocomplete) {
        return ($result_autocomplete->getText() !== "");
      }
    );

    $driver->byCss("#{$chapitre}_auto_complete li")->click();
  }

  /**
   * Créée une prescription à partir du libelle du produit et retourne les quantités administrées et prescrites
   *
   * @param string $product_libelle Le libelle du produit
   * @param int    $qte             La quantité du produit
   *
   * @return array $quantite_administrée $quantite_prescrite
   */
  public function checkQteAdmiMix($product_libelle, $qte) {
    $driver = $this->driver;

    $this->openDossierSoins("prescription_sejour");

    $this->addProduit($product_libelle);

    // Some params for the perfusion
    $driver->byCss("select[name='type_perf'] option[value=classique]")->click();

    // Create the line mix
    $driver->byCss('table.main.layout button.add')->click();

    // Set params of the perfusion
    $driver->selectElementByText("Discontinu")->click();

    $driver->byCss("input[name=duree][type=text]")->sendKeys(10);

    $driver->byCss("#frequence input[name=duree_passage]")->sendKeys(10);

    $driver->byCss("#frequence input[name=nb_tous_les]")->sendKeys(3);

    // Put the quantity
    $driver->byCss("td.field input[name=quantite]")->sendKeys($qte);

    // Close the line
    $driver->byCss(".lock")->click();

    // Go to suivi
    sleep(3);
    $this->accessControlTab("dossier_traitement");

    // Double click for the administration popup
    $elt = $driver->byCss("div[data-original_datetime=\"" . CMbDT::transform(null, null, "%Y-%m-%d %H:00:00") . "\"]");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleclick();
    $actions->perform();

    // Create the administration
    $driver->byCss('#administrations_perf button.submit')->click();
    
    // Wait for system msg
    $this->getSystemMessageElement();
    sleep(3);

    // Reopen the administration popup to check the quantity
    $elt = $driver->byCss("div[data-original_datetime=\"" . CMbDT::transform(null, null, "%Y-%m-%d %H:00:00") . "\"]");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleclick();
    $actions->perform();

    // Retreive the quantity
    $qte_adm = intval($driver->byCss("#administrations_perf tr > td > span")->getText());

    return array(
      "qte_adm"       => $qte_adm,
      "qte_prescrite" => $qte
    );
  }

  /**
   * Applique un protocole contenant une perfusion avec un débit exprimé en /kg
   *
   * @return float
   */
  public function checkDebitKg() {
    $driver = $this->driver;

    $this->openDossierSoins("constantes-medicales");

    $form = "edit-constantes-medicales";

    // Set the weight
    $driver->getFormField($form, "poids")->sendKeys(50);

    // Save the constantes
    $driver->byId("btn_save_constant")->click();

    // Go the prescription tab
    $this->accessControlTab("prescription_sejour");

    // Search and apply the protocole
    $driver->byId("applyProtocole_libelle_protocole")->click();
    $driver->selectAutocompleteByText("applyProtocole_libelle_protocole", "Protocole")->click();

    $driver->byXPath("//*[@id='datetime_now_modal']/table/tbody[2]/tr/td/button[1]")->click();

    $driver->byXPath("//*[@id='buttons_modal_apply']/button[1]")->click();

    $driver->byCss(".search_medicament");

    // Return the debit
    $debit = $driver->byCss(".debit_perf")->getText();
    if ($debit == "") {
      sleep(1);
      $debit = $driver->byCss('.debit_perf')->getText();
    }
    preg_match("/([0-9]+\.[0-9]*)/", $debit, $matches);
    $debit = $matches[0];

    return $debit;
  }

  /**
   * Crée une inscription de médicament depuis le plan de soins
   *
   * @param string $chir_name       Le nom du chirurgien
   * @param string $product_libelle Le nom du produit
   * @param float  $quantity        La quantité à administrer
   *
   * @return $float
   */
  public function checkCreateInscriptionMed($chir_name, $product_libelle, $quantity) {
    $driver = $this->driver;

    $window = $driver->getWindowHandles();

    $this->openDossierSoins("prescription_sejour");

    // Go to the suivi tab
    $this->accessControlTab("dossier_traitement");

    // Go to the inscription chapter
    $this->accessControlTab("_inscription");

    // Double click for the inscription popup
    $elt = $driver->byCss("#_inscription > tr > td:nth-child(34) > div");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    $driver->switchNewWindow();

    // Select the chir
    $form = "addLineMedInscription";

    $driver->selectOptionByText($form . "_praticien_id", $chir_name);

    // Uncheck search in livret
    $form = "searchProd";

    $driver->getFormField($form, "_recherche_livret")->click();

    // Search the product
    $driver->byId($form . "_" . "produit")->sendKeys($product_libelle);
    $driver->selectAutocompleteByText($form . "_" . "produit", strtoupper($product_libelle))->click();

    // Create the administration
    $form = "addAdministration";

    $driver->getFormField($form, "quantite")->sendKeys($quantity);

    $driver->byCss("button.submit")->click();

    sleep(2);

    $driver->switchTo()->window($window[0]);

    // Open the administration modal
    $elt = $driver->byCss("#_inscription > tr > td:nth-child(35) > div");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    // Retreive the quantity
    $qte_adm = intval($driver->byCss("#administrations tr:nth-child(2) > td > span")->getText());

    return $qte_adm;
  }

  public function checkCreateInscriptionElt($chir_name, $element_libelle, $quantity) {
    $driver = $this->driver;

    $window = $driver->getWindowHandles();

    $this->openDossierSoins("prescription_sejour");

    // Go to the suivi tab
    $this->accessControlTab("dossier_traitement");

    // Go to the inscription chapter
    $this->accessControlTab("_inscription");

    // Double click for the inscription popup
    $elt = $driver->byCss("#_inscription > tr > td:nth-child(34) > div");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    $driver->switchNewWindow();

    // Go to the Elements tab
    $this->accessControlTab("tab-elt");

    // Select the chir
    $form = "addLineElementInscription";

    $driver->selectOptionByText($form . "_praticien_id", $chir_name);

    // Uncheck search in livret
    $form = "searchElt";

    // Search the element
    $driver->byId($form . "_" . "libelle")->sendKeys($element_libelle);
    $driver->selectAutocompleteByText($form . "_" . "libelle", $element_libelle)->click();

    // Create the administration
    $form = "addAdministration";

    $driver->getFormField($form, "quantite")->sendKeys($quantity);

    $driver->byCss("button.submit")->click();

    sleep(2);

    $driver->switchTo()->window($window[0]);

    // Open the administration modal
    $elt = $driver->byCss("#_inscription > tr > td:nth-child(35) > div");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    // Retreive the quantity
    $qte_adm = intval($driver->byCss("#administrations tr:nth-child(2) > td > span")->getText());

    return $qte_adm;
  }

  public function openDossierSoins($tab, $sub_tab = null) {
    $driver = $this->driver;

    $form = "selService";

    // Select the "Non placés" service
    $driver->selectOptionByValue("{$form}_service_id", "NP");

    // Open the patient folder
    $driver->byCss("#list_sejours a")->click();

    // Go the tab requested
    $this->accessControlTab($tab);

    if (!$sub_tab) {
      return;
    }

    $this->accessControlTab($sub_tab);
  }

  /**
   * Add two constants
   *
   * @param float $weight Weight constant
   * @param float $size   Size constant
   *
   * @return void
   */
  public function addWeightAndSize($weight, $size) {
    $driver = $this->driver;

    $this->openDossierSoins("constantes-medicales");

    $form = "edit-constantes-medicales";

    $driver->getFormField($form, "poids")->sendKeys($weight);
    $driver->getFormField($form, "taille")->sendKeys($size);

    // Save the constantes
    $driver->byId("btn_save_constant")->click();
  }

  /**
   * Add two constants
   *
   * @param array $constants The constants to add (key is constant name)
   *
   * @return void
   */
  public function addConstants($constants = array()) {
    $driver = $this->driver;

    $this->openDossierSoins("constantes-medicales");

    $form = "edit-constantes-medicales";

    $driver->byCss('div#buttons_form_const- button.down')->click();

    foreach ($constants as $constant => $value) {
      $field = $driver->getFormField($form, $constant);
      $driver->getFormField($form, $constant)->sendKeys($value);
    }

    // Save the constantes
    $driver->byId("btn_save_constant")->click();
  }

  /**
   * Check if the given constant as an alert
   *
   * @param string $constant The name of the constant
   * @param int    $index    The index of the cell
   *
   * @return bool
   */
  public function constantCheckAlert($constant, $index = 1) {
    $this->openDossierSoins("constantes-medicales");

    $this->accessControlTab('constantes-table');
    $headers = $this->driver->findElements(WebDriverBy::cssSelector("#constant_grid_vert_header th"));

    $row = null;
    foreach ($headers as $i => $header) {
      if (strpos($header->getText(), $constant) !== false) {
        $row = $i + 1;
        break;
      }
    }

    if (!$row) {
      return false;
    }

    return !empty($this->driver->findElements(WebDriverBy::xpath("//table[@id='constant_grid_vert']//tr[$row]/td[$index]/i")));
  }

  /**
   * Return the BMI value calculated (Body mass index)
   *
   * @param float $weight Weight constant
   * @param float $size   Size constant
   *
   * @return string BMI value
   */
  public function calculateBMI($weight, $size) {
    $imc = round($weight / ($size * $size * 0.0001), 2);

    return strval($imc);
  }

  /**
   * Return The weight value in grams calculated (Body mass index)
   *
   * @param float $weight Weight constant
   *
   * @return string weight in grams value
   */
  public function calculateWeightInGrams($weight) {
    $weightGrams = strval($weight * 1000);

    return $weightGrams;
  }

  /**
   * Return the BMI value
   *
   * @return string BMI value
   */
  public function getBMIValue() {
    return $this->driver->byXPath("//input[@id='edit-constantes-medicales__last__imc']/parent::td")->getText();
  }

  /**
   * Return the weight value in grams
   *
   * @return string weight in grams value
   */
  public function getWeightInGrams() {
    return $this->driver->byXPath("//input[@id='edit-constantes-medicales__last__poids_g']/parent::td")->getText();
  }

  /**
   * Return the early warning signs value
   *
   * @return string early warning signs value
   */
  public function getEarlyWarningSigns() {
    $this->driver->byCss('div#buttons_form_const- button.down')->click();
    return $this->driver->byXPath("//input[@id='edit-constantes-medicales__last_early_warning_signs']/parent::td")->getText();
  }

  public function testAddTransmission($data = "Donnee", $action = "", $result = "", $diet = false) {
    $driver = $this->driver;

    $this->openDossierSoins("dossier_traitement", "dossier_suivi");

    // Open the transmission modal
    $driver->byXPath("//div[@id='dossier_suivi']//button[@class='add'][1]")->click();

    // Fill the text areas
    $form = "editTrans";
    if ($diet) {
      $driver->byId($form."___dietetique")->click();
    }
    foreach (array("data", "action", "result") as $_type) {
      $driver->getFormField($form, "_text_$_type")->sendKeys(${$_type});
    }

    // Submit the form
    $driver->byXPath("//form[@name='$form']//button[1]")->click();

    if (!$diet) {
      // Return the texts of the transmission saved
      $selector = "//tbody[@id='transmissions']/tr/td";

      return array(
        "data"   => utf8_encode($driver->byXPath($selector . "[4]")->getText()),
        "action" => utf8_encode($driver->byXPath($selector . "[5]")->getText()),
        "result" => utf8_encode($driver->byXPath($selector . "[6]")->getText())
      );
    }
  }

  public function testAddSejourTask($description = "Description", $result = "") {
    $driver = $this->driver;

    $this->openDossierSoins("dossier_traitement", "tasks");

    // Open the sejour task modal
    $driver->byXPath("//div[@id='tasks']//button[@class='add'][1]")->click();

    // Fill the text areas
    $form = "addTask";
    $driver->getFormField($form, "description")->sendKeys($description);
    $driver->getFormField($form, "resultat")->sendKeys($result);

    // Submit the form
    $driver->byXPath("//form[@name='$form']//button[@class='submit'][1]")->click();

    // Return the texts of the task saved
    $selector = "//table[@class='tbl print_tasks']//tr[2]/td";

    return array(
      "description" => utf8_encode($driver->byXPath($selector . "[3]" )->getText()),
      "resultat"    => utf8_encode($driver->byXPath($selector . "[5]" )->getText())
    );
  }

  public function testAddObjectifSoins($libelle = "Libelle", $statut = "") {
    $driver = $this->driver;

    $this->openDossierSoins("dossier_traitement", "objectif_soin");

    // Open the objective modal
    $driver->byXPath("//div[@id='objectif_soin']//button[@class='add notext'][1]")->click();

    // Fill the form
    $form = "editObjectif";
    $driver->getFormField($form, "libelle")->sendKeys($libelle);

    if ($statut) {
      $driver->byCss("select[name='libelle'] option[value=$statut]")->click();
    }

    // Submit the form
    $driver->byXPath("//form[@name='$form']//button[@class='submit'][1]")->click();

    // Return the label of the objective saved
    return $driver->byXPath("//div[@id='objectif_soin']//tr[3]/td[3]")->getText();
  }

  public function testCreateLineMed($product_libelle, $date_debut = null, $time_debut = null, $qte = 1, $prises = array()) {
    $driver = $this->driver;

    $this->openDossierSoins("prescription_sejour");

    return $this->createLineMed($product_libelle, $date_debut = null, $time_debut = null, $qte = 1, $prises = array());
  }

  public function createLineMed($product_libelle, $date_debut = null, $time_debut = null, $qte = 1, $prises = array()) {
    $driver = $this->driver;

    global $button;

    $this->addProduit($product_libelle);

    // We don't want exception to be thrown if button not found because :
    // - the user may be unable to prescribe
    try {
      $driver->wait(5, 1000)->until(
        function () use ($driver) {
          global $button;
          $buttons = $driver->findElements(WebDriverBy::cssSelector("button.lock"));
          $button  = reset($buttons);

          // if button is present, the line is opened
          return !empty($button);
        }
      );
    }
    catch (Exception $e) {
    }


    if (!$button) {
      return null;
    }

    // Retrieve id of line created
    $line_med_id = $driver->getObjectId("prescription_line_medicament");

    if ($date_debut || $time_debut) {
      $form = "editDates-Med-$line_med_id";

      /*if ($date_debut) {
        $driver->getFormField($form, "debut")->sendKeys($date_debut);
      }*/

      if ($time_debut) {
        $driver->getFormField($form, "time_debut_da")->click();
        $driver->byCss("tr.calendarRow:nth-child(2) > td:nth-child(1)")->click();
        $driver->byCss(".datepickerControl button.tick")->click();
      }
    }

    if (count($prises)) {
      $form = "addPriseMed$line_med_id";

      foreach ($prises as $_prise) {
        $driver->getFormField($form, $_prise)->click();
      }

      $driver->byCss("button.big")->click();
    }

    // Close the line
    $button->click();

    // Return the name of the product prescribed
    return trim($driver->byCss("a.search_medicament")->getText());
  }

  public function testCreateLineElt($chapitre, $element) {
    $driver = $this->driver;

    $this->openDossierSoins("prescription_sejour");

    $this->addElement($chapitre, $element);

    // Close the line
    $driver->byCss("button.lock")->click();

    // Return the name of the element prescribed
    return trim($driver->byCss("strong.search_$chapitre")->getText());
  }

  public function testCreateAllergie($allergie = "Allergie") {
    $driver = $this->driver;

    $this->openDossierSoins("antecedents");

    $form = "editAntFrm";

    // Fill the form to add the allergy
    $driver->getFormField($form, "type")->sendKeys("alle");
    $driver->getFormField($form, "rques")->sendKeys(utf8_encode($allergie));

    // Create the allergy
    $driver->byId("inc_ant_consult_trait_button_add_atcd")->click();

    // Check for the icon
    return $driver->byCss(".texticon-allergies-warning") ? true : false;
  }

  public function testCreateAntecedent($atcd = "Antécédent", $type = null, $appareil = null, $important = false, $majeur = false) {
    $driver = $this->driver;

    $this->openDossierSoins("antecedents");

    $form = "editAntFrm";

    // Fill the form to add the antecedent
    $driver->getFormField($form, "rques")->sendKeys(utf8_encode($atcd));

    if ($type) {
      $driver->getFormField($form, "type")->sendKeys($type);
    }

    if ($appareil) {
      $driver->getFormField($form, "appareil")->sendKeys($appareil);
    }

    if ($important) {
      $driver->byId($form . "___important")->click();
    }

    if ($majeur) {
      $driver->byId($form . "___majeur")->click();
    }

    // Create the antecedent
    $driver->byId("inc_ant_consult_trait_button_add_atcd")->click();

    // Check for the icon
    return $driver->byCss(".texticon-atcd") ? true : false;
  }

  public function testAjoutNoAllergie() {
    $driver = $this->driver;

    $this->openDossierSoins("antecedents");

    $driver->byCss("form[name='save_aucun_atcd'] button[class='tick']")->click();

    return $driver->byCss(".texticon-allergies-ok") ? true : false;
  }

  public function testAddMedInfirmiere() {
    $this->changeUser("infirmiere");

    return $this->testCreateLineMed("EFFERALGAN 1 g") !== null;
  }

  public function testAddMedSageFemme() {
    $this->changeUser("sagefemme");

    return $this->testCreateLineMed("EFFERALGAN 1 g") !== null;
  }

  public function testAdmLineMed($product_libelle, $qte = 1, $prises = array("matin", "midi", "soir")) {
    $driver = $this->driver;

    $this->testCreateLineMed($product_libelle, null, "00:00:00", $qte, $prises);

    $this->accessControlTab("dossier_traitement");

    $driver->waitForAjax('_med');

    // Double click for the administration popup
    $elems = $driver->findElements(WebDriverBy::cssSelector("div.non_administre"));
    $elt = reset($elems);

    if (!$elt) {
      $dt = CMbDT::format(CMbDT::dateTime(), "%Y-%m-%d %H") . ":00:00";
      $elt = $driver->byCss("div[data-datetime='$dt']");
    }
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    // Wait for modal
    $driver->byId('validateWindowAdm');

    $elems = $driver->findElements(WebDriverBy::id('editTrans__text_data'));
    if (!empty($elems)) {
      $driver->byId('addAdministration_quantite')->sendKeys($qte);
      if ($elems[0]->isDisplayed()) {
        $elems[0]->sendKeys('OK');
      }
    }

    // Create the administration
    $driver->byId("validateWindowAdm")->click();

    // Wait for system msg
    $this->getSystemMessageElement();

    // Reopen the administration popup to check the quantity
    $elems = $driver->findElements(WebDriverBy::cssSelector("div.administre"));
    $elt = reset($elems);
    if (!$elt) {
      $dt = CMbDT::format(CMbDT::dateTime(), "%Y-%m-%d %H") . ":00:00";
      $elt = $driver->byCss("div[data-datetime='$dt']");
    }
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->doubleClick();
    $actions->perform();

    // Retreive the quantity
    $qte_adm = intval($driver->byCss("#administrations tr:nth-child(2) > td > span")->getText());

    return array(
      "qte_adm"       => $qte_adm,
      "qte_prescrite" => $qte
    );
  }

  public function testAddObservation($text = "Observation", $degre = null, $type = null) {
    $driver = $this->driver;

    // Connect as a chir
    $this->changeUser("CHIRTEST");

    // Go to the "Trans / Obs. / Consult. / Const." tab
    $this->openDossierSoins("dossier_traitement", "dossier_suivi");

    // Open the observation modal
    $driver->byCss("div#dossier_suivi button.add")->click();

    $form = "editObs";

    // Fill the form
    $driver->getFormField($form, "text")->sendKeys($text);

    if ($degre) {
      $driver->getFormField($form, "degre")->sendKeys($degre);
    }

    if ($type) {
      $driver->getFormField($form, "type")->sendKeys($type);
    }

    // Create the observation
    $driver->byCss("form[name='$form'] button.add")->click();

    // Check the creation
    return $this->getSystemMessage();
  }

  public function testAutorisationSortie() {
    $driver = $this->driver;

    // Connect as a chir
    $this->changeUser("CHIRTEST");

    $this->openDossierSoins("suivi_clinique");

    // Open the authorization modal
    $driver->byCss("form[name='edit-sejour-frm'] button.edit")->click();

    // Set the date
    $sejour_id = $driver->getObjectId("sejour");

    $form = "validerSortie$sejour_id";

    $driver->getFormField($form, "confirme_da")->click();

    // Authorize the patient
    $driver->byCss("div.datepickerControl button.tick")->click();

    $driver->byCss("form[name='$form'] button.tick.singleclick")->click();

    return $driver->byCss("#suivi_clinique span.ok") ? true : false;
  }

  public function testAlerteAllergie($composant = "Paracétamol", $med = "Efferalgan 1 g") {
    $driver = $this->driver;

    $composant = utf8_encode($composant);
    $med       = utf8_encode($med);

    // Add the allergy
    $this->openDossierSoins("antecedents");

    $this->accessControlTab("atcd_base_med");

    $driver->byId("editAntFrm_keywords_composant")->sendKeys($composant);
    $driver->selectAutocompleteByText("editAntFrm_keywords_composant", $composant)->click();

    // Add the line
    $this->testCreateLineMed($med);

    return $driver->byCss("div#prescription img[src='images/icons/note_red.png']") ? true : false;
  }

  public function testAlerteRedondance($produit_1, $produit_2) {
    $driver = $this->driver;

    // Prescribe the products
    $this->openDossierSoins("prescription_sejour");

    $this->createLineMed($produit_1);
    $this->createLineMed($produit_2);

    $count_alertes = count(
      $driver->findElements(WebDriverBy::xpath("//div[@id='prescription']//img[@src='images/icons/note_orange.png']"))
    );

    return $count_alertes == 2;
  }

  public function testAlerteIPC($produit_1, $produit_2) {
    $driver = $this->driver;

    // Prescribe the products
    $this->openDossierSoins("prescription_sejour");

    $this->createLineMed($produit_1);
    $this->createLineMed($produit_2);

    $count_alertes = count(
      $driver->findElements(WebDriverBy::xpath("//div[@id='prescription']//img[@src='images/icons/note_red.png']"))
    );

    return $count_alertes == 2;
  }

  public function testAlerteInteraction($produit_1, $produit_2) {
    $driver = $this->driver;

    // Prescribe the products
    $this->openDossierSoins("prescription_sejour");

    $this->createLineMed($produit_1);
    $this->createLineMed($produit_2);

    $count_alertes = count(
      $driver->findElements(WebDriverBy::xpath("//div[@id='prescription']//img[@src='images/icons/note_red.png']"))
    );

    return $count_alertes == 2;
  }

  public function getDiagnosticSignificatif() {
    $sejour_id = $this->driver->getObjectId("sejour");
    return $this->driver->byCss("td#listAntCAnesth{$sejour_id} ul.diagnostics_significatifs li")->getText();
  }

  public function deleteAntecedent() {
    $driver = $this->driver;
    $sejour_id = $this->driver->getObjectId("sejour");
    $driver->byXPath("//td[@id='listAnt{$sejour_id}']//ul[1]//button[@title='Supprimer']")->click();
    $driver->byCss("form[name='deleteElementsForm'] button.trash")->click();
  }

  public function getAntecedentSignificatif() {
    $sejour_id = $this->driver->getObjectId("sejour");
    return utf8_decode($this->driver->byXPath("//td[@id='listAntCAnesth{$sejour_id}']//ul[1]//li[1]")->getText());
  }

  /**
   * Create a Chung score
   *
   * @return integer The Chung score
   */
  public function createChungScore() {
    $this->openDossierSoins('constantes-medicales', 'tab-fiches');
    $this->driver->byId('btn_new_chung_score')->click();
    $this->driver->byId('editChungScore_activity_1')->click();
    $this->driver->byId('editChungScore_nausea_1')->click();
    $this->driver->byId('editChungScore_bleeding_2')->click();
    $score = (int)$this->driver->byId('display_total')->getText();
    $this->driver->byId('btn_edit_score')->click();

    return $score;
  }

  /**
   * Create an IGS score
   *
   * @return integer The IGS score
   */
  public function createIgsScore() {
    $this->openDossierSoins('constantes-medicales', 'tab-fiches');
    $this->driver->byXPath("//a[text()='Score IGS']")->click();
    $this->driver->byId('btn_new_igs_score')->click();
    $this->driver->byId('editScoreIGS_PAO2_FIO2_9')->click();
    $this->driver->byId('editScoreIGS_uree_0')->click();
    $this->driver->byId('editScoreIGS_globules_blancs_0')->click();
    $this->driver->byId('editScoreIGS_kaliemie_3b')->click();
    $this->driver->byId('editScoreIGS_natremie_0')->click();
    $this->driver->byId('editScoreIGS_HCO3_6')->click();
    $this->driver->byId('editScoreIGS_billirubine_4')->click();
    $this->driver->byId('editScoreIGS_maladies_chroniques_10')->click();
    $this->driver->byId('editScoreIGS_admission_0')->click();
    $score = (int)$this->driver->byId('editScoreIGS_scoreIGS')->getAttribute('value');
    $this->driver->byCss('form[name="editScoreIGS"] button[class="submit"]')->click();

    return $score;
  }

  /*
   * Open the good tab
   */
  public function openTabHonos($type_anq) {
    $driver = $this->driver;
    $this->openDossierSoins('constantes-medicales', 'tab-fiches');
    //Accès à l'onglet Honos
    $driver->byXPath("//a[contains(@href,'_".$type_anq."_sejour')]")->click();
  }

  /**
   * Create Anq
   *
   * @param array  $fields       Champs sur lesquels boulcer
   * @param array  $type_anq     type d'ANQ
   * @param string $type         Type de saisie (entrée, sortie ou autre)
   * @param bool   $open_dossier Case of open Dossier soins
   *
   * @return void
   */
  public function createAnqScore($fields, $type_anq, $type = "entree", $open_dossier = true) {
    $driver = $this->driver;

    if ($open_dossier) {
      $this->openTabHonos($type_anq);
    }

    //Click sur le bouton de création
    $driver->byXPath("//div[contains(@id,'_".$type_anq."_sejour')]//button[@class='add']")->click();

    //Ajout des champs necessaires au formulaire
    $form = "editHonos-$type_anq-none_";
    $driver->valueRetryByID($form . "fid", 1);

    //Saisie de la date du relevé
    $driver->getFormField("editHonos-$type_anq-none", "date_da")->click();
    $this->selectDate("5", null, null, false);

    if ($type_anq == "CAnqEFM") {
      //Type de mesure
      $driver->selectOptionByValue($form . "mesure_limitative", "1");

      //Saisie de la date de début de la mesure de contrainte
      $driver->getFormField("editHonos-$type_anq-none", "date_debut_da")->click();
      $this->selectDate("5", null, null, false);
    }
    else {
      //Saisie du type de relevé ainsi que du code de drop out
      $driver->byId($form . "type_$type")->click();
      $driver->byId($form . "drop_out_0")->click();

      foreach ($fields as $_field) {
        if ($_field == "mental_rmq") {
          $driver->valueRetryByID($form . $_field, "Test");
          continue;
        }
        switch ($_field) {
          case "type_reeduc":
            $name_value = "_muscle";break;
          case "locomotion_type":
            $name_value = "_marche";break;
          case "comprehension_type":
            $name_value = "_auditive";break;
          case "expression_type":
            $name_value = "_all";break;
          case "mental_severite":
            $name_value = "_b";break;
          default:
            $name_value = "_1";break;
        }
        $driver->byId($form.$_field.$name_value)->click();
      }
    }

    //Enregistrement du formulaire
    $driver->byCss('form[name="editHonos-'.$type_anq.'-none"] button[class="save"]')->click();
  }


  /**
   * Check if the alert is displayed or not
   *
   * @return bool
   */
  public function checkAlertHonosEntree() {
    $xpath = "//tbody[contains(@id,'list_honos_')]//div[@class='small-warning']";
    return count($this->driver->findElements(WebDriverBy::xpath($xpath))) ? false : true;
  }

  public function searchInOngletDiet() {
    $driver = $this->driver;
    // Go the tab requested
    $this->accessControlTab("dietetique");
    // Return the texts of the transmission diet
    $selector = "//div[@id='suivi_nutrition']//tr/td";
    return array(
      "data"   => utf8_encode($driver->byXPath($selector . "[4]")->getText()),
      "action" => utf8_encode($driver->byXPath($selector . "[5]")->getText()),
      "result" => utf8_encode($driver->byXPath($selector . "[6]")->getText())
    );
  }

  /**
   * Ajout d'une affectation de personnel pour un horaire donné
   *
   * @param string $nom       Nom de l'horaire
   * @param string $personnel Personnel
   *
   * @return void
   */
  public function testAddHorairePersonnel($nom, $personnel) {
    $driver = $this->driver;

    //Choix du service
    $driver->byCss("#selService_service_id > option:nth-child(2)")->click();

    //Click sur le bouton d'affectation de personnel
    $driver->byXPath("(//button[contains(@class,'mediuser_black')])[2]")->click();
    $driver->changeFrameFocus();

    //Click sur l'horaire souhaité
    $driver->byXPath("//label[contains(text()[normalize-space()], '$nom')]")->click();

    $name_form = "addUserToSejour";
    //Recherche par autocomplete de l'utilisateur à affecter (sur la liste complète)
    $driver->byId($name_form."_use_personnel_affecte")->click();
    $user = $driver->byId($name_form."__view");
    $user->clear();
    $user->sendKeys($personnel);
    $driver->byXPath("//div[@class='autocomplete']//li[1]")->click();
  }

  /**
   * Ajout d'ajout d'un responsable du personnel pour le jour courant dans un service
   *
   * @param string $nom Nom du personnel
   *
   * @return void
   */
  public function testAddResponsableJour($nom) {
    $driver = $this->driver;

    //Choix du service
    $driver->byCss("#selService_service_id > option:nth-child(2)")->click();

    //Click sur le bouton de choix du responsable
    $driver->byXPath("//th//button[@class='mediuser_black notext']")->click();
    $driver->changeFrameFocus();

    //Recherche sur tous les utilisateurs
    $driver->byXPath("//input[@name='use_personnel_affecte']")->click();

    //Recherche par autocomplete de l'utilisateur à affecter (sur la liste complète)
    $user = $driver->byXPath("//input[contains(@id,'addResponsable-')][@name='_view']");
    $user->clear();
    $user->sendKeys($nom);
    $driver->byXPath("//div[@class='autocomplete']//li[1]")->click();
  }

  /**
   * Ajout d'une naissance depuis le volet Antécédents et Traitements
   */
  public function testCreateNaissance() {
    $driver = $this->driver;

    $this->openDossierSoins("antecedents");

    // On clic sur le formulaire de naissance

    $driver->byXPath("//button[contains(text()[normalize-space()], 'Naissance')]")->click();

    // On remplit le formulaire

    $form = "newNaissance";

    $driver->byId($form . "_sexe_m")->click();

    $driver->byId($form . "_prenom")->sendKeys("Prenom");

    $driver->byId($form . "__only_pediatres")->click();

    $driver->byId($form . "__prat_autocomplete")->click();

    $driver->selectAutocompleteByText($form . "__prat_autocomplete" , "CHIR")->click();

    $driver->byId($form . "__heure_da")->click();

    $driver->byCss("td.navbutton")->click();

    $driver->byXPath("//form[@name='$form']//button[@type='submit']")->click();

    // On retourne l'identité du patient
    return $driver->byXPath("//div[@id='naissance_area']//span[contains(text(), 'Prenom')]")->getText();
  }

  /**
   * Ajout d'un allaitement depuis le volet Antécédents et Traitements
   */
  public function testCreateAllaitement() {
    $driver = $this->driver;

    $this->openDossierSoins("antecedents");

    $driver->byXPath("//fieldset[@id='etat_actuel_grossesse']//button[@class='add notext']")->click();

    $driver->byCss("button.new")->click();

    // On remplit le formulaire
    $form = "editFormAllaitement";

    $driver->byId($form . "_date_debut_da")->click();

    $driver->byCss("td.navbutton")->click();

    $driver->byXPath("//div[@class='datepickerControl']//button[@class='tick']")->click();

    $driver->byXPath("//td[@id='edit_allaitement']//button[@class='save']")->click();

    return $driver->byXPath("//div[@id='list_allaitements']//a")->getText();
  }

  /**
   * Teste la présence du volet Grossesse dans le dossier de soins
   */
  public function testPresenceGrossesseTab() {
    $driver = $this->driver;

    $this->openDossierSoins("grossesse");

    return $driver->byCss("button.consultation_create");
  }

  /**
   * Create an external RDV
   *
   * @param string $libelle     Label
   * @param string $description Description
   * @param int    $duree       Time in minute
   *
   * @return int
   */
  public function createExternalRDV($libelle, $description, $duree) {
    $driver = $this->driver;

    $this->openDossierSoins("dossier_traitement", "rdv_externe");

    // Open the sejour external RDV modal
    $driver->byXPath("//div[@id='rdv_externe']//button[@class='add'][1]")->click();

    // Fill the text areas
    $form = "addRDVExterne";
    $driver->getFormField($form, "libelle")->sendKeys($libelle);
    $driver->getFormField($form, "description")->sendKeys($description);
    $driver->byId($form . "_date_debut_da")->click();
    $this->selectDate(null, null, "0");
    $driver->getFormField($form, "duree")->sendKeys($duree);

    // Submit the form
    $driver->byXPath("//form[@name='$form']//button[@class='save'][1]")->click();

    $selector = "//a[contains(@href, 'rdv_externe')]//small";

    // Return the counter tab
    $number_rdv = str_replace(array('(', ')'), '', $driver->byXPath($selector)->getText());

    return intval($number_rdv);
  }

  /**
   * Check if the external RDV is in the planning
   *
   * @return string
   */
  public function checkExternalRDVInPlanning() {
    $driver = $this->driver;

    $this->accessControlTab("suivi_clinique");
    $driver->byXPath("//button[contains(@onclick, 'PlanningSejour.view')]")->click();

    $driver->changeFrameFocus();
    $xpath = "//div[@class='event-container']//span";
    $result = $driver->byXPath($xpath)->getText();

    return $result;
  }

  /**
   * Check if trash button is present after administration deletion on inscription
   */
  public function testPresenceTrashButtonInscriptionMed() {
    $driver = $this->driver;

    $driver->byClassName("button.trash")->click();

    return $driver->byClassName("button.trash");
  }

  public function testPlanifPerfQteIndeterminee($med = "PERFALGAN", $qte = 40) {
    $driver = $this->driver;

    $this->openDossierSoins("prescription_sejour");

    $this->addProduit($med);

    // Some params for the perfusion
    $driver->byCss("select[name='type_perf'] option[value=classique]")->click();

    // Create the line mix
    $driver->byCss('table.main.layout button.add')->click();

    // Set params of the perfusion
    $driver->selectElementByText("Discontinu")->click();

    $line_mix_id = $driver->getObjectId("prescription_line_mix");

    $form = "editPerf-$line_mix_id";

    $driver->byId($form . "_duree")->sendKeys(50);
    $driver->byId($form . "_duree_passage")->sendKeys(10);
    $driver->byId($form . "_nb_tous_les")->sendKeys(8);

    $driver->byId($form . "_time_debut_da")->click();
    $driver->byXPath("//td[contains(text(), 'Maintenant')]")->click();

    $driver->byCss("button.fa-cogs")->click();

    $driver->byXPath("//input[@name='__define_quantity']")->click();

    $driver->byId("lock_CPrescriptionLineMix-$line_mix_id")->click();

    $this->accessControlTab("dossier_traitement");

    $driver->byId("mode_dossier_soin_mode_dossier_planification")->click();

    $actions = $driver->action();
    $actions->moveToElement($driver->byXPath("//div[contains(text(),'?')]"));
    $actions->doubleClick();
    $actions->perform();

    $line_mix_item_id = $driver->getObjectId("prescription_line_mix_item");

    $driver->changeFrameFocus();

    $driver->byId("addAdministration-$line_mix_item_id" . "_quantite")->clear()->sendKeys($qte);

    $driver->byId("validateWindowAdm")->click();

    return trim($driver->byXPath("//div[contains(text(), '$qte')]")->getText());
  }

  /**
   * Ouvre le dossier de soins à l'onglet Cotations et ouvre le codage du praticien
   *
   * @return void
   */
  public function openCotationSejour() {
    $this->openDossierSoins('Actes');
    $this->driver->byXPath('//fieldset[@id="codages_ccam"]//button[contains(@class,"edit") and contains(@onclick,"CSejour")]')->click();
  }

  /**
   * Ouvre le codage du praticien donné à la date donnée
   *
   * @param string $chir Le praticien
   * @param string $date La date
   *
   * @return void
   */
  public function openCodageFor($chir, $date) {
    $this->driver->byXpath("//fieldset[@id='codages_ccam']//td[span[contains(text(), '$chir')]]/following-sibling::td/form[contains(@onsubmit, '$date')]/button[contains(@class, 'add')]")->click();
  }

  /**
   * Retourne le contenu du titre de la vue des codages CCAM
   *
   * @return string
   */
  public function getCodageCCAMHeader() {
    return utf8_decode($this->driver->byId('codages-title')->getText());
  }

  /**
   * Ferme la modale de codage CCAM
   *
   * @return void
   */
  public function closeCodageModal() {
    $this->driver->byCss('div.modal-wrapper div.title button.close')->click();
  }

  /**
   * Code l'acte CCAM donné
   *
   * @param string $code     Le code CCAM
   * @param int    $activite Le code activité
   * @param int    $phase    Le code phase
   *
   * @return void
   */
  public function codageActeCCAM($code, $activite = 1, $phase = 0) {
    $path = "//form[contains(@name, 'codageActe-{$code}-{$activite}-{$phase}-')]//button[contains(@class, 'add')]";
    $this->driver->byXPath($path)->click();
  }

  /**
   * Supprime une alerte
   *
   * @return void
   */
  public function dismissAlert() {
    $this->driver->dismissAlert();
  }

  public function testAddCorrespondant($params = array()) {
    $type   = CMbArray::extract($params, "type", "prevenir");
    $nom    = CMbArray::extract($params, "nom", "CORRESPONDANT");
    $prenom = CMbArray::extract($params, "prenom", "Prenom");

    $this->openDossierSoins("suivi_clinique");

    $driver = $this->driver;

    $driver->byXPath("(//button[@class='add notext'])[1]")->click();

    $form = "editCorrespondant";

    $driver->selectOptionByValue($form . "_relation", $type);

    $driver->byId($form . "_nom")->sendKeys($nom);

    $driver->byId($form . "_prenom")->sendKeys($prenom);

    $driver->byCss("button.save")->click();

    return trim($driver->byXPath("//div[@id='suivi_clinique']//span[contains(text(), '$nom')]")->getText());
  }

  public function testAddCorrepondantMedical($params = array()) {
    $nom = CMbArray::extract($params, "nom", "CORRESPONDANT");

    $this->openDossierSoins("suivi_clinique");

    $driver = $this->driver;

    $driver->byXPath("(//button[@class='add notext'])[2]")->click();

    $driver->changeFrameFocus();

    $driver->byXPath("(//div[@id='medecins']//button[@class='search'])[1]")->click();

    // Création du correspondant médical
    $driver->byXPath("(//div[@id='medicaux']//button[@class='new'])[1]")->click();

    $form = "editMedecin_";

    $driver->byId($form . "_nom")->sendKeys($nom);

    $driver->byCss("button.save")->click();

    // On le sélectionne
    $driver->byXPath("//div[@id='medicaux']//button[@class='tick']")->click();

    $driver->byId("submit-patient")->click();

    return trim($driver->byXPath("//div[@id='suivi_clinique']//span[contains(text(), '$nom')]")->getText());
  }
}
