<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\TestBootstrap;

// Composer
$loader = require __DIR__ . '/../vendor/autoload.php';

$boostrap = new TestBootstrap($loader);
$boostrap->start();
