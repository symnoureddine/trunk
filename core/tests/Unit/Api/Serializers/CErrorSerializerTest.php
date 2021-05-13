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
use Ox\Core\Api\Serializers\CErrorSerializer;
use Ox\Tests\UnitTestMediboard;

class CErrorSerializerTest extends UnitTestMediboard {

  public function testSerializeItem() {
    $resource = new CItem(
      [
        '_type' => 'type_test',
        '_id' => 'id_test',
        'foo' => 'bar'
      ]
    );
    $resource->setSerializer(CErrorSerializer::class);
    $serial = $resource->serialize();

    $this->assertEquals(
      [
        'errors' => [
          'foo' => 'bar'
        ]
      ],
      $serial
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

    $resource->setSerializer(CErrorSerializer::class);
    $serial = $resource->serialize();

    $this->assertEquals(
      [
        [
          'errors' => [
            'foo' => 'bar'
          ]
        ],
        [
          'errors' => [
            'foo' => 'bar1'
          ]
        ],
      ],
      $serial
    );
  }
}