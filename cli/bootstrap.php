<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * CLI boostrap
 */
// CLI or die
PHP_SAPI === "cli" or die;

$mbpath = dirname(__DIR__).'/';

// Composer
if (is_file($mbpath . "vendor/autoload.php")) {
  require $mbpath . "vendor/autoload.php";
}
