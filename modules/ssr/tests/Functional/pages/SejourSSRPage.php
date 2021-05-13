<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
/**
 * Sejour SSR page representation
 */
class SejourSSRPage extends HomePage {
  protected $module_name = "ssr";
  protected $tab_name = "vw_sejours_ssr";

  public function createSejourSSR($patientLastname) {
    $driver = $this->driver;
    $name_form = "editSejour";

    //Click sur la création d'une prise en charge
    $driver->byCss("a.button")->click();

    //Choix du praticien
    $driver->byCss("#".$name_form."_praticien_id > option:nth-child(2)")->click();

    //Choix d'une durée
    $duree = $driver->byId($name_form."__duree_prevue");
    $duree->clear();
    $duree->sendKeys(1);

    //Selection du patient
    $driver->byId($name_form."_patient_view")->click();
    $this->patientModalSelector($patientLastname);
    // Reset the focus to the current window
    $driver->switchTo()->defaultContent();

    //Click sur le boutton de création
    $driver->byCss("button.submit")->click();
  }

  /**
   * Click on the first patient on the vw_sejour_ssr and add a prescription
   *
   * @param string $categoryName Category name
   * @param string $elementName  Element name
   *
   * @return void
   */
  public function addSoinSejourSSR($categoryName, $elementName, $select_patient = true) {
    $driver = $this->driver;
    if ($select_patient) {
      $driver->byCss('#sejours-ssr .CPatient-view')->click();
    }
    $this->accessControlTab("bilan");
    $driver->byXPath("//strong[contains(text(),'$categoryName')]/../../td/form//input")->sendKeys($elementName);
    $driver->byCss("div.autocomplete li:first-child")->click();

    // Wait for the element
    $driver->byCss('table.form td span.mediuser');
  }

  /**
   * Add a evenement
   *
   * @param string $categoryName Category name
   * @param string $elementName  Element name
   * @param string $days         Day or Week
   * @param bool   $use_csarr    Utilise ou non les actes csarr dans la planification
   *
   * @return void
   */
  public function addEvenement($categoryName, $elementName, $type_evt = "dediee", $days = "day", $use_csarr = true) {
    $driver = $this->driver;
    $this->accessControlTab("planification");
    $name_form = "editEvenementSSR";

    //Séance dédiée
    $driver->byId($name_form."_type_seance_$type_evt")->click();

    //Choix de la catégorie
    $driver->byXPath("//form[@name='$name_form']//button[contains(text(),'$categoryName')]")->click();

    //Choix d'un élément
    $driver->byXPath("//form[@name='$name_form']//span[contains(text(),'$elementName')]")->click();

    //if (CAppUI::gconf("ssr general use_acte_presta") == "csarr") {
    if ($use_csarr) {
      //Ajout d'un code Csarr
      $driver->byId($name_form."__csarr")->click();
      $driver->byId($name_form."_code_csarr")->click();
      $driver->byCss("#code_csarr_autocomplete li:first-child")->click();
    }

    //Choix du rééducateur
    $driver->byXPath("//select[@class='_technicien_id']/option[contains(text(),'CHIR Test')]")->click();

    //Choix de l'équipement
    $driver->byId("equipement-")->click();

    //Sélection de jour
    if ($days == "day") {
      $day_number = date('N', strtotime(CMbDT::date())) - 1;
      $driver->byId($name_form."__days[$day_number]")->click();
    }
    else {
      $week = range(0, 6);
      foreach ($week as $_day_of_week) {
        $driver->byId($name_form."__days[$_day_of_week]")->click();
      }
    }

    //Choix de l'heure de début (8h)
    $driver->byId("editEvenementSSR__heure_deb_da")->click();
    $driver->byCss("tr.calendarRow:nth-child(3) > td:nth-child(3)")->click();
    $driver->byCss(".datepickerControl button.tick")->click();

    if ($type_evt == "collective") {
      $driver->byId("seance_collective_add_patient")->click();
      $driver->changeFrameFocus();
      $driver->byId("select_sejour_collectif_check_all_sejours")->click();
      $driver->byXPath("//form[@name='select_sejour_collectif']//button[@class='tick']")->click();
    }
    //Enregistrement de l'évènement
    $driver->byXPath("//form[@name='$name_form']//button[@type='submit']")->click();
  }

  /**
   * Validate or annule evenement (dedie|non_dediee)
   *
   * @param bool $annulation Annule or validate
   * @param bool $non_dedie  Utilisation des événéments non dediés
   *
   * @return int
   */
  public function valideSeance($annulation = false, $non_dedie = false) {
    $driver = $this->driver;
    //Choix du planning du rééducateur
    $driver->byXPath("//form[@name='selectKine']/select/option[contains(text(),'CHIR Test')]")->click();

    if ($non_dedie) {
      $driver->byCss("button.change.notext")->click();
    }

    //Sélection de l'évènement (doit être de 8h00 à 8h30)
    $driver->byCss("div.event div.time[title='08h00 - 08h30']")->click();
    //Click sur le boutton Valider
    $driver->byCss("button.tick.singleclick")->click();

    $driver->changeFrameFocus();
    if ($annulation) {
      $driver->byCss("input.annule")->click();
    }
    //Validation des modifications d'évènement
    $driver->byCss("button.submit.singleclick")->click();
    $driver->waitForAjax('planning-technicien');
    sleep(2);
  }

  /**
   * Récupération du nombre de div correspond à l'action réalisée
   *
   * @param string $type_div Type d'évenement à compter
   *
   * @return int
   */
  public function getEventCount($type_div = null) {
    $driver = $this->driver;
    if (!$type_div) {
      $type_div = "realise";
    }
    return count($driver->findElements(WebDriverBy::cssSelector("div.event.$type_div")));
  }

  /**
   * Supprime la validation ou l'annulation d'une séance
   *
   * @return int
   */
  public function eraseSeance() {
    $driver = $this->driver;
    //Choix du planning du rééducateur
    $driver->byXPath("//form[@name='selectKine']/select/option[contains(text(),'CHIR Test')]")->click();

    //Sélection de l'évènement (doit être de 8h00 à 8h30)
    $driver->byCss("div.event div.time[title='08h00 - 08h30']")->click();
    //Click sur le boutton d'effecament
    $driver->byCss("button.erase.notext.singleclick")->click();
  }

  /**
   * Vérifier la présence d'un RHS après validation d'événement
   *
   * @return int
   */
  public function checkRHS() {
    $driver = $this->driver;
    $this->accessControlTab("cotation-rhs");
    $semaine = $driver->byXPath("//div[@id='cotation-rhs']//div[@class='tab-container']");
    return $semaine->getAttribute('data-rhs_id') ? 1 : 0;
  }

  /**
   * Modification de la durée du séjour SSR
   *
   * @param int $nb_days Durée du séjour en nombre de jours
   *
   * @return void
   */
  public function changeDureeSejour($nb_days) {
    $driver = $this->driver;
    $name_form = "editSejour";

    //Choix d'une durée
    $duree = $driver->byId($name_form."__duree_prevue");
    $duree->clear();
    $duree->sendKeys($nb_days);

    //Click sur le boutton d'enregistrement du séjour
    $driver->byCss("button.modify.default")->click();
    sleep(2);
  }
}