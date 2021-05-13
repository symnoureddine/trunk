<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;

use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\Api\Request\CFilter;
use Ox\Core\Api\Request\CRequestFilter;
use Ox\Core\CSQLDataSource;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestFilterTest extends UnitTestMediboard {
  /** @var CRequestFilter */
  private $req_filter;

  /** @var CFilter[] */
  private $filters;

  /**
   * @param array $query
   *
   * @dataProvider getSqlFilterProvider
   * @throws CApiRequestException
   */
  public function testGetSqlFilter(array $query) {
    $req = new Request($query['query']);

    $filter = new CRequestFilter($req);
    $where  = $filter->getSqlFilter(CSQLDataSource::get('std'));
    $this->assertEquals($query['expected'], $where);
  }

  /**
   * @throws CApiRequestException
   */
  public function testGetSqlFilterOperatorDoesNotExists() {
    $req = new Request(
      [
        CRequestFilter::QUERY_KEYWORD_FILTER => 'bar' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IS_NOT_NULL
          . CRequestFilter::FILTER_SEPARATOR . 'foo' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_LESS_OR_EQUAL
          . '5' . CRequestFilter::FILTER_PART_SEPARATOR . '500' . CRequestFilter::FILTER_SEPARATOR . 'arg2'
          . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_DO_NOT_CONTAINS . 'hey'
          . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_EQUAL . CRequestFilter::FILTER_PART_SEPARATOR . '   '
          . CRequestFilter::FILTER_DO_NOT_BEGIN_WITH
      ]
    );

    $filter = new CRequestFilter($req);
    $this->expectException(CApiRequestException::class);
    $filter->getSqlFilter(CSQLDataSource::get('std'));
  }

  /**
   * @throws CApiRequestException
   */
  public function testgetSqlFilterWithoutKey() {
    $filter = new CFilter('', 'equal', ['test']);

    $req        = new Request();
    $req_filter = new CRequestFilter($req);
    $req_filter->addFilter($filter);

    $this->expectException(CApiRequestException::class);
    $req_filter->getSqlFilter(CSQLDataSource::get('std'));
  }

  /**
   * Test the iterator functions of CRequestFilter
   */
  public function testCRequestFilterIterator() {
    $this->prepareRequestFilter();
    $this->isIterable($this->req_filter, $this->filters, 5);
  }

  /**
   * Test count
   *
   * @return void
   */
  public function testCRequestFilterCount() {
    $this->prepareRequestFilter();
    $this->isCountable($this->req_filter, 5);
  }

  /**
   * @throws CApiRequestException
   */
  public function testRemoveFilterReindex() {
    $this->prepareRequestFilter();

    $this->req_filter->removeFilter(2, true);
    $this->assertCount(4, $this->req_filter);
    $this->assertEquals([0, 1, 2, 3], array_keys($this->req_filter->getFilters()));
  }

  /**
   * @throws CApiRequestException
   */
  public function testRemoveFilterIndexDoesNotExists() {
    $this->prepareRequestFilter();
    $this->expectException(CApiRequestException::class);
    $this->req_filter->removeFilter(10);
  }

  /**
   * @return void
   */
  public function testExistingFilters() {
    $req        = new Request();
    $req_filter = new CRequestFilter($req);
    $this->assertEquals(
      [
        CRequestFilter::FILTER_EQUAL             => '= ?',
        CRequestFilter::FILTER_NOT_EQUAL         => '!= ?',
        CRequestFilter::FILTER_LESS              => '< ?',
        CRequestFilter::FILTER_LESS_OR_EQUAL     => '<= ?',
        CRequestFilter::FILTER_GREATER           => '> ?',
        CRequestFilter::FILTER_GREATER_OR_EQUAL  => '>= ?',
        CRequestFilter::FILTER_BEGIN_WITH        => '?%',
        CRequestFilter::FILTER_CONTAINS          => '%?%',
        CRequestFilter::FILTER_END_WITH          => '%?',
        CRequestFilter::FILTER_DO_NOT_BEGIN_WITH => '?%',
        CRequestFilter::FILTER_DO_NOT_CONTAINS   => '%?%',
        CRequestFilter::FILTER_DO_NOT_END_WITH   => '%?',
        CRequestFilter::FILTER_IN                => 'IN ?',
        CRequestFilter::FILTER_NOT_IN            => 'NOT ' . CRequestFilter::FILTER_IN,
        CRequestFilter::FILTER_IS_NULL           => 'IS NULL',
        CRequestFilter::FILTER_IS_NOT_NULL       => 'IS NOT NULL',
        CRequestFilter::FILTER_IS_EMPTY          => '= ""',
        CRequestFilter::FILTER_IS_NOT_EMPTY      => '!= ""',
      ],
      $req_filter->getExistingFilters()
    );
  }

  /**
   * @return void
   */
  private function prepareRequestFilter() {
    $this->req_filter = new CRequestFilter(new Request());
    $this->filters    = [
      new CFilter('test1', 'equal', ['value1']),
      new CFilter('test2', 'equal', ['value2']),
      new CFilter('test3', 'equal', ['value3']),
      new CFilter('test4', 'equal', ['value4']),
      new CFilter('test5', 'equal', ['value5']),
    ];

    foreach ($this->filters as $_filter) {
      $this->req_filter->addFilter($_filter);
    }
  }


  /**
   * @return array
   */
  public function getSqlFilterProvider(): array {
    return [
      'filterEqual' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'test_bool' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_EQUAL
              . CRequestFilter::FILTER_PART_SEPARATOR . '1',
          ],
          'expected' => [
            "`test_bool` = '1'",
          ]
        ],
      ],

      'filterBeginWith' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'foo' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_BEGIN_WITH
              . CRequestFilter::FILTER_PART_SEPARATOR . 'test'
          ],
          'expected' => [
            "`foo` LIKE 'test%'"
          ]
        ]
      ],

      'filterDoNotContains' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'foo' . CRequestFilter::FILTER_PART_SEPARATOR
              . CRequestFilter::FILTER_DO_NOT_CONTAINS . CRequestFilter::FILTER_PART_SEPARATOR . 'test'
          ],
          'expected' => [
            "`foo` NOT LIKE '%test%'"
          ]
        ]
      ],

      'filterInNotIn' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'bar' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IN
              . CRequestFilter::FILTER_PART_SEPARATOR . 'test' . CRequestFilter::FILTER_PART_SEPARATOR . '0'
              . CRequestFilter::FILTER_PART_SEPARATOR . '1' . CRequestFilter::FILTER_PART_SEPARATOR . 'hello'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IN . CRequestFilter::FILTER_SEPARATOR . 'foo'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_IN . CRequestFilter::FILTER_PART_SEPARATOR
              . 'toto' . CRequestFilter::FILTER_PART_SEPARATOR . 'hello'
          ],
          'expected' => [
            "`bar` IN ('test', '0', '1', 'hello', 'in')",
            "`foo` NOT IN ('toto', 'hello')",
          ]
        ]
      ],

      'filterIsNull' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'bar' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IS_NULL
          ],
          'expected' => [
            "`bar` IS NULL"
          ]
        ]
      ],

      'filterMultiIgnoreLast' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'bar' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IS_NOT_NULL
              . CRequestFilter::FILTER_SEPARATOR . ' foo' . CRequestFilter::FILTER_PART_SEPARATOR
              . CRequestFilter::FILTER_LESS_OR_EQUAL . CRequestFilter::FILTER_PART_SEPARATOR . '500' . CRequestFilter::FILTER_SEPARATOR
              . '     arg2    ' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_DO_NOT_CONTAINS
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_EQUAL . CRequestFilter::FILTER_PART_SEPARATOR
              . '   ' . CRequestFilter::FILTER_DO_NOT_BEGIN_WITH

          ],
          'expected' => [
            "`bar` IS NOT NULL",
            "`foo` <= '500'",
            "`arg2` NOT LIKE '%notEqual%'",
          ]
        ]
      ],

      'filterEmptyArguments' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'bar' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IN
              . CRequestFilter::FILTER_SEPARATOR . 'toto' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_EQUAL
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_SEPARATOR . 'string'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_EQUAL . CRequestFilter::FILTER_SEPARATOR . 'bar2'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_NOT_IN . CRequestFilter::FILTER_PART_SEPARATOR
              . CRequestFilter::FILTER_SEPARATOR . 'again' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_END_WITH
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_SEPARATOR . 'toto'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_IS_NOT_EMPTY

          ],
          'expected' => [
            '`toto` != ""'
          ]
        ]
      ],

      'filterEscape' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'fo`o' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_EQUAL
              . CRequestFilter::FILTER_PART_SEPARATOR . '5' . CRequestFilter::FILTER_SEPARATOR . 'ba\'r'
              . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_CONTAINS . CRequestFilter::FILTER_PART_SEPARATOR
              . 'te`s\'t'

          ],
          'expected' => [
            "`foo` = '5'",
            "`ba'r` LIKE '%te`s\\'t%'"
          ]
        ]
      ],

      'filterUrlDecode' => [
        [
          'query'    => [
            CRequestFilter::QUERY_KEYWORD_FILTER => 'fo%20o' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_CONTAINS
              . CRequestFilter::FILTER_PART_SEPARATOR . 'test%2ehaha'

          ],
          'expected' => [
            "`fo o` LIKE '%test.haha%'",
          ]
        ]
      ],
    ];
  }
}