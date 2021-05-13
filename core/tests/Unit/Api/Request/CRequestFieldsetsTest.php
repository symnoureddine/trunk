<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Request\CRequestFieldsets;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestFieldsetsTest extends UnitTestMediboard {

  /**
   * @param $query_content
   * @param $expected
   *
   * @dataProvider fieldSetsProvider
   */
  public function testFieldSets($query_content, $expected) {
    $req = new Request([CRequestFieldsets::QUERY_KEYWORD => $query_content]);
    $req_fieldsets = new CRequestFieldsets($req);
    $this->assertEquals($expected, $req_fieldsets->getFieldsets());
  }

  /**
   * @return array
   */
  public function fieldSetsProvider() {
    return [
      'fieldsetsNone' => [
        'none',
        ['none']
      ],
      'fieldsetsMulti' => [
        'hello' . CRequestFieldsets::FIELDSETS_SEPARATOR . 'test' . CRequestFieldsets::FIELDSETS_SEPARATOR . 'toto',
        ['hello', 'test', 'toto']
      ],
      'fieldsetsEmpty' => [
        '',
        []
      ],
      'fieldsetsWithEmpty' => [
        'foo' . CRequestFieldsets::FIELDSETS_SEPARATOR,
        ['foo', '']
      ]
    ];
  }
}