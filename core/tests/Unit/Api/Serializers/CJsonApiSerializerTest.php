<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Serializers;

use Ox\Core\Api\Request\CRequestRelations;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Api\Serializers\CJsonApiSerializer;
use Ox\Core\Tests\Resources\CLoremIpsum;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CUserLog;
use Ox\Tests\UnitTestMediboard;

class CJsonApiSerializerTest extends UnitTestMediboard {

  public function testSerializeItemObject() {
    $resource = new CItem(new CLoremIpsum(5, 'testlorem', 'foo_bar'));
    $resource->setName('loremIpsum');
    $resource->setSerializer(CJsonApiSerializer::class);
    $serial = $resource->serialize();

    // Do not check details in meta because of timestamps
    $this->assertArrayHasKey('meta', $serial);
    // No links for object or array
    $this->assertArrayNotHasKey('links', $serial);
    // No relations for object or array
    $this->assertArrayNotHasKey('relations', $serial);

    $this->assertEquals(
      [
        'type'       => 'loremIpsum',
        'id'         => 5,
        'attributes' => [
          'id'      => 5,
          'type'    => 'testlorem',
          'libelle' => 'foo_bar'
        ]
      ],
      $serial['data']
    );
  }

  public function testSerializeCollectionObject() {
    $resource = new CCollection([new CLoremIpsum(5, 'testlorem', 'foo_bar'), new CLoremIpsum(2, 'hey_there', 'bar_foo')]);
    $resource->setName('Ipsum');
    $resource->setSerializer(CJsonApiSerializer::class);
    $serial = $resource->serialize();

    // Do not check details in meta because of timestamps
    $this->assertArrayHasKey('meta', $serial);
    // No links for object or array
    $this->assertArrayNotHasKey('links', $serial);
    // No relations for object or array
    $this->assertArrayNotHasKey('relations', $serial);

    $this->assertEquals(
      [
        [
          'type'       => 'Ipsum',
          'id'         => 5,
          'attributes' => [
            'id'      => 5,
            'type'    => 'testlorem',
            'libelle' => 'foo_bar'
          ]
        ],
        [
          'type'       => 'Ipsum',
          'id'         => 2,
          'attributes' => [
            'id'      => 2,
            'type'    => 'hey_there',
            'libelle' => 'bar_foo'
          ]
        ],
      ],
      $serial['data']
    );
  }

  public function testSerializeItemCMbObject() {
    $log      = $this->getUserLog();
    $resource = new CItem($log);
    $resource->setModelRelations(CRequestRelations::QUERY_KEYWORD_ALL);
    $resource->setSerializer(CJsonApiSerializer::class);

    $serial = $resource->serialize();

    $this->assertEquals(CUserLog::RESOURCE_NAME, $serial['data']['type']);
    $this->assertArrayHasKey('attributes', $serial['data']);

    $this->assertArrayHasKey('relationships', $serial['data']);
    $user_id   = $serial['data']['relationships']['user']['data']['id'];
    $user_type = $serial['data']['relationships']['user']['data']['type'];

    $this->assertArrayHasKey('links', $serial['data']);
    $this->assertArrayHasKey('meta', $serial);

    $includes      = $serial['included'];
    $user_in_array = false;
    foreach ($includes as $_incl) {
      if ($_incl['id'] === $user_id && $_incl['type'] === $user_type) {
        $user_in_array = true;
        break;
      }
    }

    $this->assertTrue($user_in_array);
  }

  public function testSerializeCollectionCMbObject() {
    $log      = $this->getUserLog();
    $resource = new CCollection([$log, $log]);
    $resource->setModelRelations(CRequestRelations::QUERY_KEYWORD_ALL);
    $resource->setSerializer(CJsonApiSerializer::class);

    $serial = $resource->serialize();

    $users = [];
    foreach ($serial['data'] as $_data) {
      $this->assertEquals(CUserLog::RESOURCE_NAME, $_data['type']);
      $this->assertArrayHasKey('attributes', $_data);
      $this->assertArrayHasKey('relationships', $_data);
      $this->assertArrayHasKey('links', $_data);

      $users[] = [
        'id'   => $_data['relationships']['user']['data']['id'],
        'type' => $_data['relationships']['user']['data']['type']
      ];
    }

    $this->assertEquals(2, $serial['meta']['count']);
    $this->assertEquals($users[0], $users[1]);

    $this->assertCount(1, $serial['included']);
    $this->assertEquals($users[0]['id'], $serial['included'][0]['id']);
    $this->assertEquals($users[0]['type'], $serial['included'][0]['type']);
  }

  private function getUserLog() {
    $current_user_id = CUser::get()->_id;

    $log               = new CUserLog();
    $log->user_log_id  = 1;
    $log->_id          = 1;
    $log->type         = 'store';
    $log->user_id      = $current_user_id;
    $log->object_class = 'CUser';
    $log->object_id    = $current_user_id;

    return $log;
  }
}