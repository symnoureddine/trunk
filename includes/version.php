<?php
/**
 * @package Mediboard\Includes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Global system version
 */
global $version;
$version = array (
  // Manual numbering
  "major" => 0,
  "minor" => 5,
  "patch" => 0,

  // Automated numbering (should be incremented at each commit)
  "build" => 901,
);

$version["string"] = implode(".", $version);
$version["version"] = "{$version['major']}.{$version['minor']}.{$version['patch']}";
