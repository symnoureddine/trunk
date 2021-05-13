<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
/**
 * Planification Sejour page representation
 */
class PlanifSejourSejourPage extends PlanifSejourAbstractPage {

  protected $tab_name    = "vw_edit_sejour";

  /**
   * Vérification de la création de séjour
   *
   * @param string $patientName      Nom du patient
   * @param string $patientFirstName Prénom du patient
   * @param string $chirName         Nom du chirurgien
   * @param string $libelle          Libelle
   * @param string $entree           Heure d'entrée
   * @param int    $duree            Durée
   *
   * @return void
   */
  public function createSejour($patientName, $patientFirstName, $chirName, $libelle, $entree = "", $duree = 7) {
    $driver = $this->driver;

    $form = "editSejour";

    if (!$entree) {
      $entree = CMbDT::date();
    }

    // Fill the chir
    $driver->byId($form . "_praticien_id_view")->click();
    $driver->selectAutocompleteByText($form . "_praticien_id_view", $chirName)->click();

    // Search patient
    $form_patient = "patientSearch";
    $driver->byId("didac_button_pat_selector")->click();

    $driver->changeFrameFocus();
    $driver->getFormField($form_patient, "nom")->sendKeys($patientName);
    $driver->getFormField($form_patient, "prenom")->sendKeys($patientFirstName);
    $driver->byId("pat_selector_search_pat_button")->click();
    $driver->byId("inc_pat_selector_select_pat")->click();
    $driver->switchTo()->defaultContent();

    $driver->getFormField($form, "libelle")->sendKeys($libelle);

    // Set admission type (Hospi. complète)
    $driver->selectOptionByValue("{$form}_type", 'comp');

    // Fill the date
    $driver->getFormField($form, "_date_entree_prevue_da")->click();
    $driver->byCss("td.today")->click();
    $driver->getFormField($form, "_duree_prevue")->sendKeys($duree);

    // Creation of the sejour
    $driver->byId("didac_button_create")->click();

    // Wait until end of sejour creation
    try {
      $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('th.modify')));
    }
    catch (Exception $e) {
      $driver->fail($e->getMessage());
    }
  }
}
