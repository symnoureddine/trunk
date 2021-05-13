<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\System\CViewSender;

CCanDo::checkEdit();

$sender_id = CValue::get("sender_id");
$sender = new CViewSender();
$sender->offset = "0";
$sender->load($sender_id);
$sender->loadRefsNotes();

$smarty = new CSmartyDP();
$smarty->assign("sender", $sender);
$smarty->display("inc_form_view_sender.tpl");
