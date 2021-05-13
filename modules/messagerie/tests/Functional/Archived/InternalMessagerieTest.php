<?php
/**
 * @package Mediboard\Messagerie\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * Tests on the Messagerie (internal)
 *
 * @description Execute tests on the internal messagerie
 *
 * @screen MessageriePage
 */
class InternalMessagerieTest extends SeleniumTestMediboard {

  /** @var MessageriePage $page */
  private $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new MessageriePage($this);
//  }

  /**
   * Teste l'envoi de message interne
   *
   * @config messagerie access allow_internal_mail 1
   */
  public function testSendInternalMessage() {
    $this->page = new MessageriePage($this);
    $this->page->sendInternalMessage('CHIR Test', 'Test', 'This is a test message');
    $this->assertContains('Message envoyé', $this->page->getSystemMessage());
  }

  /**
   * Teste la réception et l'archivage de messages internes
   */
  public function testGetInternalMessage() {
    $this->page = new MessageriePage($this);
    $this->page->sendInternalMessage('SELENIUM', 'Test', 'This is a test message');
    $this->page->getInternalMessages();
    $this->assertEquals('1', $this->page->getInternalMessageInbox());
    $this->page->archiveInternalMessages();
    $this->assertContains('Message archivé', $this->page->getSystemMessage());
  }

  /**
   * Teste la réception et l'archivage de messages internes
   *
   * @config [CConfiguration] messagerie access allow_internal_mail 1
   * @config [CConfiguration] messagerie access internal_mail_refresh_frequency 1
   */
  public function testMessagerieNotifications() {
    $this->page = new MessageriePage($this);
    $this->page->sendInternalMessage('SELENIUM', 'Test', 'This is a test message');
    /* Le sleep est ici obligatoire pour laisser le temps au compteur de se rafraichir */
    sleep(2);
    $this->assertEquals(1, $this->page->getUnreadMessagesCounter());
    $this->page->markInternalMessagesAsRead();
    $this->page->archiveInternalMessages();
  }
}