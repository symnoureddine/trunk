<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// CLI or die
PHP_SAPI === "cli" or die;

use Symfony\Component\Console\Application;

$loader = require __DIR__ . '/../vendor/autoload.php';

// Change output encoding for Windows ...
if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
    exec('CHCP 1252');
}

$application = new Application();

$dir = __DIR__ . '/classes/Console';
$ns  = "Ox\\Cli\\Console\\";

$dir_iterator = new RecursiveDirectoryIterator($dir);
$iterator     = new RecursiveIteratorIterator($dir_iterator);

foreach ($iterator as $Spl_file_info) {
    if (!$Spl_file_info->isFile()) {
        continue;
    }
    // class name
    $base_name = $Spl_file_info->getBasename('.php');

    // sub ns
    $path = $Spl_file_info->getPath();
    $path = str_replace([$dir, '/'], ['', '\\'], $path);

    if ($path) {
        if (str_starts_with($path, '\\')) {
            $path = substr($path, 1);
        }
        if (!str_ends_with($path, '\\')) {
            $path .= '\\';
        }
    }

    $class_name = $ns . $path . $base_name;


    $application->add(new $class_name());
}

$application->run();
