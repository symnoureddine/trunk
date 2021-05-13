<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Unit\Controllers;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Kernel\Event\CEtagListener;
use Ox\Core\SHM;
use Ox\Core\Tests\Unit\Api\Controllers\AbstractControllerRequestTest;
use Ox\Core\Tests\Unit\Api\UnitTestRequest;
use Ox\Mediboard\System\Controllers\CLocalesController;
use Ox\Mediboard\System\Controllers\CPreferencesController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CPreferencesControllerRequestTest extends AbstractControllerRequestTest
{
    protected function prepareRequest(Request $attribute_request): Request
    {
        $request = new Request();
        $request->attributes->add(
            [
                '_route'      => 'system_preferences',
                '_controller' => CPreferencesController::class . '::listPreferences',
                'mod_name'    => 'system',
                'request_api' => new CRequestApi($attribute_request),
            ]
        );

        return $request;
    }

    protected function getArgsForRequest(): array
    {
        return ['search' => 'brm'];
    }
}
