<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;

/**
 * Stats sur les documents
 */
global $m;
$m = "dPfiles";
$_GET["doc_class"] = "CCompteRendu";
CAppUI::requireModuleFile($m, "vw_stats");
$m = "dPcompteRendu";
