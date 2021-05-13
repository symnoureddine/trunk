<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// Création du template
use Ox\Core\CSmartyDP;

$smarty = new CSmartyDP();
$smarty->display("inc_legend_planning_astreinte");