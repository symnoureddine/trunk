<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;

CCanDo::checkRead();

$owner_guid  = CView::request("owner_guid", "str");

CView::checkin();

$smarty = new CSmartyDP();

$smarty->assign("owner", $owner_guid === "Instance" ? CCompteRendu::getInstanceObject() : CMbObject::loadFromGuid($owner_guid));

$smarty->display("inc_vw_import_modele");