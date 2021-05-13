<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Tests\Functional;

use Ox\Tests\SeleniumTestMediboard;
use Ox\Mediboard\Cabinet\Tests\Functional\Pages\BanquesPage;

/**
 * CreateBanqueTest
 *
 * @description Test the creation of a banque
 *
 * @screen BanquesPage
 */
class CreateBanqueTest extends SeleniumTestMediboard {

  /**
   * Création d'une banque appelée NomBanque
   *
   * @config locale_warn 0
   */
  public function testCreateBanqueOk() {
    $page = new BanquesPage($this);
    $page->createBanque("NomBanque");

    $this->assertEquals("Banque créée", $page->getSystemMessage());
  }
}