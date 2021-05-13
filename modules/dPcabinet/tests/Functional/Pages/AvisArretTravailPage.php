<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Tests\HomePage;

/**
 * Description
 */
class AvisArretTravailPage extends HomePage {
  public $form_name;
  public $uid;

  protected $module_name = 'patients';
  protected $tab_name = 'vw_idx_patients';
  
  /**
   * Open the consultation view for the given patient
   *
   * @param string $patient_name The last name of the patient
   *
   * @return void
   */
  public function openConsultation($patient_name) {
    $patientPage = new DossierPatientPage($this->driver, false);
    $patientPage->searchPatientByName($patient_name);
    $patientPage->selectPatientAndConsultation();
  }

  /**
   * Open the AvisArretTravail view
   *
   * @return void
   */
  public function openArretTravail() {
    $this->accessControlTab('facturation');
    $this->driver->waitForAjax('facturation');
    $this->driver->byXPath('//div[@id="arret_travail"]//button[@class="new"]')->click();
    $this->form_name = $this->driver->byXPath('//form[contains(@name, "createAAT")]')->getAttribute('name');
    list($form, $this->uid) = explode('-', $this->form_name);
  }

  /**
   * Renseigne le type d'arr�t
   *
   * @param string $type Le type d'arret (initial ou prolongation)
   *
   * @return void
   */
  public function setType($type) {
    if (!in_array($type, array('initial', 'prolongation'))) {
      return;
    }

    $this->driver->setInputValueById("{$this->form_name}_type", $type);
  }

  /**
   * Renseigne le type de prescripteur
   *
   * @param string $type Le type de prescripteur (MT, MR, MS, MH, 5, 6, 7, 8)
   *
   * @return void
   */
  public function setTypePrescripteur($type) {
    if (!in_array($type, array('MT', 'MR', 'MS', 'MH', '5', '6', '7', '8'))) {
      return;
    }

    $this->driver->setInputValueById("{$this->form_name}_prescripteur_type", $type);
  }

  /**
   * Renseigne le compl�ment du type de prescripteur
   *
   * @param string $text Le compl�ment du type de prescripteur
   *
   * @return void
   */
  public function setTextePrescripteur($text) {
    $this->driver->setInputValueById("{$this->form_name}_prescripteur_text", utf8_encode($text));
  }

  /**
   * Renseigne la nature de l'arr�t
   *
   * @param string $type La nature de l'arret (TC, TP ou TCP)
   *
   * @return void
   */
  public function setNature($type) {
    if (!in_array($type, array('TC', 'TP', 'TCP'))) {
      return;
    }

    $this->driver->setInputValueById("{$this->form_name}_nature", $type);
  }

  /**
   * Autorise les sorties avec restrictions
   *
   * @return void
   */
  public function checkALDTempsComplet() {
    $this->driver->byId("{$this->form_name}___ald_temps_complet")->click();
  }

  /**
   * Autorise les sorties avec restrictions
   *
   * @return void
   */
  public function checkALDTempsPartiel() {
    $this->driver->byId("{$this->form_name}___ald_temps_partiel")->click();
  }

  /**
   * Autorise les sorties avec restrictions
   *
   * @return void
   */
  public function checkMaternite() {
    $this->driver->byId("{$this->form_name}___maternite")->click();
  }

  /**
   * S�lectionne le motif ayant le libell� donn�
   *
   * @param string $motif Le libell� du motif
   *
   * @return void
   */
  public function setMotif($motif) {
    $this->driver->byId("{$this->form_name}_libelle_motif")->sendKeys(utf8_encode($motif));
    $this->driver->selectAutocompleteByText("{$this->form_name}_libelle_motif", utf8_encode($motif))->click();
  }

  /**
   * Renseigne le complement du motif
   *
   * @param string $complement Le compl�ment du motif
   *
   * @return void
   */
  public function setComplementMotif($complement) {
    $this->driver->setInputValueById("{$this->form_name}_complement_motif", utf8_encode($complement));
  }

  /**
   * Retourne la date de d�but de l'arr�t
   *
   * @return string
   */
  public function getDateDebutArret() {
    return $this->driver->getInputValueById("{$this->form_name}_debut", false, false);
  }

  /**
   * Renseigne la dur�e de l'arr�t � temps complet
   *
   * @param integer $duree La dur�e, en jour
   *
   * @return void
   */
  public function setDuree($duree) {
    $this->driver->setInputValueById("{$this->form_name}__duree", intval($duree));
  }

