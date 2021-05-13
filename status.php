<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbConfig;
use Ox\Core\CSQLDataSource;
use Ox\Core\SHM;

error_reporting(0);

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/includes/config_all.php";

/**
 * Basic output function for the application status
 *
 * @param array $status Status info
 *
 * @return void
 */
function out($status)
{
    global $version;

    $releaseInfo = CApp::getReleaseInfo();

    $branch = CMbArray::get($releaseInfo, "releaseCode");

    // Headers
    http_response_code($status["code"]);
    header("Content-type: application/json");

    $status["time"] = strftime("%Y-%m-%d %H:%M:%S");

    $status["app"] = [
        "version" => $version,
        "branch"  => $branch,
    ];

    // Output
    echo json_encode($status);
    exit();
}

try {
    // Check that the user has correctly set the root directory
    if (!is_file($dPconfig["root_dir"] . "/includes/config.php")) {
        throw new Exception("not configured", 503);
    }

    SHM::init(); // previously init() called in code out of class SHM

    // Offline mode
    if ($dPconfig["offline"]) {
        throw new Exception("offline", 503);
    }

    $ds = @CSQLDataSource::get("std");
    if (!$ds) {
        throw new Exception("db error", 500);
    }

    // Include config in DB
    if (CAppUI::conf("config_db")) {
        CMbConfig::loadValuesFromDB();
    }

    // Init shared memory, must be after DB init
    SHM::initDistributed();

    $status = [
        "code"   => 200,
        "status" => "ok",
    ];
} catch (Exception $exception) {
    $status = [
        "code"   => $exception->getCode(),
        "status" => $exception->getMessage(),
    ];
}

out($status);
