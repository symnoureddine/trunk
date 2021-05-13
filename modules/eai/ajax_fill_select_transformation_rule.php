<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CValue;

/**
 * Edit transformaiton rule EAI
 */
CCanDo::checkAdmin();

$standard_name = CValue::get("standard_name");
$select_type   = CValue::get("select_type");

mbTrace($standard_name);