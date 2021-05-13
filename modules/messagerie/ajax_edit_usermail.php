<?php
/**
 * @package Mediboard\Messagerie
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CTemplateManager;
use Ox\Mediboard\Messagerie\CUserMail;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourcePOP;

CCanDo::checkEdit();

$account_id         = CView::get('account_id', 'ref class|CSourcePOP');
$mail_id            = CView::get('mail_id', 'ref class|CUserMail');
$reply_to_id        = CView::get('reply_to_id', 'ref class|CUserMail');
$answer_to_all      = CView::get('answer_to_all', 'bool');
$contact_support_ox = CView::get('contact_support_ox', 'bool default|0');
$context            = CView::get('context', 'str');
$mail_subject       = CView::get('mail_subject', 'str');

CView::checkin();

$account = new CSourcePOP();
$account->load($account_id);

if (strpos($account->name, 'apicrypt') !== false) {
  $smtp = CExchangeSource::get("mediuser-{$account->object_id}-apicrypt", 'smtp');
}
else {
  $smtp = CExchangeSource::get("mediuser-{$account->object_id}", 'smtp');
}

if (!$smtp->_id) {
  $smarty = new CSmartyDP();
  $smarty->assign('msg', CAppUI::tr('CUserMail-msg-no_smtp_source_linked_to_pop_account'));
  $smarty->assign('type', 'error');
  $smarty->assign('modal', 1);
  $smarty->assign('close_modal', 1);
  $smarty->display('inc_display_msg.tpl');
  CApp::rip();
}

$mail = new CUserMail();
if ($mail_id) {
  $mail->load($mail_id);
  if ($mail->text_html_id) {
    $mail->loadContentHTML();
    $mail->_content = $mail->_text_html->content;
  }
  elseif ($mail->text_plain_id) {
    $mail->loadContentPlain();
    $mail->_content = $mail->_text_plain->content;
  }
}
else {
  $mail->from = $account->user;
  $mail->account_class = $account->_class;
  $mail->account_id = $account->_id;
  $mail->draft = '1';

  if ($reply_to_id) {
    $mail->in_reply_to_id = $reply_to_id;
    $reply_to = new CUserMail();
    $reply_to->load($reply_to_id);
    $mail->to = $reply_to->from;
    strpos($reply_to->subject, 'Re:') === false ? $mail->subject = "Re: $reply_to->subject" : $mail->subject = $reply_to->subject;

    if ($answer_to_all) {
      $mail->cc = $reply_to->cc;

      /* Récupération des destinataires différents de l'adresse de compte smtp */
      $receivers = explode(',', $reply_to->to);
      foreach ($receivers as $receiver) {
        if ($receiver != '' && strpos($receiver, $smtp->email) === false) {
          $mail->to .= ',' . $receiver;
        }
      }
    }
  }

  $mail->store();
}
$mail->loadAttachments();
foreach ($mail->_attachments as $_attachment) {
  $_attachment->loadFiles();
}

if ($contact_support_ox) {
  $mail->to      = CAppUI::gconf("oxCabinet General email_support");
  $mail->subject = $mail_subject;
}

// Initialisation de CKEditor
$templateManager = new CTemplateManager();
$templateManager->editor = "ckeditor";
$templateManager->messageMode = true;
$templateManager->initHTMLArea();

$smarty = new CSmartyDP();
$smarty->assign('mail', $mail);
$smarty->assign('account', $account);
$smarty->display('inc_edit_usermail.tpl');