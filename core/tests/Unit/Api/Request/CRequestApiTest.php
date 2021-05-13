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
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestFieldsets;
use Ox\Core\Api\Request\CRequestFilter;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Core\Api\Request\CRequestLanguages;
use Ox\Core\Api\Request\CRequestLimit;
use Ox\Core\Api\Request\CRequestRelations;
use Ox\Core\Api\Request\CRequestSort;
use Ox\Core\CSQLDataSource;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestApiTest extends UnitTestMediboard {

  /**
   * @param array $request_params
   * @param array $request_head
   * @param array $expected
   *
   * @dataProvider requestApiProvider
   * @throws CApiRequestException
   */
  public function testSortGetFieldsOk(array $request_params, array $request_head, $expected) {
    $req = new Request($request_params, [], [], [], [], $request_head);

    $req_api = new CRequestApi($req);
    $this->assertEquals($expected['request'], $req_api->getRequest());
    $this->assertEquals($expected['request_formats'], $req_api->getFormats());
    $this->assertEquals($expected['request_limit'], $req_api->getLimit());
    $this->assertEquals($expected['request_offset'], $req_api->getOffset());
    $this->assertEquals($expected['request_limit_sql'], $req_api->getLimitAsSql());
    $this->assertEquals($expected['request_sort'], $req_api->getSort());
    $this->assertEquals($expected['request_sort_sql'], $req_api->getSortAsSql());
    $this->assertEquals($expected['request_relations'], $req_api->getRelations());
    $this->assertEquals($expected['request_relations_exclude'], $req_api->getRelationsExcluded());
    $this->assertEquals($expected['request_fieldsets'], $req_api->getFieldsets());
    $this->assertEquals($expected['request_filter'], $req_api->getFilters());
    $this->assertEquals($expected['request_filter_sql'], $req_api->getFilterAsSQL(CSQLDataSource::get('std')));
    $this->assertEquals($expected['request_languages'], $req_api->getLanguages());
    $this->assertEquals($expected['request_language_expected'], $req_api->getLanguageExpected());
  }

  /**
   * @throws CApiRequestException
   */
  public function testGetRequestParameterOk() {
    $req     = new Request([CRequestLimit::QUERY_KEYWORD_LIMIT => 20]);
    $req_limit = new CRequestLimit($req);

    $req_api = new CRequestApi($req);
    $this->assertEquals($req_limit, $req_api->getRequestParameter(CRequestLimit::class));
  }

  /**
   * @throws CApiRequestException
   */
  public function testGetRequestParameterParameterDoesNotExists() {
    $req = new Request();
    $req_api = new CRequestApi($req);
    $this->expectException(CApiRequestException::class);
    $req_api->getRequestParameter('foo');
  }


  /**
   * @return array
   */
  public function requestApiProvider() {
    $query_params = [
      CRequestLimit::QUERY_KEYWORD_LIMIT       => 20,
      CRequestLimit::QUERY_KEYWORD_OFFSET      => 100,
      CRequestSort::QUERY_KEYWORD_SORT         => '-foo' . CRequestSort::SORT_SEPARATOR . '+bar',
      CRequestRelations::QUERY_KEYWORD_INCLUDE => 'foo' . CRequestRelations::RELATION_SEPARATOR . 'toto',
      CRequestRelations::QUERY_KEYWORD_EXCLUDE => 'bar' . CRequestRelations::RELATION_SEPARATOR . 'tata',
      CRequestFieldsets::QUERY_KEYWORD         => 'test' . CRequestFieldsets::FIELDSETS_SEPARATOR . 'titi',
      CRequestFilter::QUERY_KEYWORD_FILTER     => 'test' . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_EQUAL
        . CRequestFilter::FILTER_PART_SEPARATOR . '0' . CRequestFilter::FILTER_SEPARATOR . 'toto'
        . CRequestFilter::FILTER_PART_SEPARATOR . CRequestFilter::FILTER_GREATER . CRequestFilter::FILTER_PART_SEPARATOR . 'titi'
    ];

    $query_head = [
      'HTTP_' . CRequestFormats::HEADER_KEY_WORD   => CRequestFormats::FORMAT_XML,
      'HTTP_' . CRequestLanguages::HEADER_KEY_WORD => CRequestLanguages::LONG_TAG_EN
    ];

    return [
      'emptyRequestApi' => [
        [],
        [],
        [
          'request'                   => new Request(),
          'request_formats'           => [CRequestFormats::FORMAT_JSON],
          'request_limit'             => CRequestLimit::LIMIT_DEFAULT,
          'request_offset'            => CRequestLimit::OFFSET_DEFAULT,
          'request_limit_sql'         => CRequestLimit::OFFSET_DEFAULT . ',' . CRequestLimit::LIMIT_DEFAULT,
          'request_sort'              => [],
          'request_sort_sql'          => '',
          'request_relations'         => [],
          'request_relations_exclude' => [],
          'request_fieldsets'         => [],
          'request_filter'            => [],
          'request_filter_sql'        => [],
          'request_languages'         => [CRequestLanguages::SHORT_TAG_FR],
          'request_language_expected' => CRequestLanguages::SHORT_TAG_FR,
        ]
      ],
      'RequestApi'      => [
        $query_params,
        $query_head,
        [
          'request'                   => new Request($query_params, [], [], [], [], $query_head),
          'request_formats'           => [CRequestFormats::FORMAT_XML],
          'request_limit'             => 20,
          'request_offset'            => 100,
          'request_limit_sql'         => '100,20',
          'request_sort'              => [['foo', CRequestSort::SORT_DESC], ['bar', CRequestSort::SORT_ASC]],
          'request_sort_sql'          => 'foo ' . CRequestSort::SORT_DESC . CRequestSort::SORT_SEPARATOR . 'bar '
            . CRequestSort::SORT_ASC,
          'request_relations'         => ['foo', 'toto'],
          'request_relations_exclude' => ['bar', 'tata'],
          'request_fieldsets'         => ['test', 'titi'],
          'request_filter'            => [
            new CFilter('test', CRequestFilter::FILTER_EQUAL, [0]),
            new CFilter('toto', CRequestFilter::FILTER_GREATER, ['titi'])
          ],
          'request_filter_sql'        => [
            "`test` = '0'",
            "`toto` > 'titi'"
          ],
          'request_languages'         => [CRequestLanguages::LONG_TAG_EN],
          'request_language_expected' => CRequestLanguages::LONG_TAG_EN,
        ]
      ]
    ];
  }

}