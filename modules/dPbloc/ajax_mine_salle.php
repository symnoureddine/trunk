<?php
/**
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Bloc\CDailySalleOccupation;

CCanDo::checkEdit();

$miner = new CDailySalleOccupation();
mbTrace($miner->countUnmined(), "unmined");
mbTrace($miner->countUnremined(), "un-remined");
mbTrace($miner->countUnpostmined(), "un-postmined");

$smarty = new CSmartyDP();
$smarty->display("inc_mine_salle.tpl");