<?php
/**
 * @package Mediboard\Messagerie
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbString;
use Ox\Core\CView;
use Ox\Mediboard\Messagerie\CUserMessage;

CCanDo::checkRead();

$usermessage_id = CView::get('usermessage_id', 'ref class|CUserMessage');

CView::checkin();

$usermessage = new CUserMessage();
$usermessage->load($usermessage_id);

$usermessage->content = CMbString::purifyHTML($usermessage->content);

if ($usermessage->_id) {
  if (strpos($usermessage->content, '<br />') === false) {
    echo nl2br($usermessage->content);
  }
  else {
    echo $usermessage->content;
  }
}
