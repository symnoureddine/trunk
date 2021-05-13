<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Mediboard\PasswordKeeper\CKeychain;
use Ox\Mediboard\PasswordKeeper\CKeychainChallenge;
use Ox\Mediboard\System\CAbonnement;

CCanDo::checkRead();

$date = CMbDT::date();

$abonnement               = new CAbonnement();
$abonnement->object_class = 'CKeychain';

$abonnements = $abonnement->loadMatchingList();

$abonnements_by_user = array();

/** @var CAbonnement $_abonnement */
foreach ($abonnements as $_abonnement) {
  if (!isset($abonnements_by_user[$_abonnement->user_id])) {
    $abonnements_by_user[$_abonnement->user_id] = array();
  }

  $abonnements_by_user[$_abonnement->user_id][] = $_abonnement->loadTargetObject();
}

foreach ($abonnements_by_user as $_user_id => $_keychains) {
  $_challenges_to_send = array();

  /** @var CKeychain $_keychain */
  foreach ($_keychains as $_keychain) {
    if (!$_keychain || !$_keychain->_id) {
      continue;
    }

    $_challenge = $_keychain->loadUserChallenge($_user_id);

    if ($_challenge->checkToNotify($date)) {
      $_challenges_to_send[] = $_challenge;
    }
  }

  $_urls = array();

  /** @var CKeychainChallenge $_challenge */
  foreach ($_challenges_to_send as $_challenge) {
    $_keychain = $_challenge->loadRefKeychain();

    $_url = CAppUI::conf('external_url');
    $_url = str_replace('http://', '', $_url);
    $_url = str_replace('https://', '', $_url);
    $_url = "https://{$_url}";

    $_urls[] = array(
      'rule'              => CAppUI::tr("{$_challenge->_class}._rule.{$_challenge->_rule}"),
      'last_success_date' => CMbDT::format($_challenge->last_success_date, "%a %d %b %H h %M"),
      'url'               => "<h4><a href='{$_url}?m=passwordKeeper&tab=vw_keychains&challenge=1&keychain_id={$_keychain->_id}'>{$_keychain->_view}</a></h4>",
    );
  }

  if (!$_urls) {
    continue;
  }

  $_content = CAppUI::tr('CKeychainChallenge-msg-You receive this message because you subscribe to some keychain|pl');
  $_content .= '<br />';
  $_content .= CAppUI::tr('CKeychainChallenge-msg-In order to help you in memorizing a passphrase, you are invited to pass a periodic challenge.');

  $_content .= '<br /><br />';
  $_content .= CAppUI::tr('CKeychainChallenge-msg-Here are the several rules that may apply:');

  $_content .= '<dl>';
  foreach (CKeychainChallenge::$rules as $_rule) {
    $_content .= '<dt>' . CAppUI::tr("CKeychainChallenge._rule.{$_rule}") . '</dt>';
    $_content .= '<dd>' . CAppUI::tr("CKeychainChallenge._rule.{$_rule}-desc") . '</dd>';
  }
  $_content .= '</dl>';

  $_content .= CAppUI::tr('CKeychainChallenge-msg-Here is the keychain:|pl');
  $_content .= '<br /><br />';

  $_content .= '<table>';
  $_content .= '<tr>';
  $_content .= '<th>' . CAppUI::tr('CKeychainChallenge-keychain_id') . '</th>';
  $_content .= '<th>' . CAppUI::tr('CKeychainChallenge-last_success_date') . '</th>';
  $_content .= '<th>' . CAppUI::tr('CKeychainChallenge-_rule') . '</th>';
  $_content .= '</tr>';

  foreach ($_urls as $_url) {
    $_content .= '<tr>';
    $_content .= "<td style='text-align: center;'>{$_url['url']}</td>";
    $_content .= "<td style='text-align: center;'>{$_url['last_success_date']}</td>";
    $_content .= "<td style='text-align: center;'>{$_url['rule']}</td>";
    $_content .= '</tr>';
  }

  $_content .= '</table>';

  CApp::sendEmail(CAppUI::tr('CKeychainChallenge-msg-You have pending object|pl'), $_content, $_user_id);
}

CSessionHandler::end(true);