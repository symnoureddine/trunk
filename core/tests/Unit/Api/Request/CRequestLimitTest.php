<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Request\CRequestLimit;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestLimitTest extends UnitTestMediboard {

  /**
   * @param array $query_content
   * @param int   $expected_offset
   * @param int   $expected_limit
   *
   * @dataProvider limitOkProvider
   */
  public function testLimitOk(array $query_content, $expected_offset, $expected_limit) {
    $req = new Request($query_content);

    $req_limit = new CRequestLimit($req);
    $this->assertEquals($expected_offset, $req_limit->getOffset());
    $this->assertEquals($expected_limit, $req_limit->getLimit());
    $this->assertEquals("{$expected_offset},{$expected_limit}", $req_limit->getSqlLimit());
  }

  /**
   * Limit in query
   */
  public function testLimitInQuery() {
    $req = new Request([CRequestLimit::QUERY_KEYWORD_LIMIT => 20]);
    $req_limit = new CRequestLimit($req);
    $this->assertTrue($req_limit->isInQuery());

    $req = new Request();
    $req_limit = new CRequestLimit($req);
    $this->assertFalse($req_limit->isInQuery());
  }

  /**
   * @return array
   */
  public function limitOkProvider() {
    return [
      'noLimit' => [
        [],
        CRequestLimit::OFFSET_DEFAULT,
        CRequestLimit::LIMIT_DEFAULT,
      ],
      'onlyOffset' => [
        [CRequestLimit::QUERY_KEYWORD_OFFSET => 10],
        10,
        CRequestLimit::LIMIT_DEFAULT
      ],
      'onlyLimit' => [
        [CRequestLimit::QUERY_KEYWORD_LIMIT => 10],
        CRequestLimit::OFFSET_DEFAULT,
        10
      ],
      'offsetAndLimit' => [
        [CRequestLimit::QUERY_KEYWORD_LIMIT => 10, CRequestLimit::QUERY_KEYWORD_OFFSET => 20],
        20,
        10
      ],
      'limitGreaterThanMax' => [
        [CRequestLimit::QUERY_KEYWORD_LIMIT => CRequestLimit::LIMIT_MAX*10],
        CRequestLimit::OFFSET_DEFAULT,
        CRequestLimit::LIMIT_MAX
      ]
    ];
  }
}