<?php
/**
 * @package Mediboard\Messagerie
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\HomePage;

/**
 * Description
 */
class MessageriePage extends HomePage {

  protected $module_name = 'messagerie';
  protected $tab_name = 'vw_messagerie';

  /**
   * Select the given account
   *
   * @param string $account The value to select in the account selector (ie source guid for external account)
   *
   * @return void
   */
  public function selectAccount($account = 'internal') {
    $this->driver->byCss("input[type='radio'][value='$account']")->click();

    $container = 'internalMessages';
    if ($account != 'internal') {
      $container = 'externalMessages';
    }

    $this->driver->byId($container);
  }

  /**
   * Send an email
   *
   * @param string $account   The external account to use
   * @param string $recipient The mail address which will receive the mail
   * @param string $subject   The subject of the mail
   * @param string $content   The content of the mail
   * @param string $file      A file path to add as attachment
   *
   * @return void
   */
  public function sendExternalMessage($account, $recipient, $subject, $content, $file = null) {
    $this->selectAccount($account);

    /* Click on the new message button */
    $this->driver->byCss('div#user_messages_actions button i.fa-envelope')->click();
    $this->driver->changeFrameFocus();
    $this->driver->byId('message-header');

    /* Set the recipient */
    if ($this->driver->getBrowserName() == 'internet explorer') {
      $this->driver->setInputValueById('edit-userMail__to', $recipient);
    }
    else {
      $this->driver->byId('edit-userMail__to')->sendKeys($recipient);
    }
    $this->driver->byCss('button.add.notext')->click();

    /* Set the subject */
    $this->driver->byId('edit-userMail_subject')->sendKeys($subject);

    /* Access to the editor area */
    $this->driver->switchTo()->frame($this->driver->byCss("#cke_1_contents iframe"));

    /* Set the content in CKEditor */
    $this->driver->byCss('body.cke_editable')->sendKeys($content);

    /* Give the focus to the main frame */
    $this->driver->switchTo()->defaultContent();
    /* Change frame focus to modal */
    $this->driver->changeFrameFocus();

    if ($file) {
      $this->driver->byCss('button i.fa-paperclip')->click();
      $this->driver->byId('addAttachment_attachment[0]')->sendKeys($file);
      $this->driver->byId('btn_add_attachments')->click();
      $this->getSystemMessage();
      $this->driver->switchTo()->defaultContent();
      $this->driver->changeFrameFocus();
    }

    /* Send the mail */
    $this->driver->byId('btn_send_email')->click();

    /* Give the focus to the main frame */
    $this->driver->switchTo()->defaultContent();
  }

  /**
   * Receive the external messages
   *
   * @param string $account The external account to use
   *
   * @return void
   */
  public function getExternalMessages($account) {
    $this->selectAccount($account);

    $this->driver->byCss('div#account button.oneclick i.fas fa-sync')->click();
    $this->driver->waitForAjax('systemMsg', 300);
    $this->driver->waitForAjax('list-messages');
  }

  /**
   * Return the number of unread inbox external messages
   *
   * @return string
   */
  public function getExternalMessageInbox() {
    return $this->driver->byCss('div#externalMessages div.folder[data-folder="inbox"] span.count')->getText();
  }

  /**
   * Open an email and download the attachment
   *
   * @return void
   */
  public function getAttachment() {
    $this->driver->byCss('tr.message td.text')->click();
    $this->driver->byCss('button i.fa-download')->click();
  }

  /**
   * Archive all the inbox external messages
   *
   * @return void
   */
  public function archiveExternalMessages() {
    $this->driver->waitForAjax('list-messages');
    $this->driver->byCss('div#user_messages_actions input[type="checkbox"]')->click();
    $this->driver->byCss('div#user_messages_actions button i.fa-archive')->click();
  }

  /**
   * Send an internal message
   *
   * @param string $recipient The recipient name
   * @param string $subject   The subject
   * @param string $content   The message to send
   *
   * @pref inputMode text
   *
   * @return void
   */
  public function sendInternalMessage($recipient, $subject, $content) {
    $this->selectAccount();

    /* Click on the new message button */
    $this->driver->byCss('div#user_messages_actions button i.fa-envelope')->click();
    $this->driver->changeFrameFocus();
    $this->driver->byId('message_area');

    /* Set the recipient */
    $this->driver->setInputValueById('edit_usermessage_keywords', $recipient);
    $this->driver->byId('edit_usermessage_keywords')->click();
    $this->driver->selectAutocompleteByText('edit_usermessage_keywords', $recipient)->click();

    /* Set the subject */
    $this->driver->byId('edit_usermessage_subject')->sendKeys($subject);

    /* Access to the editor area */
    $this->driver->byId('htmlarea')->sendKeys($content);

    /* Send the mail */
    $this->driver->byCss('button.send')->click();

    /* Give the focus to the main frame */
    $this->driver->switchTo()->defaultContent();
  }

  /**
   * Get the internal messages
   *
   * @return void
   */
  public function getInternalMessages() {
    $this->selectAccount();

    $this->driver->byCss('div.folder[data-folder="inbox"]')->click();
    $this->driver->waitForAjax('list-messages');
  }

  /**
   * Get the internal messages count
   *
   * @return string
   */
  public function getInternalMessageInbox() {
    return $this->driver->byCss('div#internalMessages div.folder[data-folder="inbox"] span.count')->getText();
  }

  /**
   * Archive all the inbox external messages
   *
   * @return void
   */
  public function archiveInternalMessages() {
    $this->driver->byCss('div#user_messages_actions input[type="checkbox"]')->click();
    $this->driver->byCss('div#user_messages_actions button i.fa-archive')->click();
  }

  /**
   * Archive all the inbox external messages
   *
   * @return void
   */
  public function markInternalMessagesAsRead() {
    $this->driver->byCss('div#user_messages_actions input[type="checkbox"]')->click();
    $this->driver->byCss('div#user_messages_actions button i.fa-eye')->click();
  }

  /**
   * Check if the attachment is downloaded
   *
   * @return bool
   */
  public function isAttachmentDownloaded() {
    $text = $this->driver->byXPath("//ul[@id='list_attachment']//button")->getText();

    return $text == 'Supprimer';
  }

  /**
   * Create a folder
   *
   * @param string $account The external account to use
   *
   * @return void
   */
  public function createFolder($account) {
    $this->selectAccount($account);

    $this->driver->byId('btn_create_folder')->click();
    $this->driver->byId('editFolder_name');
    $this->driver->setInputValueById('editFolder_name', 'Folder');
    $this->driver->selectOptionByValue('editFolder__folder', 'inbox');
    $this->driver->byCss('button[class="new"]')->click();
  }

  /**
   * Move mails to the given folder
   *
   * @param string $folder The folder's name
   *
   * @return void
   */
  public function moveMails($folder) {
    $this->driver->waitForAjax('list-messages');
    $this->driver->byCss('div#user_messages_actions input[type="checkbox"]')->click();
    $this->driver->byId('btn_move_mails')->click();
    $this->driver->byId('selectFolder__folder');
    $this->driver->selectOptionByText('selectFolder__folder', $folder);
    $this->driver->byCss('button[class="save"]')->click();

  }

  /**
   * Return the number of unread messages from the messagerie menu
   *
   * @param bool $displayed If true, the element must be displayed
   *
   * @return int
   */
  public function getUnreadMessagesCounter($displayed = true) {
    return (int) $this->driver->byId('messagerie-total-counter', 30, true, $displayed)->getText();
  }
}