  /**
   * Retourne la dur�e de l'arr�t � temps complet
   *
   * @return integer
   */
  public function getDuree() {
    return intval($this->driver->getInputValueById("{$this->form_name}__duree"));
  }

  /**
   * S�lectionne la prmei�re dur�e indicative pour le motif s�lectionn�
   *
   * @return void
   */
  public function selectDureeIndicative() {
    $this->driver->byId("AAT_button_show_duration-{$this->uid}")->click();
    $this->driver->byXPath("//form[@name=\"formDureeIndicative-{$this->uid}\"]//input[@name=\"duree_indicative\"]")->click();
  }

  /**
   * Retourne la date de fin de l'arr�t
   *
   * @return string
   */
  public function getDateFinArret() {
    return $this->driver->getInputValueById("{$this->form_name}_fin", false, false);
  }

  /**
   * Retourne la dur�e de l'arr�t de reprise � temps partiel
   *
   * @return integer
   */
  public function getDebutReprise() {
    return $this->driver->getInputValueById("{$this->form_name}_debut_tcp", false, false);
  }

  /**
   * Renseigne la dur�e de la reprise � temps partiel
   *
   * @param integer $duree La dur�e, en jour
   *
   * @return void
   */
  public function setDureeReprise($duree) {
    $this->driver->setInputValueById("{$this->form_name}__duree_tcp", intval($duree));
  }

  /**
   * Retourne la dur�e de la reprise � temps partiel
   *
   * @return integer
   */
  public function getDureeReprise() {
    return intval($this->driver->getInputValueById("{$this->form_name}__duree_tcp"));
  }

  /**
   * Coche Accident caus� par un tiers
   *
   * @return void
   */
  public function checkAccidentTiers() {
    $this->driver->byId("{$this->form_name}___accident_tiers")->click();
  }

  /**
   * Renseigne la date de sortie avec restrictions d'horaires
   *
   * @param string $relative The relative date
   *
   * @return void
   */
  public function setDateAccident($relative) {
    $this->driver->byId("{$this->form_name}_date_accident_da")->click();
    $this->selectDate(CMbDT::transform($relative, null, '%e'), null, null, false);
  }

  /**
   * Retourne la date d'autorisations de sorties avec restrictions d'horaires
   *
   * @return string
   */
  public function getDateAccident() {
    return $this->driver->getInputValueById("{$this->form_name}_date_accident", false, false);
  }

  /**
   * Renseigne la situation du patient
   *
   * @param string $situation La situation du patient :
   *                            - PI : Profession ind�pendante,
   *                            - NS : Non sala�rie agricole,
   *                            - SA : Salari�,
   *                            - FO : Fonctionnaire,
   *                            - SE : Sans emploi
   *
   * @return void
   */
  public function setPatientActivite($situation) {
    if (!in_array($situation, array('PI', 'NS', 'SA', 'FO', 'SE'))) {
      return;
    }

    $this->driver->setInputValueById("{$this->form_name}_patient_activite", $situation);
  }

  /**
   * Retourne l'activit� du patient
   *
   * @return string
   */
  public function getPatientActivite() {
    return $this->driver->getInputValueById("{$this->form_name}_patient_activite");
  }

  /**
   * Renseigne la date de cessation d'activit�
   *
   * @param string $relative The relative date
   *
   * @return void
   */
  public function setDateCessationActivite($relative) {
    $this->driver->byId("{$this->form_name}_patient_date_sans_activite_da")->click();
    $this->selectDate(CMbDT::transform($relative, null, '%e'), null, null, false);
  }

  /**
   * Retourne la date de cessation d'activit�
   *
   * @return string
   */
  public function getDateCessationActivite() {
    return $this->driver->getInputValueById("{$this->form_name}_patient_date_sans_activite", false, false);
  }

  /**
   * Autorise les sorties avec restrictions
   *
   * @return void
   */
  public function checkSorties() {
    $this->driver->byId("{$this->form_name}_sorties_autorisees_1")->click();
  }

  /**
   * V�rifie si les sorties avec restrictions sont autoris�es
   *
   * @return bool
   */
  public function sortiesAutorisees() {
    return $this->driver->getInputValueById("{$this->form_name}_sorties", false, false) == '1';
  }

