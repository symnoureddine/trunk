<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Ccam\CFraisDiversType;

/**
 * dPccam
 */
$frais_divers_type_id = CValue::getOrSession("frais_divers_type_id");

$type = new CFraisDiversType;
$type->load($frais_divers_type_id);

$list_types = $type->loadList(null, "code");

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("type", $type);
$smarty->assign("list_types", $list_types);
$smarty->display("vw_idx_frais_divers_types.tpl");
