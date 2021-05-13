<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * CreateProtocoleTest
 *
 * @description Test creation Protocole
 * @screen      ProtocolePage
 */
class CreateProtocoleTest extends SeleniumTestMediboard {
  public $libelle  = "Protocole";
  public $chir     = "CHIR Test";

  /** @var ProtocolePage $page */
  public $protocolePage;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->protocolePage = new ProtocolePage($this);
//  }

  /**
   * Création d'un protocole
   */
  public function testCreateProtocole() {
    $page = $this->protocolePage;

    $params = array(
      "libelle" => $this->libelle,
      "chir"    => $this->chir
    );

    $page->testCreateProtocole($params);

    $this->assertContains("Protocole créé", $page->getSystemMessage());
  }
}
