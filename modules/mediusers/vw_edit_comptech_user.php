<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusersCompteCh;

CCanDo::checkRead();
$compte_ch_id = CView::get('compte_ch_id', 'ref class|CMediusersCompteCh');
$user_id      = CView::get('user_id', 'ref class|CMediusers');
CView::checkin();

$compte_ch = new CMediusersCompteCh();
$compte_ch->load($compte_ch_id);
if (!$compte_ch->_id) {
  $compte_ch->user_id = $user_id;
}

$smarty = new CSmartyDP();
$smarty->assign('compte_ch', $compte_ch);
$smarty->display('vw_edit_comptech_user');