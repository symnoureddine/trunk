<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Serializers;

use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Api\Serializers\CArraySerializer;
use Ox\Tests\UnitTestMediboard;

class CArraySerializerTest extends UnitTestMediboard {

  public function testSerializeItem() {
    $resource = new CItem(
      [
        '_type' => 'type_test',
        '_id' => 'id_test',
        'foo' => 'bar'
      ]
    );
    $resource->setSerializer(CArraySerializer::class);
    $serial = $resource->serialize();

    // Do not check details in meta because of timestamps
    $this->assertArrayHasKey('metas', $serial);
    // No links for array
    $this->assertTrue(empty($serial['links']));

    $this->assertEquals(
      [
        '_type' => 'type_test',
        '_id' => 'id_test',
        'foo' => 'bar'
      ],
      $serial['datas']
    );
  }

  public function testSerializeCollection() {
    $resource = new CCollection(
      [
        [
          '_type' => 'type_test',
          '_id' => 'id_test',
          'foo' => 'bar'
        ],
        [
          '_type' => 'type_test2',
          '_id' => 'test_bar',
          'foo' => 'bar1'
        ],
      ]
    );

    $resource->setSerializer(CArraySerializer::class);
    $serial = $resource->serialize();

    // Do not check details in meta because of timestamps
    $this->assertArrayHasKey('metas', $serial);
    // No links for array
    $this->assertTrue(empty($serial['links']));

    $this->assertEquals(
      [
        [
          'datas' => [
            '_type' => 'type_test',
            '_id' => 'id_test',
            'foo' => 'bar'
          ]
        ],
        [
          'datas' => [
            '_type' => 'type_test2',
            '_id' => 'test_bar',
            'foo' => 'bar1'
          ]
        ],
      ],
      $serial['datas']
    );
  }
}