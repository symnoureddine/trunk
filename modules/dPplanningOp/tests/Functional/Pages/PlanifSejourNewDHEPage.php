<?php 
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * New DHE page
 */
class PlanifSejourNewDHEPage extends PlanifSejourAbstractPage {

  protected $tab_name = 'vw_dhe';

  /**
   * Create the DHE objects (sejour, operation or consultation)
   *
   * @return string
   */
  public function create() {
    $this->driver->byCss('button.save')->click();

    return $this->getSystemMessage();
  }

  /**
   * @param string $patient_name The patient name
   * @param string $chir_name    The chir name
   * @param string $type         The type of sejour (default ambu)
   * @param int    $duration     The duration of the sejour in days
   *
   * @return void
   */
  public function setSejour($patient_name, $chir_name, $type = 'ambu', $duration = 0) {
    /* Set the chir */
    $this->driver->byId('sejourSummary__chir_view')->click();
    // Click twice because of an IE bug..
    $this->driver->byId('sejourSummary__chir_view')->click();
    $this->driver->selectAutocompleteByText('sejourSummary__chir_view', $chir_name)->click();

    /* Set the patient */
    $this->driver->byId('selectPatient__patient_view')->sendKeys($patient_name);
    $this->driver->selectAutocompleteByText('selectPatient__patient_view', $patient_name)->click();

    /* Set the entry date */
    $this->driver->byId('sejourSummary_entree_prevue_da')->click();
    $this->driver->byCss('div.datepickerControl td.today')->click();
    $this->driver->byCss('div.datepickerControl button.tick')->click();

    /* Set the type */
    $this->driver->byId('sejourSummary_type')->sendKeys($type);

    /* Set the duration */
    if ($duration) {
      $duration_field_id = 'sejourSummary__duree_prevue';
      if ($type == 'ambu') {
        $duration_field_id = 'sejourSummary__duree_prevue_heure';
      }

      $this->driver->byId($duration_field_id)->sendKeys($duration);
    }
  }

  /**
   * @param string $patient_name  The patient name
   * @param string $chir_name     The chir name
   * @param string $protocol_name The name of the protocol to use
   *
   * @return void
   */
  public function setSejourWithProtocol($patient_name, $chir_name, $protocol_name) {
    /* The chir is already in session, so no need to select it */

    /* Set the patient */
    $this->driver->valueRetryByID('selectPatient__patient_view', $patient_name);
    $this->driver->selectAutocompleteByText('selectPatient__patient_view', $patient_name)->click();

    /* Select the protocol */
    $this->driver->byId('selectProtocole__protocole_view')->sendKeys($protocol_name);
    $this->driver->selectAutocompleteByText('selectProtocole__protocole_view', $protocol_name)->click();
  }

  /**
   * Display the consult form and set it
   *
   * @return void
   */
  public function setConsultation() {
    /* Display the consultation form */
    $this->driver->byId('btn_add_consult')->click();

    $this->driver->waitForAjax('consultation');
    /* Set the consultation type to immediate */
    $this->driver->selectOptionByValue('consultationSummary__type', 'immediate');

    /* Set the datetime */
    $this->driver->byId('consultationSummary__datetime_da')->click();
    $this->driver->byCss('div.datepickerControl td.today')->click();
    $this->driver->byCss('div.datepickerControl button.tick')->click();
  }

  /**
   * Display the operation form and set it
   *
   * @return void
   */
  public function setOperation() {
    /* Display the operation form */
    $this->driver->byId('btn_add_interv')->click();
    $this->driver->waitForAjax('operation');

    /* Set the libelle of the operation */
    $this->driver->byId('operationSummary_libelle')->sendKeys('Test');

    /* Display the operation planification view */
    $this->driver->byId('planif_category')->click();
    $this->driver->byId('operation-edit-planification');

    /* Set the duration */
    $this->driver->byCss('form[name="operationEdit"] input[name="_time_op_da"]')->click();
    $this->driver->byCss('div.datepickerControl td.now')->click();

    /* Set the type of planification */
    $this->driver->selectOptionByValue('operationEdit_type_op', 'hors_plage');

    /* Set the date and time */
    $this->driver->byCss('form[name="operationEdit"] input[name="_date_hors_plage_da"]')->click();
    $this->driver->byCss('div.datepickerControl td.today')->click();

    /* Close the view */
    $this->driver->byId('btn_close_operation_edit')->click();
  }

  /**
   * @param string $patient_name  The patient name
   * @param string $chir_name     The chir name
   * @param string $protocol_name The name of the protocol to use
   *
   * @return void
   */
  public function setOperationWithProtocol($patient_name, $chir_name, $protocol_name) {
    /* Display the operation form */
    $this->driver->byId('btn_add_interv')->click();

    /* The chir is already in session, so no need to select it */

    /* Set the patient */
    $this->driver->valueRetryByID('selectPatient__patient_view', $patient_name);
    $this->driver->selectAutocompleteByText('selectPatient__patient_view', $patient_name)->click();

    /* Select the protocol */
    $this->driver->byId('selectProtocole__protocole_view')->sendKeys($protocol_name);
    $this->driver->selectAutocompleteByText('selectProtocole__protocole_view', $protocol_name)->click();

    /* Display the operation planification view */
    $this->driver->byId('planif_category')->click();
    $this->driver->byId('operation-edit-planification');

    /* Set the type of planification */
    $this->driver->selectOptionByValue('operationEdit_type_op', 'hors_plage');

    /* Set the date and time */
    $this->driver->byCss('form[name="operationEdit"] input[name="_date_hors_plage_da"]')->click();
    $this->driver->byCss('div.datepickerControl td.today')->click();

    /* Close the view */
    $this->driver->byId('btn_close_operation_edit')->click();
  }
}
