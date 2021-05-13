<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Ox\Core\CApp;
use Ox\Core\CFTP;
use Ox\Core\Chronometer;
use Ox\Core\CMbException;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Tests\UnitTestMediboard;
use stdClass;

class CFTPTest extends UnitTestMediboard {

  /**
   * TestTruncate
   */
  public function testTruncate() {
    $text = new stdClass();
    $this->assertInstanceOf(stdClass::class, CFTP::truncate($text));

    // length 100
    $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labor.';

    $string = null;
    for ($i = 0; $i <= 10; $i++) {
      $string .= $text;
    }

    $string_truncated = CFTP::truncate($string);

    $this->assertStringEndsWith('... [1100 bytes]', $string_truncated);

    $this->assertEquals(1024 + 13, strlen($string_truncated));
  }

  /**
   * @return CFTP
   */
  public function testInitError() {
    $ftp             = new CFTP();
    $exchange_source = new CSourceFTP();

    $this->expectException(CMbException::class);
    $ftp->init($exchange_source);
  }


  /**
   * @return CFTP
   */
  public function testInit() {
    $ftp             = new CFTP();
    $exchange_source = new CSourceFTP();

    $exchange_source->_id = mt_rand(1, 1000);
    $ftp->init($exchange_source);
    $this->assertInstanceOf(CSourceFTP::class, $ftp->_source);

    return $ftp;
  }


  /**
   * @param $ftp CFTP
   *
   * @depends testInit
   */
  public function testCallError(CFTP $ftp) {
    $this->expectException(CMbException::class);
    $ftp->toto();
  }

  /**
   *
   */
  public function testCallLoggable() {
    CApp::$chrono = new Chronometer();
    CApp::$chrono->start();

    $mock = $this->getMockBuilder(CFTP::class)
      ->setMethods(['_connect'])
      ->getMock();

    $mock->method('_connect')->willReturn(true);

    $exchange_source           = new CSourceFTP();
    $exchange_source->_id      = rand(1,1000);
    $exchange_source->loggable = true;

    $mock->init($exchange_source);

    $this->assertTrue($mock->connect());
  }


  /**
   * @param $ftp CFTP
   *
   * @depends testInit
   */
  public function testConnectKo(CFTP $ftp) {
    $this->expectException(CMbException::class);
    $ftp->connect();
  }

}
