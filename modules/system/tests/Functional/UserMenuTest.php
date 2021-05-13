<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use Ox\Mediboard\Mediusers\Tests\Functional\Pages\MediusersPage;
use Ox\Tests\HomePage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Test on the user menu actions
 *
 * @description Test the availables options on user menu
 *
 * @screen HomePage
 */
class UserMenuTest extends SeleniumTestMediboard {

  /** @var HomePage $page */
  public $page;

  /**
   * @inheritdoc
   */
  public function setUpPage() {
    $this->markTestSkipped('Not tested on gitlab-ci');
    parent::setUpPage();
    $this->page = new HomePage($this);
  }

  /**
   * Teste le changement d'utilisateur à l'aide du menu utilisateur
   */
  public function testSwitchUserOk() {
    $this->markTestSkipped('Not tested on gitlab-ci');
    new MediusersPage($this);
    $this->importObject("mediusers/tests/Functional/data/mediuser.xml");
    $this->page->changeUser("BATMAN");
    sleep(3);
    $this->assertEquals("Bruce WAYNE", $this->byCss(".welcome")->text());
  }

  /**
   * @inheritdoc
   */
  public function tearDown() {
    $this->page->changeToDefaultTestUser();
    parent::tearDown();
  }
}