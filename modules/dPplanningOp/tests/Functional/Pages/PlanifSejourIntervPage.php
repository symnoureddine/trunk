<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Planification Sejour page representation
 */
class PlanifSejourIntervPage extends PlanifSejourAbstractPage {

  protected $tab_name    = "vw_edit_planning";

  /**
   * Vérification l'application de protocole
   *
   * @return null|string
   */
  public function applyProtocole($name_protocole, $form = "editOp") {
    $driver = $this->driver;

    $protocole = $driver->byId($form . "_search_protocole");
    $protocole->clear();
    $protocole->sendKeys($name_protocole);
    $driver->selectAutocompleteByText($form . "_search_protocole", "coucou")->click();

    if ($form !== "editOp") {
      return null;
    }

    return array(
      "name_protocole" => $driver->byId("editOp_libelle")->getAttribute('value'),
      "time_op"        => $driver->byId("editOp__time_op_da")->getAttribute('value'),
      "duree"          => $driver->byId("editSejour__duree_prevue")->getAttribute('value'),
      "type_sejour"    => $driver->byId("editSejour_type")->getAttribute('value')
    );
  }

  public function createOperationHorsPlage($chir_name, $patient_name, $code_ccam = false) {
    $this->switchTab('vw_edit_urgence');

    /* Sélection du chirurgien */
    $this->selectChir($chir_name);

    /* Sélection du patient */
    $this->selectPatient();

    /* Saisie de la durée */
    $this->driver->byId('editOp__time_op_da')->click();
    $this->driver->byXPath('//div[@class="datepickerControl"]//td[@class="hour"][contains(text(),"2")]')->click();
    $this->driver->byXPath('//div[@class="datepickerControl"]//button[@class="tick"]')->click();
    /* Saisie de l'heure */
    $this->driver->byId('editOp__time_urgence_da')->click();
    $this->driver->byXPath("//td[contains(@class, 'hour')][text() = '11']")->click();
    $this->driver->byCss("div.datepickerControl button.tick")->click();

    /* Saisie du coté */
    $this->driver->selectOptionByValue('editOp_cote', 'droit');

    if ($code_ccam) {
      /* Ajout du code CCAM */
      $this->driver->byId('editOp__codes_ccam')->sendKeys($code_ccam);
      $this->driver->selectAutocompleteByText('editOp__codes_ccam', $code_ccam)->click();
      /* Ouvre la vue du codage */
      $this->driver->byCss('td#listCodageCCAM_chir button')->click();
      /* Codage de l'acte */
      $this->driver->byCss("form[name='codageActe-$code_ccam-1-0-'] button.add")->click();
      $this->driver->byCss('form[name="applyCodage"] button.tick')->click();
    }

    /* Création de l'intervention */
    $this->driver->byId('didac_submit_interv')->click();
    return $this->getSystemMessage();
  }

  public function getCodedCCAMAct($code_ccam) {
    return $this->driver->byXPath("//a[contains(@href, '#CodeCCAM-show-$code_ccam')]")->getText();
  }

  public function selectChir($chir_name, $form = "editOp") {
    $elt = $this->driver->byId($form . '_chir_id_view');
    if ($elt->getText() != $chir_name) {
      $elt = $this->driver->byId($form . '_chir_id_view');
      $elt->clear();
      $elt->click();
      $this->driver->selectAutocompleteByText($form . '_chir_id_view', $chir_name)->click();
    }
  }

  public function selectPatient($patient_name, $form = "editSejour") {
    $this->driver->byId($form . '__seek_patient')->sendKeys($patient_name);
    $this->driver->selectAutocompleteByText($form . '__seek_patient', $patient_name)->click();
  }

  /**
   * Changement de plage d'une DHE
   *
   * @param string $protocole_name Nom du protocole
   * @param string $chir_name      Nom du chirurgien
   * @param string $patient_name   Nom du patient
   *
   * @return string
   */
  public function changePlageDHE($protocole_name, $chir_name, $patient_name) {
    $form = "editOpEasy";

    $this->selectChir($chir_name, $form);

    $this->applyProtocole($protocole_name, $form);

    $this->selectPatient($patient_name, $form);

    $this->driver->byId($form . "__locale_date")->click();

    // Choix de la plage
    $this->driver->changeFrameFocus();

    $this->driver->byXPath("//table[@class='tbl tab-container']/tbody/tr[2]/td[3]/label/div")->click();

    $this->driver->byXPath("//table[@class='form']//button[@class='tick'][1]")->click();

    // Création de l'intervention
    $this->driver->byId("didac_submit_interv")->click();

    // Changement de plage
    $this->driver->byId($form . "__locale_date")->click();

    $this->driver->changeFrameFocus();

    $this->driver->byXPath("//table[@class='tbl tab-container']/tbody/tr[3]/td[3]/label/div")->click();

    $this->driver->byXPath("//table[@class='form']//button[@class='tick'][1]")->click();

    $this->driver->byCss("button.submit")->click();

    return $this->getSystemMessage();
  }

  public function getSpecsPlages() {
    $form = "editFrm";
    $specs_plages = array();

    $this->driver->byXPath("//table[@id='planning_bloc_day']//tr[3]/td[10]/a")->click();

    $specs_plages[0] = trim($this->driver->byCss("#$form" . "_spec_id option[selected]")->getText());

    $this->closeModal();

    $this->driver->byXPath("//table[@id='planning_bloc_day']//tr[4]/td[10]/a")->click();

    $specs_plages[1] = trim($this->driver->byCss("#$form" . "_spec_id option[selected]")->getText());

    return $specs_plages;
  }
}
