<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Tests;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbString;

/**
 * Home page representation, abstract class which defines header and navbar
 */
class HomePage {

  /** @var SeleniumTestMediboard $driver */
  public $driver;

  /** @var string $credentials Test user credentials */
  public $credentials;

  protected $module_name;
  protected $tab_name;

  /**
   * Parent constructor to set url and asign the WebDriver
   *
   * @param SeleniumTestMediboard $driver         WebDriver
   * @param bool                  $connectWithUrl True if you need to use connexion with url
   */
  function __construct(SeleniumTestMediboard $driver, $connectWithUrl = true) {
    $this->credentials = 'PHPUnit:' . CAppUI::conf('sourceCode phpunit_user_password');
    $this->driver      = $driver;
    $driver->timeouts()->implicitWait(5000);
    if ($connectWithUrl) {
      $this->driver->url("{$driver->base_url}/?login=" . $this->credentials);
      if ($this->module_name) {
        $this->driver->url(
          "{$driver->base_url}/index.php?m=$this->module_name" . ($this->tab_name ? "&tab=$this->tab_name" : "")
        );
      }
    }
  }

  /**
   * Switch to another tab by its tab name
   *
   * @param string $tabName Tab name
   *
   * @return void
   */
  public function switchTab($tabName) {
    $driver = $this->driver;
    $driver->byCss(".moduletab-$tabName")->click();
  }

  /**
   * Switch to another module by its name
   *
   * @param string $modName Module name
   *
   * @return void
   */
  public function switchModule($modName) {
    $driver = $this->driver;
    $driver->byCss(".module-$modName")->click();
  }

  /**
   * Click on the given tab with the given href
   *
   * @param string $href The value of the href of the a element
   *
   * @return void
   */
  public function accessControlTab($href) {
    $driver = $this->driver;
    $driver->byCss("a[href=\"#$href\"]")->click();
    $driver->byId($href);
  }


  /**
   * Create a DSN configuration
   *
   * @param string $dsn      Data source name
   * @param string $username Username
   * @param string $password Password
   *
   * @return void
   */
  public function createDSN($dsn, $username, $password) {
    $driver = $this->driver;

    $driver->byCss('button.edit')->click();

    $form           = "ConfigDSN-{$dsn}_db[$dsn]";
    $username_input = $driver->byId($form . "[dbuser]");
    $password_input = $driver->byId($form . "[dbpass]");

    $username_input->clear();
    $username_input->value($username);

    $password_input->clear();
    $password_input->value($password);

    $driver->byCss("form[name='ConfigDSN-$dsn'] button.modify")->click();
  }

  /**
   * Global patient selector with modal
   * Search for a patient and select it if found
   *
   * @param string $lastname  Patient lastname
   * @param string $firstname Patient firstname
   *
   * @return void
   */
  function patientModalSelector($lastname, $firstname = null) {
    $driver = $this->driver;

    $lastnameField  = $driver->getFormField("patientSearch", "nom");
    $firstnameField = $driver->getFormField("patientSearch", "prenom");

    $lastnameField->clear();
    $firstnameField->clear();

    $lastnameField->value($lastname);
    $firstnameField->value($firstname);
    $driver->byId("pat_selector_search_pat_button")->click();
    $driver->byId("inc_pat_selector_select_pat")->click();
  }

  /**
   * Global object selector with pop up
   * Search for an object and select it if found
   *
   * @param string $keyword      Keyword to search
   * @param string $parentWindow The parent window
   * @param bool   $selectFirst  Select first if true else select second elemnt
   *
   * @return void
   */
  function objectPopupSelector($keyword, $parentWindow, $selectFirst = true) {
    $driver = $this->driver;
    $driver->window("Object Selector");
    $driver->valueRetryByID("frmSelector_keywords", $keyword);
    $driver->getFormField("frmSelector", "keywords")->submit();
    $buttons = $driver->findElementsByCss("td.button:nth-child(2) > button");
    $selectFirst ? $buttons[0]->click() : $buttons[1]->click();
    $driver->window($parentWindow[0]);
  }

  /**
   * Get system message after an object creation
   *
   * @return null|string
   */
  public function getSystemMessage() {
    $driver = $this->driver;

    $msg = utf8_decode($driver->byId("systemMsg")->text());
    try {
      $driver->waitUntil(function () use (&$msg, $driver) {
        if ($msg != "" && $msg != null && $msg !== "Chargement en cours...") {
          return true;
        }
        $msg = utf8_decode($driver->byId("systemMsg")->text());

        return null;
      }, 8000);
    }
    catch (Exception $e) {
      $driver->fail($e->getMessage());
    }

    $driver->byId("systemMsg")->click();

    // Waits until system message disappears
    // todo ref with waituntil
    sleep(3);

    return $msg;
  }

  /**
   * Get info system message
   *
   * @return Facebook\WebDriver\WebDriverElement
   */
  public function getSystemMessageElement() {
    return $this->driver->byId("systemMsg");
  }

  /**
   * Check if system message element is an "info" message
   *
   * @return bool True if system message is an info message
   */
  public function isInfoMessage() {
    $infoMsg = $this->driver->findElementsByCss("#systemMsg .info");

    return !empty($infoMsg);
  }

  /**
   * Check if system message element is an "error" message
   *
   * @return bool True if system message is an error message
   */
  public function isErrorMessage() {
    $infoMsg = $this->driver->findElementsByCss("#systemMsg .error");

    return !empty($infoMsg);
  }

