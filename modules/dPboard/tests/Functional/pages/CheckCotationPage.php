<?php
/**
 * @package Mediboard\Board\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Navigation et utilisation de la vue Saisie des cotations
 */
class CheckCotationPage extends HomePage {

  protected $module_name =  'board';
  protected $tab_name    = 'vw_interv_non_cotees';

  /**
   * Check the uncoted cotations for the given user
   *
   * @param string $user The user to check the cotation for
   *
   * @return void
   */
  public function checkCotationFor($user) {
    $driver = $this->driver;

    $input = $driver->byId('selectChir__chir_view');
    $input->clear();
    $input->sendKeys($user);
    $driver->selectAutocompleteByText('selectChir__chir_view', $user)->click();

    $driver->wait()->until(
      // Presence of $user in url means that page loading is done
      WebDriverExpectedCondition::urlContains($user)
    );

    $driver->byId('doFilterCotation');
  }

  /**
   * Filter the interventions by the validation state of the CCodageCCAM
   *
   * @param string $validation The validation (unlocked, locked_by_chir, locked
   *
   * @return void
   */
  public function checkValidationFor($validation) {
    $driver = $this->driver;

    $driver->selectOptionByValue('filterObjects_codage_lock_status', $validation);
    $driver->byId('doFilterCotation')->click();

    /* The click on the button reload the entire page */
    $driver->byId('doFilterCotation');
  }

  /**
   * Mass code several interventions
   *
   * @param string $code         The CCAM code to check
   * @param string $object_class The object class
   *
   * @return void
   */
  public function massCoding($code, $object_class = 'COperation') {
    $driver = $this->driver;

    $driver->byId('filterObjects__codes_ccam')->sendKeys($code);
    $driver->selectAutocompleteByText('filterObjects__codes_ccam', $code)->click();

    $driver->multipleSelectDeselectAll('filterObjects_object_classes');

    $driver->selectOptionByValue('filterObjects_object_classes', $object_class);

    $driver->byId('doFilterCotation')->click();

    /* The click on the button reload the entire page */
    $driver->wait()->until(
      // Presence of $code in url means that page loading is done
      WebDriverExpectedCondition::urlContains($code)
    );
    $driver->byId('doFilterCotation');

    $driver->byCss('input[name="select_all_objects"]')->click();
    $driver->byId('mass_coding')->click();
    $driver->byId('didac_actes_ccam_tr_modificateurs');
    $driver->byCss('button.add')->click();
    $driver->byId('btn_valid_codage')->click();
  }

  /**
   * Count the number of interventions
   *
   * @return int
   */
  public function countInterventions() {
    $selector = '//div[@id="list_interv_non_cotees"]//table//tr[@class="alternate"]';
    return count($this->driver->findElements(WebDriverBy::xpath($selector)));
  }
}
