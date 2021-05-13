<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Unit\Controllers;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\SHM;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\Controllers\CPreferencesController;
use Ox\Mediboard\System\CPreferences;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CPreferencesControllerTest extends UnitTestMediboard
{
    public function setUp(): void
    {
        parent::setUp();

        SHM::remKeys(CPreferencesController::CACHE_PREFIX . '-*');
    }

    public function testModuleDoesNotExists(): void
    {
        $controller = new CPreferencesController();
        $this->expectExceptionMessage("Module 'dPtoto' does not exists or is not active");
        $this->invokePrivateMethod($controller, 'loadModulePrefs', 'toto');
    }

    public function testLoadModulePrefs(): void
    {
        CPreferences::$modules['system'] = [];
        include dirname(__DIR__, 3) . '/preferences.php';
        $expected_prefs = CPreferences::$modules['system'];

        $controller = new CPreferencesController();
        $prefs      = $this->invokePrivateMethod($controller, 'loadModulePrefs', 'system');
        $this->assertEquals($expected_prefs, $prefs);
    }

    public function testLoadModuleWithNoPrefs(): void
    {
        $controller = new CPreferencesController();
        $prefs      = $this->invokePrivateMethod($controller, 'loadModulePrefs', 'openData');
        $this->assertEquals([], $prefs);
    }

    public function testDefaultPreferencesResponseIsOk(): void
    {
        $request_api = new CRequestApi(new Request());
        $controller  = new CPreferencesController();
        $response    = $controller->listPreferences('system', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('preferences', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }

    public function testDefaultPreferencesResponseIsOkWithDp(): void
    {
        $request_api = new CRequestApi(new Request());
        $controller  = new CPreferencesController();
        $response    = $controller->listPreferences('patients', $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('preferences', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }

    public function testListProfilePreferencesProfileDoesNotExists(): void
    {
        $request_api = new CRequestApi(new Request());
        $controller  = new CPreferencesController();

        $profile_name = uniqid();

        $this->expectExceptionMessage("Profile '{$profile_name}' does not exists or is not active");
        $controller->listProfilePreferences('patients', $profile_name, $request_api);
    }

    public function testListProfilePreferences(): void
    {
        $profile = new CUser();
        $profile->template = 1;
        $profile->loadMatchingObjectEsc();

        $request_api = new CRequestApi(new Request());
        $controller  = new CPreferencesController();
        $response    = $controller->listProfilePreferences('system', $profile->user_username, $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('preferences', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }

    public function testListUsersPreferences(): void
    {
        $request_api = new CRequestApi(new Request());
        $controller  = new CPreferencesController();
        $response    = $controller->listUserPreferences('system', CMediusers::get()->loadRefUser(), $request_api);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('preferences', $content['data']['type']);

        $this->assertTrue(count($content['data']['attributes']) > 0);
    }
}
