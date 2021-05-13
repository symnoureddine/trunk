<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Request\CRequestRelations;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestRelationsTest extends UnitTestMediboard {

  /**
   * @param array $query_content
   * @param array $expected_includes
   * @param array $expected_excludes
   *
   * @dataProvider relationsProvider
   */
  public function testRelations(array $query_content, $expected_includes, $expected_excludes) {
    $req = new Request($query_content);

    $req_relations = new CRequestRelations($req);
    $this->assertEquals($expected_includes, $req_relations->getRelations());
    $this->assertEquals($expected_excludes, $req_relations->getRelationsExcludes());
  }

  /**
   * @return array
   */
  public function relationsProvider() {
    return [
      'noRelations'       => [
        [],
        [],
        []
      ],
      'includeAll'        => [
        [CRequestRelations::QUERY_KEYWORD_INCLUDE => CRequestRelations::QUERY_KEYWORD_ALL],
        [CRequestRelations::QUERY_KEYWORD_ALL],
        []
      ],
      'includeNone'       => [
        [CRequestRelations::QUERY_KEYWORD_INCLUDE => CRequestRelations::QUERY_KEYWORD_NONE],
        [CRequestRelations::QUERY_KEYWORD_NONE],
        []
      ],
      'includeMulti'      => [
        [
          CRequestRelations::QUERY_KEYWORD_INCLUDE => 'foo' . CRequestRelations::RELATION_SEPARATOR . 'bar'
            . CRequestRelations::RELATION_SEPARATOR
        ],
        ['foo', 'bar', ''],
        []
      ],
      'excludeSingle'     => [
        [CRequestRelations::QUERY_KEYWORD_EXCLUDE => 'foo'],
        [],
        ['foo']
      ],
      'excludeMulti'      => [
        [
          CRequestRelations::QUERY_KEYWORD_EXCLUDE => 'foo' . CRequestRelations::RELATION_SEPARATOR . 'bar'
            . CRequestRelations::RELATION_SEPARATOR
        ],
        [],
        ['foo', 'bar', '']
      ],
      'excludeAndInclude' => [
        [
          CRequestRelations::QUERY_KEYWORD_INCLUDE => 'foo' . CRequestRelations::RELATION_SEPARATOR . 'bar',
          CRequestRelations::QUERY_KEYWORD_EXCLUDE => 'toto' . CRequestRelations::RELATION_SEPARATOR . 'tata',
        ],
        ['foo', 'bar'],
        ['toto', 'tata'],
      ]
    ];
  }
}