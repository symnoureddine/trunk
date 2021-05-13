<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestFormatsTest extends UnitTestMediboard {

  /**
   * @param string $query_content
   * @param array  $expected
   *
   * @dataProvider formatsProvider
   */
  public function testFormats($query_content, $expected) {
    $req = new Request([], [], [], [], [], ['HTTP_' . CRequestFormats::HEADER_KEY_WORD => $query_content]);
    $req_format = new CRequestFormats($req);
    $this->assertEquals($expected, $req_format->getFormats());
  }

//  /**
//   * Test format not supported
//   */
//  public function testFormatsNotSupportedException() {
//    $this->markTestSkipped('Throw exception in CRequestFormats constructor');
//
//    $req = new Request([], [], [], [], [], ['HTTP_' . CRequestFormats::HEADER_KEY_WORD => 'toto']);
//    $this->expectException(CApiRequestException::class);
//    new CRequestFormats($req);
//  }

  /**
   * @param string $query_content
   * @param array  $expected
   *
   * @dataProvider expectedFormatProvider
   */
  public function testGetExpectedFormat($query_content, $expected) {
    $req = new Request([], [], [], [], [], ['HTTP_' . CRequestFormats::HEADER_KEY_WORD => $query_content]);
    $req_format = new CRequestFormats($req);
    $this->assertEquals($expected, $req_format->getExpected());
  }

  /**
   * @return array
   */
  public function formatsProvider() {
    return [
      'noFormat' => [
        '',
        ['']
      ],
      'singleFormatJson' => [
        CRequestFormats::FORMAT_JSON,
        [CRequestFormats::FORMAT_JSON]
      ],
      'singleFormatXml' => [
        CRequestFormats::FORMAT_XML,
        [CRequestFormats::FORMAT_XML]
      ],
      'multiFormats' => [
        CRequestFormats::FORMAT_JSON . ',' . CRequestFormats::FORMAT_XML . ',' . CRequestFormats::FORMAT_HTML . ','
        . CRequestFormats::FORMAT_JSON_API,
        [CRequestFormats::FORMAT_JSON, CRequestFormats::FORMAT_XML, CRequestFormats::FORMAT_HTML, CRequestFormats::FORMAT_JSON_API]
      ]
    ];
  }

  /**
   * @return array
   */
  public function expectedFormatProvider() {
    return [
      'expectJson' => [
        'foo,' . CRequestFormats::FORMAT_JSON,
        CRequestFormats::FORMAT_JSON,
      ],
      'expectXml' => [
        'bar,' . CRequestFormats::FORMAT_XML,
        CRequestFormats::FORMAT_XML
      ],
      'expectXmlFirst' => [
        CRequestFormats::FORMAT_JSON . ',' . CRequestFormats::FORMAT_XML,
        CRequestFormats::FORMAT_XML,
      ]
    ];
  }
}