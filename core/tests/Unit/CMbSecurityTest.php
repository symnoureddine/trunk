<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Ox\Core\CMbSecurity;
use Ox\Tests\UnitTestMediboard;

class CMbSecurityTest extends UnitTestMediboard {
  /**
   * Test basic AES CTR encryption
   */
  public function testAESCTREncryption() {
    $algo    = CMbSecurity::AES;
    $mode    = CMbSecurity::CTR;
    $key     = 'ThisIsMyAESCTRTestingKey';
    $clear   = 'mediboard';
    $crypted = CMbSecurity::encrypt($algo, $mode, $key, $clear);

    $this->assertEquals($crypted, 'ZoICTUtvPeYT');
  }

  /**
   * Test basic AES CTR decryption
   */
  public function testAESCTRDecryption() {
    $algo    = CMbSecurity::AES;
    $mode    = CMbSecurity::CTR;
    $key     = 'ThisIsMyAESCTRTestingKey';
    $crypted = 'ZoICTUtvPeYT';
    $clear   = CMbSecurity::decrypt($algo, $mode, $key, $crypted);

    $this->assertEquals($clear, 'mediboard');
  }

  /**
   * Test SHA256 hash
   */
  public function testSHA256Hash() {
    $algo = CMbSecurity::SHA256;
    $text = 'mediboard_testing_SHA256_hash';
    $hash = CMbSecurity::hash($algo, $text);

    $this->assertEquals($hash, 'afbf553953d7772842d44ed9278f1465593ad6fbd45070588530069c300ccd4d');
  }

  /**
   * Test specific key and value with AES CBC (bug with previous rtrim treatment)
   */
  public function testAESCBC() {
    $key   = 'e0e85fc24544ae6e8561153640e35955';
    $plain = '######1102';

    $this->assertEquals(
      CMbSecurity::decrypt(
        CMbSecurity::AES,
        CMbSecurity::CBC,
        $key,
        CMbSecurity::encrypt(
          CMbSecurity::AES,
          CMbSecurity::CBC,
          $key,
          $plain
        )
      ),
      $plain
    );
  }
}
