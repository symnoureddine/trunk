<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\Api\Request\CRequestLimit;
use Ox\Core\Api\Request\CRequestSort;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestSortTest extends UnitTestMediboard {

  /**
   * @param string $query_content
   * @param array  $expected
   *
   * @dataProvider sortGetFieldsOkProvider
   * @throws \Ox\Core\Api\Exceptions\CApiException
   */
  public function testSortGetFieldsOk(?string $query_content, $expected) {
    $req = new Request([CRequestSort::QUERY_KEYWORD_SORT => $query_content]);

    $req_sort = new CRequestSort($req);
    $this->assertEquals($expected, $req_sort->getFields());
  }

  /**
   * Test throw exception
   */
  public function testSortGetFieldsKo() {
    $req = new Request([CRequestSort::QUERY_KEYWORD_SORT => 'foo bar,+toto']);
    $this->expectException(CApiRequestException::class);
    new CRequestSort($req);
  }


  /**
   * @param      $query_content
   * @param      $expected
   * @param null $default
   *
   * @throws CApiRequestException
   *
   * @dataProvider sortGetSqlOrderByProvider
   */
  public function testSortGetSqlOrderBy($query_content, $expected, $default = null) {
    $req = new Request([CRequestSort::QUERY_KEYWORD_SORT => $query_content]);

    $req_sort = new CRequestSort($req);
    $this->assertEquals($expected, $req_sort->getSqlOrderBy($default));
  }

  /**
   * @return array
   */
  public function sortGetFieldsOkProvider() {
    return [
      'sortNull'       => [
        null,
        [],
      ],
      'sortEmpty'      => [
        '',
        []
      ],
      'sortOneAsc'     => [
        '+test',
        [['test', CRequestSort::SORT_ASC]]
      ],
      'sortOneDesc'    => [
        '-bar',
        [['bar', CRequestSort::SORT_DESC]]
      ],
      'sortOneDefault' => [
        'foo',
        [['foo', CRequestSort::SORT_ASC]]
      ],
      'sortMulti'      => [
        '-foo' . CRequestSort::SORT_SEPARATOR . 'bar' . CRequestSort::SORT_SEPARATOR . '+test' . CRequestSort::SORT_SEPARATOR
        . '-toto',
        [
          ['foo', CRequestSort::SORT_DESC],
          ['bar', CRequestSort::SORT_ASC],
          ['test', CRequestSort::SORT_ASC],
          ['toto', CRequestSort::SORT_DESC],
        ]
      ],
      'sortAddSlashes' => [
        '-f\o' . CRequestSort::SORT_SEPARATOR . 'ba"r' . CRequestSort::SORT_SEPARATOR . "+tes\\t",
        [
          ['f\\\\o', CRequestSort::SORT_DESC],
          ['ba\"r', CRequestSort::SORT_ASC],
          ['tes\\\\t', CRequestSort::SORT_ASC],
        ]
      ]
    ];
  }

  /**
   * @return array
   */
  public function sortGetSqlOrderByProvider() {
    return [
      'sortNullNoDefault'   => [
        null,
        null,
      ],
      'sortNullWithDefault' => [
        null,
        'foo',
        'foo',
      ],
      'sortOneField' => [
        '-foo',
        'foo ' . CRequestSort::SORT_DESC
      ],
      'sortMultiFields' => [
        '-foo' . CRequestSort::SORT_SEPARATOR . '-bar' . CRequestSort::SORT_SEPARATOR . 'toto',
        'foo ' . CRequestSort::SORT_DESC . ',bar ' . CRequestSort::SORT_DESC . ',toto ' . CRequestSort::SORT_ASC
      ]
    ];
  }
}