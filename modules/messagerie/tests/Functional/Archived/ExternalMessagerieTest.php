<?php
/**
 * @package Mediboard\Messagerie\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Tests on the Messagerie (external)
 *
 * @description Execute tests on the external messagerie
 *
 * @screen MessageriePage
 */
class ExternalMessagerieTest extends SeleniumTestMediboard {

  /** @var MessageriePage $page */
  private $page;

  /** @var string $account */
  private $account;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->markTestIncomplete('Intermitent failure, needs maintenance...');
//
//    if (!$this->account = $this->getAccount()) {
//      $this->markTestSkipped('No account configured');
//    }
//
//    $this->page = new MessageriePage($this);
//  }

  /**
   * Teste l'envoi de messages externes
   */
  public function testSendExternalMessage() {
    $this->page->sendExternalMessage($this->account, 'selenium@oxfse.com', 'Test ' . CMbDT::dateTime(), 'This is a test message');
    $this->assertContains('Message envoy�', $this->page->getSystemMessage());
  }

  /**
   * Teste la r�ception de messages externes
   *
   * @depends testSendExternalMessage
   */
  public function testGetExternalMessages() {
    $this->page->getExternalMessages($this->account);
    $nb_mails = intval($this->page->getExternalMessageInbox());
    $this->assertGreaterThanOrEqual(1, $nb_mails);
    $this->assertContains("$nb_mails messages r�cup�r�s", $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un r�pertoire dans la messagerie externe
   *
   * @depends testGetExternalMessages
   */
  public function testCreateFolder() {
    $this->page->createFolder($this->account);
    $this->assertContains('Dossier cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un r�pertoire dans la messagerie externe
   *
   * @depends testCreateFolder
   */
  public function testMoveMails() {
    $this->page->createFolder($this->account);
    $nb_mails = intval($this->page->getExternalMessageInbox());
    $this->page->moveMails('Folder');
    $this->assertContains(($nb_mails == 1) ? 'Mail d�plac�' : 'Mails d�plac�s', $this->page->getSystemMessage());
  }

  /**
   * Teste l'envoi de message externe contenant une pi�ce jointe
   */
  public function testSendExternalMessageWithAttachment() {
    $this->page->sendExternalMessage(
      $this->account, 'selenium@oxfse.com', 'Test ' . CMbDT::dateTime(), 'This is a test message', 'C:\file.pdf'
    );

    $this->assertContains('Message envoy�', $this->page->getSystemMessage());
  }

  /**
   * Teste la r�ception de messages externes
   *
   * @depends testSendExternalMessageWithAttachment
   */
  public function testGetExternalMessagesWithAttachment() {
    $this->page->getExternalMessages($this->account);
    $nb_mails = intval($this->page->getExternalMessageInbox());
    $this->assertGreaterThanOrEqual(1, $nb_mails);
    $this->page->getAttachment();
    $this->assertTrue($this->page->isAttachmentDownloaded());

    $this->page->closeModal();
    $this->page->archiveExternalMessages();
    $this->assertContains(($nb_mails == 1) ? 'Mail archiv�' : 'Mails archiv�s', $this->page->getSystemMessage());
  }

  /**
   * Returns the guid of a source POP linked to the given user
   *
   * @param string $username
   *
   * @return string
   */
  protected function getAccount($username = 'selenium') {
    $query = new CRequest();
    $query->addTable('source_pop');
    $query->addColumn('source_pop_id');
    $query->addLJoinClause('users', 'users.user_id = source_pop.object_id');
    $query->addWhereClause('users.user_username', " = '$username'");
    $query->addWhereClause('source_pop.object_class', " = 'CMediusers'");

    $ds  = CSQLDataSource::get('std');
    $row = $ds->fetchArray($ds->exec($query->makeSelect()));

    if (!array_key_exists('source_pop_id', $row)) {
      return false;
    }

    return 'CSourcePOP-' . $row['source_pop_id'];
  }
}