  /**
   * Renseigne la date de sortie avec restrictions d'horaires
   *
   * @param string $relative The relative date
   *
   * @return void
   */
  public function setDateSorties($relative) {
    $this->driver->byId("{$this->form_name}_sorties_date_da")->click();
    $this->selectDate(CMbDT::transform($relative, null, '%e'), null, null, false);
  }

  /**
   * Retourne la date d'autorisations de sorties avec restrictions d'horaires
   *
   * @return string
   */
  public function getDateSorties() {
    return $this->driver->getInputValueById("{$this->form_name}_sorties_date", false, false);
  }

  /**
   * Autorise les sorties sans restrictions d'horaires
   *
   * @return void
   */
  public function checkSortiesSansRestrictions() {
    $this->driver->byId("{$this->form_name}___sorties_sans_restriction")->click();
  }

  /**
   * V�rifie si les sorties sans restrictions sont autoris�es
   *
   * @return bool
   */
  public function sortiesSansRestrictionsAutorisees() {
    return $this->driver->getInputValueById("{$this->form_name}_sorties_sans_restriction", false, false) == '1';
  }

  /**
   * Renseigne le motif d'autorisation des sorties sans restrictions d'horaires
   *
   * @param string $motif Le motif de sortie
   *
   * @return void
   */
  public function setMotifSortiesSansRestrictions($motif) {
    $this->driver->setInputValueById("{$this->form_name}_sorties_sans_restriction_motif", utf8_encode($motif));
  }

  /**
   * Retourne le motif d'autorisation des sorties sans restrictions d'horaires
   *
   * @return string
   */
  public function getMotifSortiesSansRestrictions() {
    return $this->driver->getInputValueById("{$this->form_name}_sorties_sans_restriction_motif");
  }

  /**
   * Renseigne la date de sortie sans restrictions d'horaires
   *
   * @param string $relative The relative date
   *
   * @return void
   */
  public function setDateSortiesSansRestrictions($relative) {
    $this->driver->byId("{$this->form_name}_sorties_sans_restriction_date_da")->click();
    $this->selectDate(CMbDT::transform($relative, null, '%e'), null, null, false);
  }

  /**
   * Retourne la date d'autorisations de sorties sans restrictions d'horaires
   *
   * @return string
   */
  public function getDateSortiesSansRestrictions() {
    return $this->driver->getInputValueById("{$this->form_name}_sorties_sans_restriction_date", false, false);
  }

  /**
   * Enregistre l'avis d'arr�t de travail
   *
   * @return void
   */
  public function saveAAT() {
    $this->driver->byXPath("//div[@id=\"aat_summary-{$this->uid}\"]//button[@class=\"save\"]")->click();
  }

  /**
   * Check if the message with the given id is displayed or not
   *
   * @param string $message_id The message's id
   *
   * @return bool
   */
  public function isMessageDisplayed($message_id) {
    return $this->driver->byId("{$message_id}-{$this->uid}", 30, true, false)->isDisplayed();
  }

  /**
   * Check if the field with the given name is displayed or not
   *
   * @param string $field The field's name
   *
   * @return bool
   */
  public function isFieldDisplayed($field) {
    return $this->driver->byId("{$this->form_name}_{$field}", 30, false, false)->isDisplayed();
  }

  /**
   * Check if the field with the given name is enabled or not
   *
   * @param string $field The field's name
   *
   * @return bool
   */
  public function isFieldEnabled($field) {
    return $this->driver->byId("{$this->form_name}_{$field}", 30, false, false)->isEnabled();
  }

  /**
   * Check if the button with the given id is enabled or not
   *
   * @param string $id The button's id
   *
   * @return bool
   */
  public function isButtonEnabled($id) {
    return $this->driver->byId("{$id}-{$this->uid}", 30, false, false)->isEnabled();
  }

  /**
   * Passe � l'�tape  de saisie du formulaire donn�e en param�tre
   *
   * @param string $actual L'�tape actuelle
   * @param string $next   L'�tape de destination
   *
   * @return void
   */
  public function goToStep($actual, $next) {
    $this->driver->setInputValueById("{$this->form_name}_aat_navigation_{$actual}", "aat_{$next}-{$this->uid}");
  }

  /**
   * V�rifie si le champ donn� est obligatoire ou non
   *
   * @param string $field Le nom du champ
   *
   * @return bool
   */
  public function isFieldNotNull($field) {
    $classes = $this->driver->byId("{$this->form_name}_{$field}", 30, false, false)->getAttribute('class');

    return strpos($classes, 'notNull') !== false;
  }
}
