<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkEdit();

$_doc_item  = mbGetObjectFromGet(null, null, "item_guid");
$onComplete = CView::get("onComplete", "str");

CView::checkin();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("notext"    , "");
$smarty->assign("_doc_item" , $_doc_item);
$smarty->assign("onComplete", $onComplete);

$smarty->display("inc_file_send_button.tpl");

