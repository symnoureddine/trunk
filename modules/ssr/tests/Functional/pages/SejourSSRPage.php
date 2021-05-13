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

    //Click sur la cr�ation d'une prise en charge
    $driver->byCss("a.button")->click();

    //Choix du praticien
    $driver->byCss("#".$name_form."_praticien_id > option:nth-child(2)")->click();

    //Choix d'une dur�e
    $duree = $driver->byId($name_form."__duree_prevue");
    $duree->clear();
    $duree->sendKeys(1);

    //Selection du patient
    $driver->byId($name_form."_patient_view")->click();
    $this->patientModalSelector($patientLastname);
    // Reset the focus to the current window
    $driver->switchTo()->defaultContent();

    //Click sur le boutton de cr�ation
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

    //S�ance d�di�e
    $driver->byId($name_form."_type_seance_$type_evt")->click();

    //Choix de la cat�gorie
    $driver->byXPath("//form[@name='$name_form']//button[contains(text(),'$categoryName')]")->click();

    //Choix d'un �l�ment
    $driver->byXPath("//form[@name='$name_form']//span[contains(text(),'$elementName')]")->click();

    //if (CAppUI::gconf("ssr general use_acte_presta") == "csarr") {
    if ($use_csarr) {
      //Ajout d'un code Csarr
      $driver->byId($name_form."__csarr")->click();
      $driver->byId($name_form."_code_csarr")->click();
      $driver->byCss("#code_csarr_autocomplete li:first-child")->click();
    }

    //Choix du r��ducateur
    $driver->byXPath("//select[@class='_technicien_id']/option[contains(text(),'CHIR Test')]")->click();

    //Choix de l'�quipement
    $driver->byId("equipement-")->click();

    //S�lection de jour
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

    //Choix de l'heure de d�but (8h)
    $driver->byId("editEvenementSSR__heure_deb_da")->click();
    $driver->byCss("tr.calendarRow:nth-child(3) > td:nth-child(3)")->click();
    $driver->byCss(".datepickerControl button.tick")->click();

    if ($type_evt == "collective") {
      $driver->byId("seance_collective_add_patient")->click();
      $driver->changeFrameFocus();
      $driver->byId("select_sejour_collectif_check_all_sejours")->click();
      $driver->byXPath("//form[@name='select_sejour_collectif']//button[@class='tick']")->click();
    }
    //Enregistrement de l'�v�nement
    $driver->byXPath("//form[@name='$name_form']//button[@type='submit']")->click();
  }

  /**
   * Validate or annule evenement (dedie|non_dediee)
   *
   * @param bool $annulation Annule or validate
   * @param bool $non_dedie  Utilisation des �v�n�ments non dedi�s
   *
   * @return int
   */
  public function valideSeance($annulation = false, $non_dedie = false) {
    $driver = $this->driver;
    //Choix du planning du r��ducateur
    $driver->byXPath("//form[@name='selectKine']/select/option[contains(text(),'CHIR Test')]")->click();

    if ($non_dedie) {
      $driver->byCss("button.change.notext")->click();
    }

    //S�lection de l'�v�nement (doit �tre de 8h00 � 8h30)
    $driver->byCss("div.event div.time[title='08h00 - 08h30']")->click();
    //Click sur le boutton Valider
    $driver->byCss("button.tick.singleclick")->click();

    $driver->changeFrameFocus();
    if ($annulation) {
      $driver->byCss("input.annule")->click();
    }
    //Validation des modifications d'�v�nement
    $driver->byCss("button.submit.singleclick")->click();
    $driver->waitForAjax('planning-technicien');
    sleep(2);
  }

  /**
   * R�cup�ration du nombre de div correspond � l'action r�alis�e
   *
   * @param string $type_div Type d'�venement � compter
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
   * Supprime la validation ou l'annulation d'une s�ance
   *
   * @return int
   */
  public function eraseSeance() {
    $driver = $this->driver;
    //Choix du planning du r��ducateur
    $driver->byXPath("//form[@name='selectKine']/select/option[contains(text(),'CHIR Test')]")->click();

    //S�lection de l'�v�nement (doit �tre de 8h00 � 8h30)
    $driver->byCss("div.event div.time[title='08h00 - 08h30']")->click();
    //Click sur le boutton d'effecament
    $driver->byCss("button.erase.notext.singleclick")->click();
  }

  /**
   * V�rifier la pr�sence d'un RHS apr�s validation d'�v�nement
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
   * Modification de la dur�e du s�jour SSR
   *
   * @param int $nb_days Dur�e du s�jour en nombre de jours
   *
   * @return void
   */
  public function changeDureeSejour($nb_days) {
    $driver = $this->driver;
    $name_form = "editSejour";

    //Choix d'une dur�e
    $duree = $driver->byId($name_form."__duree_prevue");
    $duree->clear();
    $duree->sendKeys($nb_days);

    //Click sur le boutton d'enregistrement du s�jour
    $driver->byCss("button.modify.default")->click();
    sleep(2);
  }
}