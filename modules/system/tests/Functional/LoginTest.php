<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use Ox\Core\CAppUI;
use Ox\Mediboard\Mediusers\Tests\Functional\Pages\MediusersPage;
use Ox\Mediboard\System\Tests\Functional\Pages\LoginPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * LoginTest
 *
 * @description Try to connect to the app with params included in /tests/data/login.csv
 * @screen      LoginPage
 */
class LoginTest extends SeleniumTestMediboard {

  /**
   * Teste les différents cas de connexion à Mediboard (utilisation d'un *dataprovider* csv)
   *
   * @dataProvider credentialProvider
   */
  public function testLogin($login, $password, $expected) {
    $loginPage = new LoginPage($this);
    $loginPage->doLogin($login, $password);
    switch ($expected) {
      case "pass":
        $this->assertNotContains("Connexion", utf8_decode($this->title()));
        break;
      case "fail":
        if ($login == "" || $password == "") {
          $this->acceptAlert();
        }
        $this->assertContains("Connexion", utf8_decode($this->title()));
        break;
      default:
        $this->markTestSkipped("ignore expected login");
    }
  }

  /**
   * Teste la connexion avec un compte utilisateur secondaire
   *
   */
  public function testLoginSecondaryUser() {
    $this->markTestSkipped('not work ');
    $page = new MediusersPage($this);
    $this->importObject("mediusers/tests/Functional/data/secondary_user.xml");
    $loginPage = new LoginPage($this);
    $loginPage->substitute('second');
    sleep(8);
    $this->assertContains("secondaire", $loginPage->getSubstituteMessage());
  }

  /**
   * Provide login password informations
   * format login,password,pass|fail,comment
   * see /tests/data/login.csv for more details
   *
   * @return array
   */
  public function credentialProvider() {
    $password = CAppUI::conf('sourceCode phpunit_user_password');

    return [
      'wrong credential'        => ['login', 'password', 'fail'],
      'valid credential'        => ['PHPUnit', $password, 'pass'],
      'no login'                => [null, 'password', 'fail'],
      'no password'             => ['login', null, 'fail'],
      'no login & no password ' => [null, null, 'fail'],
    ];
  }
}