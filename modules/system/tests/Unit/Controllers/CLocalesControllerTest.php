<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Unit\Controllers;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Mediboard\System\Api\LocalesFilter;
use Ox\Mediboard\System\Controllers\CLocalesController;
use Ox\Mediboard\System\CTranslationOverwrite;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CLocalesControllerTest extends UnitTestMediboard
{
    public function testModuleDoesNotExists()
    {
        $request_api = new CRequestApi(new Request());

        $controller = new CLocalesController();
        $this->expectExceptionMessage("Module 'dPtoto' does not exists or is not active");
        $controller->listLocales('fr', 'toto', $request_api);
    }

    public function testResponseIsOk()
    {
        $request_api = new CRequestApi(new Request());
        $controller = new CLocalesController();
        $response = $controller->listLocales('fr', 'system', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('locales', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }

    public function testResponseIsOkWithDp()
    {
        $request_api = new CRequestApi(new Request());
        $controller = new CLocalesController();
        $response = $controller->listLocales('fr', 'patients', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('locales', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }

    public function testFilterEmptyResult()
    {
        $request_api = new CRequestApi(new Request(['search' => uniqid()]));
        $controller = new CLocalesController();
        $response = $controller->listLocales('fr', 'system', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('locales', $content['data']['type']);

        $this->assertCount(0, $content['data']['attributes']);
    }

    public function testFilterOk()
    {
        $request_api = new CRequestApi(
            new Request(
                [
                    'search' => 'CAbon',
                    'search_mode' => LocalesFilter::SEARCH_MODE_CONTAINS,
                    'search_in' => LocalesFilter::SEARCH_IN_KEY
                ]
            )
        );

        $controller = new CLocalesController();
        $response_filtered = $controller->listLocales('fr', 'system', $request_api);

        $this->assertEquals(200, $response_filtered->getStatusCode());

        $content_filter = json_decode($response_filtered->getContent(), true);
        $this->assertEquals('locales', $content_filter['data']['type']);

        $request_api = new CRequestApi(new Request());
        $response = $controller->listLocales('fr', 'system', $request_api);
        $content = json_decode($response->getContent(), true);

        // Filtered response must have less elements than base response
        $this->assertTrue(count($content_filter['data']['attributes']) < count($content['data']['attributes']));
    }

    public function testApplyTranslationsOverwrite()
    {
        $new_trans = uniqid();

        $translation = new CTranslationOverwrite();
        $translation->language = 'fr';
        $translation->source = 'Aggregate';
        $translation->translation = $new_trans;
        if ($msg = $translation->store()) {
            $this->fail($msg);
        }

        $request_api = new CRequestApi(new Request());
        $controller = new CLocalesController();
        $response = $controller->listLocales('fr', 'system', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('locales', $content['data']['type']);

        $this->assertEquals($new_trans, $content['data']['attributes']['Aggregate']);
    }
}
