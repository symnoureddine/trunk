<?php
/**
 * Front Controller
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// Composer autoload
$loader = require __DIR__ . '/vendor/autoload.php';

// Request
$request = Ox\Core\Kernel\Routing\CRequestFactory::createFromGlobals();

// Kernel
$kernel = new Ox\Core\Kernel\CKernel($request);

// Response
$response = $kernel->handleRequest();
$response->send();

// Terminate
$kernel->terminate($request, $response);
