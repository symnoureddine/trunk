<?php
/**
 * @package Core\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Ox\Core\CApp;
use Ox\Tests\UnitTestMediboard;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CAppTest
 * @package Ox\Core\Tests\Unit
 */
class CAppTest extends UnitTestMediboard {
  /**
   * Expected order of shutdown callbacks
   * - Apps at first, in order of registering
   * - AUTOLOAD
   * - EVENT
   * - MUTEX
   * - PEACE
   * - ERROR
   */
  public function testRegisterShutdown() {
    $expected_order = array(
      array(static::class, 'app1Shutdown'),
      array(static::class, 'app2Shutdown'),
      array(static::class, 'app3Shutdown'),
      array(static::class, 'autoloadShutdown'),
      array(static::class, 'eventShutdown'),
      array(static::class, 'mutexShutdown'),
      array(static::class, 'sessionShutdown'),
      array(static::class, 'peaceShutdown'),
      array(static::class, 'errorShutdown'),
      array(static::class, 'cronShutdown'),
    );

    // Random call order, but should conserve the correct sorting thanks to priorities
    CApp::registerShutdown(array(static::class, 'errorShutdown'), CApp::ERROR_PRIORITY);
    CApp::registerShutdown(array(static::class, 'app1Shutdown'), CApp::APP_PRIORITY);
    CApp::registerShutdown(array(static::class, 'mutexShutdown'), CApp::MUTEX_PRIORITY);
    CApp::registerShutdown(array(static::class, 'cronShutdown'), CApp::CRON_PRIORITY);
    CApp::registerShutdown(array(static::class, 'app2Shutdown'), CApp::APP_PRIORITY);
    CApp::registerShutdown(array(static::class, 'peaceShutdown'), CApp::PEACE_PRIORITY);
    CApp::registerShutdown(array(static::class, 'sessionShutdown'), CApp::SESSION_PRIORITY);
    CApp::registerShutdown(array(static::class, 'app3Shutdown'), CApp::APP_PRIORITY);
    CApp::registerShutdown(array(static::class, 'autoloadShutdown'), CApp::AUTOLOAD_PRIORITY);
    CApp::registerShutdown(array(static::class, 'eventShutdown'), CApp::EVENT_PRIORITY);

    $callbacks_order = [];
    foreach (CApp::getShutdownCallbacks() as $_callback) {
      $callbacks_order[] = $_callback;
    }

    // Asserting the priorities are what we expected
    $this->assertEquals($expected_order, $callbacks_order);
  }

  public function errorShutdown() {
  }

  public function mutexShutdown() {
  }

  public function sessionShutdown() {
  }

  public function autoloadShutdown() {
  }

  public function eventShutdown() {
  }

  public function peaceShutdown() {
  }

  public function app1Shutdown() {
  }

  public function app2Shutdown() {
  }

  public function app3Shutdown() {
  }

  public function cronShutdown() {
  }

  public function testSingelton() {
    $instance = CApp::getInstance();
    $this->assertInstanceOf(CApp::class, $instance);
    $this->assertDirectoryExists($this->invokePrivateMethod($instance, 'getRootDir'));
    $this->assertSame($instance, CApp::getInstance());
  }

  public function testStart(){
    $instance = CApp::getInstance();
    $reflexion = new ReflectionClass($instance);
    $prop      = $reflexion->getProperty('is_started');
    $prop->setAccessible(true);
    $prop->setValue($instance, true);
    $this->expectExceptionMessage('The app is already started.');
    $instance->start(new Request());
    $prop->setValue($instance, false);
  }

  public function testStop() {
    $instance = CApp::getInstance();
    $reflexion = new ReflectionClass($instance);
    $prop      = $reflexion->getProperty('is_started');
    // restore default value
    $prop->setAccessible(true);
    $prop->setValue($instance, false);

    $req      = new Request();
    $this->expectExceptionMessage('The app is not started.');
    $instance->stop($req);
  }

  public function testTerminated() {
    $instance = CApp::getInstance();
    $req      = new Request();
    $this->expectExceptionMessage('The app is not start&stop correctly.');
    $instance->terminate($req);
  }
}
