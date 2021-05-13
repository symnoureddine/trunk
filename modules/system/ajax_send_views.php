<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\System\CViewSender;

CCanDo::checkRead();

$export = CView::get('export', 'bool default|1');

CView::checkin();

// Minute courante
$date_time = CMbDT::dateTime();
$minute = intval(CMbDT::transform($date_time, null, "%M"));
$hour   = intval(CMbDT::transform($date_time, null, "%H"));
$day    = intval(CMbDT::transform($date_time, null, "%d"));

// Chargement des senders actifs
$sender  = new CViewSender();
$where = array(
  "active" => "= '1'",
);

/** @var CViewSender[] $senders */
$senders = $sender->loadList($where, "name");

// Envoi de vues
foreach ($senders as $_sender) {
  // Vérification des senders à envoyer à la minute courante
  if (!$_sender->getActive($minute, $hour, $day)) {
    unset($senders[$_sender->_id]);
    continue;
  }

  // Si export on envoie les senders
  if ($export) {
    $_sender->makeUrl();
    $filepath = $_sender->makeFile();

    if ($filepath && filesize($filepath) > 0) {
      $_sender->sendFile();
    }
    else {
      CAppUI::stepMessage(UI_MSG_WARNING, "CViewSender-response-empty");
    }
  }
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("senders", $senders);
$smarty->assign("time"   , $date_time);
$smarty->assign("minute" , $minute);
$smarty->display("inc_send_views.tpl");
