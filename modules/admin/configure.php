<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Admin\CAuthenticationFactor;

CCanDo::checkAdmin();

$authentication_source = CAuthenticationFactor::getSMTPSource();
$activer_user_action = CAppUI::conf("activer_user_action");

$smarty = new CSmartyDP();
$smarty->assign('authentication_source', $authentication_source);
$smarty->assign('activer_user_action', $activer_user_action);
$smarty->display('configure.tpl');
