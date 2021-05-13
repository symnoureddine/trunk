<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Eai\CInteropActor;

/**
 * Refresh Exchanges Source Actor EAI
 */

CCanDo::checkRead();

$actor_guid = CView::get("actor_guid", "str");

CView::checkin();

/** @var CInteropActor $actor */
$actor = CMbObject::loadFromGuid($actor_guid);
$actor->loadRefsExchangesSources();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("actor", $actor);
$smarty->display($actor->_parent_class."_exchanges_source.tpl");