<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceSMTP;

/**
 * Envoi d'un docitem par mail
 */
CCanDo::checkRead();

$receivers              = json_decode(utf8_encode(stripslashes(CView::post("receivers", 'str'))), true);
$subject                = CView::post("subject", 'str');
$body                   = CView::post("body", 'str');
$object_guid            = CView::post("object_guid", 'str');
$objects_guids          = CView::post("objects_guids", 'str');
$destinataires_item_ids = CView::post("destinataires_item_ids", 'str');

CView::checkin();

$object  = null;
$objects = array();

/* Vérification de l'accès à distance à la messagerie */
if (!CAppUI::gconf('messagerie access external_access') && !CAppUI::isIntranet()) {
  CAppUI::displayAjaxMsg('messagerie-msg-external_access_disabled', UI_MSG_ERROR);
}

if ($object_guid) {
  $object = CMbObject::loadFromGuid($object_guid);
  $objects = array($object);
}
else {
  foreach ($objects_guids as $_object_guid) {
    $_object = CMbObject::loadFromGuid($_object_guid);
    $objects[] = $_object;
  }
}

$user = CMediusers::get();

/** @var $exchange_source CSourceSMTP */
$exchange_source = CExchangeSource::get("mediuser-" . $user->_id, CSourceSMTP::TYPE);

$exchange_source->setSenderNameFromUser($user, true);

try {
  $exchange_source->init();
}
catch(phpmailerException $e) {
  CAppUI::displayAjaxMsg($e->errorMessage(), UI_MSG_WARNING);
}
catch (CMbException $e) {
  $e->stepAjax();
}

if (CAppUI::pref('hprim_med_header') && $object) {
  $body = $object->makeHprimHeader($exchange_source->email, reset($emails)) . "\n" . $body;
}

foreach ($receivers as $receiver) {
  if (array_key_exists('email', $receiver) && $receiver['email'] != '') {
    $exchange_source->setRecipient($receiver['email'], $receiver['name']);
  }
}

$exchange_source->setSubject($subject);
$exchange_source->setBody(nl2br($body));

foreach ($objects as $_object) {
  switch ($_object->_class) {
    case "CCompteRendu":
      /** @var $object CCompteRendu */
      $_object->makePDFpreview(true);
      $file = $_object->_ref_file;
      $exchange_source->addAttachment($file->_file_path, $file->file_name);
      break;
    case "CFile":
      /** @var $object CFile */
      $exchange_source->addAttachment($_object->_file_path, $_object->file_name);
  }
}

$mail = $exchange_source->createUserMail(CAppUI::$user->_id, $objects, null, $destinataires_item_ids);

if ($exchange_source->asynchronous == '0') {
  try {
    $exchange_source->send();
    if ($mail) {
      $mail->sent = 1;
      $mail->store();
    }

    CAppUI::displayAjaxMsg("Message envoyé");
  }
  catch (phpmailerException $e) {
    if ($mail) {
      $mail->delete();
    }
    CAppUI::displayAjaxMsg($e->errorMessage(), UI_MSG_WARNING);
  }
  catch (CMbException $e) {
    if ($mail) {
      $mail->delete();
    }
    $e->stepAjax();
  }
}
else {
  CAppUI::displayAjaxMsg('CUsermail-to_send');
}