  /**
   * Check if system message element is an "error" message
   *
   * @return bool True if system message is an error message
   */
  public function isWarningMessage() {
    $infoMsg = $this->driver->findElementsByCss("#systemMsg .warning");

    return !empty($infoMsg);
  }

  /**
   * Perform a click on a user menu element
   * Actions available : help | tracker | changePasswd | preference | loginAs | lock | logOut
   *
   * @param string $action One of the available actions
   *
   * @return void
   */
  function userMenuAction($action) {
    $this->driver->byCss(".userMenu-$action")->click();
  }

  /**
   * Perform merge on selected objects
   * Can be used in system or in vwPatient
   *
   * @param bool $massMerge Classic merge or mass merge
   *
   * @return void
   */
  public function doMerge($massMerge = true) {
    $driver = $this->driver;

    $massMerge ?
      $driver->byCss("button.merge:nth-child(2)")->click() :
      $driver->byCss("button.merge:nth-child(1)")->click();

    sleep(1);
    $driver->active()->click();

    // reset window
    $windows = $driver->windowHandles();
    $driver->window($windows[0]);

    // Pop-up isn't automaticaly closed with IE
    if ($driver->getBrowser() !== 'internet explorer') {
      $driver->waitUntilSingleWindow();
    }
  }

  /**
   * Close the performance div by clicking on the close button
   * @return void
   */
  public function closePerformance() {
    $this->driver->byCss("#performance .close")->click();
  }

  /**
   * Close a modal dialog by clicking on the close button
   *
   * @return void
   */
  public function closeModal() {
    $driver  = $this->driver;
    $buttons = $driver->findElementsByCss('button.notext.close');
    $button  = end($buttons);
    $button->click();
  }

  /**
   * Change user by the user menu
   *
   * @param string $username Username
   * @param string $password Password
   *
   * @return void
   */
  public function changeUser($username, $password = null) {
    $driver = $this->driver;
    $this->userMenuAction("loginAs");
    $driver->changeFrameFocus();
    $driver->valueRetryByID("userSwitchForm_username", $username);
    if ($password) {
      $driver->executeScript("document.getElementsByClassName(\"userSwitchPassword\")[0].style.display = 'block'");
      $driver->executeScript("document.getElementById(\"userSwitchForm_password\").disabled = false");
      $driver->valueRetryByID("userSwitchForm_password", $password);
    }
    $driver->byCss("button.tick")->click();
  }

  /**
   * Show preferences view
   *
   * @param string|null $module Module name
   *
   * @return void
   */
  public function showPreferences($module = null) {
    $this->userMenuAction('preference');
    $this->accessControlTab('edit_prefs');

    if ($module) {
      $this->accessControlTab("module-$module");
    }
  }

  /**
   * Set a preference
   *
   * @param string $preference Preference name
   * @param string $value      Value
   *
   * @return void
   */
  public function setPreference($preference, $value) {
    $driver = $this->driver;

    $driver->byCss("select[name='pref[$preference]'] option[value='$value']")->click();
    $driver->byCss('button.submit.singleclick')->click();
  }

  /**
   * Return true if the username is found
   *
   * @return string
   */
  public function getCurrentUsername() {
    $this->driver->byClassName('welcome');

    return $this->driver->executeScript("return User.login;");
  }

  /**
   * Change connected user to default user
   *
   * @return void
   */
  public function changeToDefaultTestUser() {
    $driver = $this->driver;
    list($login, $password) = explode(':', $this->credentials);

    if (strtolower($this->getCurrentUsername()) !== strtolower($login)) {
      while ($driver->getModalCount() > 0) {
        $this->closeModal();
        sleep(1);
      }
      $this->changeUser($login, $password);
      $driver->byCss('body');
    }
  }

  /**
   * Select a color by hexadecimal value on the spectrum color picker
   *
   * @param string $hex_color Hexadecimal color value
   *
   * @return void
   */
  public function selectColor($hex_color) {
    $driver    = $this->driver;
    $hex_color = CMbString::lower($hex_color);
    $driver->byCss("div.sp-replacer")->click();
    $driver->byCss("div.sp-container:not(.sp-hidden) span[title='#$hex_color']")->click();
    $driver->byCss("div.sp-container:not(.sp-hidden) button.sp-choose")->click();
  }

  /**
   * Generic selector to select dates on date picker (works only on current month)
   * The date picker should already be openned
   * By default, selects current date
   *
   * @param string|null $day_number Day number
   * @param string|null $hour       Hour
   * @param string|null $minute     Minute
   * @param bool        $btn_valide Button to validate the date
   *
   * @return void
   */
  public function selectDate($day_number = null, $hour = null, $minute = null, $btn_valide = true) {
    $driver = $this->driver;

    if ($day_number === null) {
      $driver->byCss('div.datepickerControl td.navbutton.now')->click();
    }
    else {
      if (strlen($day_number) > 1) {
        $day_number = ltrim($day_number, '0');
      }
      $xpath = "//div[@class='datepickerControl']//td[contains(@class, 'day')][@class != 'dayothermonth'][text()='$day_number']";
      $driver->byXPath($xpath)->click();
    }

    if ($hour !== null) {
      if (strlen($hour) > 1) {
        $hour = ltrim($hour, '0');
      }

      $driver->byXPath("//div[@class='datepickerControl']//td[contains(@class, 'hour')][text()='$hour']")->click();
    }

    if ($minute !== null) {
      $input = $driver->byCss("div.datepickerControl td.otherminute input");
      $input->clear();
      $input->value($minute);
    }

    if ($btn_valide) {
      $driver->byCss("div.datepickerControl button.tick")->click();
    }
  }
}