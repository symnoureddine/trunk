<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Ox\Core\CMbString;
use Ox\Mediboard\Patients\CPatient;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CMbStringTest extends UnitTestMediboard {

  public function testToWords() {
    $text1 = CMbString::toWords(1983.36);
    $text2 = "mille neuf cent quatre-vingt-trois virgule trente-six";
    $this->assertEquals($text1, $text2);
  }

  /**
   * @param string|object $text
   * @param int           $size
   * @param string|object $expected
   * @param string        $replacement
   *
   * @dataProvider truncateTextProvider
   */
  public function testTruncate($text, $size, $expected, $replacement = '...') {
    $this->assertEquals($expected, CMbString::truncate($text, $size, $replacement));
  }

  /**
   * Provide text and expected results for the truncate function
   *
   * @return array
   */
  public function truncateTextProvider() {
    return array(
      ['toto', 10, 'toto'],
      [new CPatient, 10, new CPatient()],
      ['Test truncate text too long for it', 10, 'Test tr...'],
      ['Test truncate text too long for it', 12, 'Test trun?!?', '?!?'],
    );
  }

  /**
   * Test for the function uriToArray which parses an URI and returns an array [protocol, host, params ...]
   */
  public function testUriToArray() {
    $expected = [
      "scheme" => "https",
      "host"   => "mediboard.com",
      "path"   => null,
      "params" => [
        "sejour_id" => "1111",
        "patient"   => "1234"
      ]
    ];

    $this->assertEquals($expected, CMbString::uriToArray("https://mediboard.com?sejour_id=1111&patient=1234"));

    $expected["path"] = "//";
    $this->assertEquals($expected, CMbString::uriToArray("https://mediboard.com//?sejour_id=1111&patient=1234"));

    $expected["path"] = "/index.php";
    $this->assertEquals($expected, CMbString::uriToArray("https://mediboard.com/index.php?sejour_id=1111&patient=1234"));

    $expected["path"]   = null;
    $expected["params"] = null;
    $this->assertEquals($expected, CMbString::uriToArray("https://mediboard.com"));

    $expected["path"] = "/";
    $this->assertEquals($expected, CMbString::uriToArray("https://mediboard.com/"));
  }

  /**
   * Test is base64 string
   *
   * @param string $string The string to be tested
   */
  public function testIsBase64() {
    $string = "I am not base 64 encoded";
    $this->assertFalse(CMbString::isBase64($string));

    $string = base64_encode($string);
    $this->assertTrue(CMbString::isBase64($string));
  }

  /**
   * @param string $code
   * @param bool   $expected
   *
   * @dataProvider isLuhnProvider
   */
  public function testIsLuhn(?string $code, bool $expected) {
    $this->assertEquals($expected, CMbString::luhn($code));
  }

  public function isLuhnProvider() {
    return [
      'number_is_luhn1'       => ['15362', true],
      'number_is_luhn2'       => ['999985566622', true],
      'number_is_luhn3'       => ['0', true],
      'number_is_luhn_letter' => ['15A362', true],
      'number_is_luhn_space'  => ['15 36 2', true],
      'number_is_luhn_empty'  => ['', true],
      'number_is_luhn_null'   => [null, true],
      'number_is_not_luhn1'   => ['12255566', false],
//      'number_is_not_luhn2'   => [598776, false],
    ];
  }

  /**
   * @param string|null $code
   * @param bool        $expected
   *
   * @dataProvider isLuhnForAdeliProvider
   */
  public function testIsAdeliLuhn(?string $code, bool $expected) {
    $this->assertEquals($expected, CMbString::luhnForAdeli($code));
  }

  public function isLuhnForAdeliProvider() {
    return [
      'number_is_luhn1'       => ['15362', true],
      'number_is_luhn2'       => ['999985566622', true],
      'number_is_luhn3'       => ['0', true],
      'number_is_luhn_letter' => ['15A362', false],
      'number_is_luhn_space'  => ['15 36 2', true],
      'number_is_luhn_empty'  => ['', true],
      'number_is_luhn_null'   => [null, true],
      'number_is_luhn_adeli'  => ['9DA005191', true],
//      'number_is_not_luhn1'   => [598776, false],
      'number_is_not_luhn2'   => ['aaaaaaaaa', false],
    ];
  }
}
