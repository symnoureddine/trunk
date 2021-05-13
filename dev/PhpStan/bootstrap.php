<?php
/**
 * @package Mediboard\Dev
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Autoload\CAutoloadAlias;
use Ox\Core\CClassMap;

/**
 * PHPStan boostrap file
 */

// AutoloadAlias
CClassMap::init();
CAutoloadAlias::register();

// Config
global $dPconfig;
include_once __DIR__ . "/../../includes/config_all.php";

// Timezone
date_default_timezone_set($dPconfig["timezone"]);

// Constants
define("UI_MSG_OK", 1);
define("UI_MSG_ALERT", 2);
define("UI_MSG_WARNING", 3);
define("UI_MSG_ERROR", 4);
define("PERM_DENY" , 0);
define("PERM_READ" , 1);
define("PERM_EDIT" , 2);