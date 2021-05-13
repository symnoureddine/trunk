<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional\Pages;

use Ox\Tests\HomePage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Login page representation
 */
class LoginPage {

  /** @var  SeleniumTestMediboard $driver */
  public $driver;

  /**
   * Login page constructor
   *
   * @param SeleniumTestMediboard $driver Instance of a WebDriver
   * @param string                $url    Url for the loginPage
   */
  function __construct(SeleniumTestMediboard $driver, $url = "/") {
    $this->driver = $driver;
    $this->driver->url($driver->base_url . $url);
  }

  /**
   * Fill the login form field
   *
   * @param string $login User login
   *
   * @return void
   */
  function setLogin($login) {
    $loginEdit = $this->driver->getFormField("loginFrm", "username");
    $loginEdit->value($login);
  }

  /**
   * Fill the password form field
   *
   * @param string $passwd User password
   *
   * @return void
   */
  function setPasswd($passwd) {
    $passwordEdit = $this->driver->getFormField("loginFrm", "password");
    $passwordEdit->value($passwd);
  }

  /**
   * Perform a click on the login button
   *
   * @return void
   */
  function clickLoginButton() {
    $loginButton = $this->driver->byCssSelector("form[name='loginFrm'] > div > button[type='submit']");
    $loginButton->click();
  }

  /**
   * Perform the login action with the login et password params
   *
   * @param string $login  User login
   * @param string $passwd User password
   *
   * @return HomePage
   */
  function doLogin($login, $passwd) {
    $this->setLogin($login);
    $this->setPasswd($passwd);
    $this->clickLoginButton();
    sleep(3);
    return new HomePage($this->driver, false);
  }

  /**
   * Substitute as the given user
   *
   * @param string $username The user name
   *
   * @return void
   */
  public function substitute($username) {
    $this->driver->byXPath('//a[@onclick="UserSwitch.popup()"]')->click();
    $this->driver->setInputValueById('userSwitchForm_username', $username);
    $this->driver->byXPath('//form[@name="userSwitchForm"]//button[@type="submit"]')->click();
  }

  /**
   * Return the error message returned from the substitute action
   *
   * @return string
   */
  public function getSubstituteMessage() {
    return $this->driver->byXPath('//div[@id="userSwitch"]//div[@class="login-message"]')->text();
  }
}