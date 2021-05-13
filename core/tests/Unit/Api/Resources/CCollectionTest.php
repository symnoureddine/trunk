<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Resources;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Tests\Resources\CLoremIpsum;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\Rgpd\CRGPDConsent;
use Ox\Tests\UnitTestMediboard;
use TypeError;

class CCollectionTest extends UnitTestMediboard {

  public function testFailedConstructNotArray(): void {
    $this->expectException(TypeError::class);
    $collection = new CCollection('lorem ipsum');
  }

  public function testConstructFromArray(): CCollection {
    $datas      = [
      ['lorem' => 'ipsum'],
      ['foo' => 'bar'],
    ];
    $collection = new CCollection($datas);
    $this->assertEquals($collection->getDatas(), $datas);
    $items = [];
    foreach ($datas as $data) {
      $items[] = new CItem($data);
    }
    $this->assertEquals($collection->getItems(), $items);

    return $collection;
  }


  public function testConstructFromObject(): void {
    $datas      = [
      new CLoremIpsum(123, 'foo', 'testConstructFromObject'),
      new CLoremIpsum(123, 'foo', 'testConstructFromObject')
    ];
    $collection = new CCollection($datas);
    $this->assertEquals($collection->getDatas(), $datas);
  }

  public function testConstructFromCModelObject(): CCollection {
    $users      = $this->getRandomObjects(CUser::class, 2);
    $collection = new CCollection($users);
    $this->assertEquals($collection->getDatas(), $users);

    return $collection;
  }


  private function createCModelObjectCollection(): CCollection {
    $users = $this->getRandomObjects(CUser::class, 5);

    return new CCollection($users);
  }


  public function testCreateLinksPagination(): void {
    $collection = $this->createCModelObjectCollection();
    $links      = [
      "self"  => "?offset=10&limit=100",
      "next"  => "?limit=100&offset=110",
      "first" => "?limit=100&offset=0",
      "last"  => "?limit=100&offset=1000"
    ];
    $collection->createLinksPagination(10, 100, 1000);
    $this->assertEquals($links, $collection->getLinks());
  }

  public function testPropageSettings(): void {
    $collection = $this->createCModelObjectCollection();
    $collection->setName('lorem');
    $collection->setModelFieldsets('none');
    $collection->setModelRelations('none');

    foreach ($collection as $item) {
      $this->invokePrivateMethod($collection, 'propageSettings', $item);
      $this->assertEquals($item->getName(), 'lorem');
      $this->assertEmpty($item->getModelFieldsets());
      $this->assertEmpty($item->getModelRelations());
    }
  }

  public function testPropageSettingsFieldsetsInDeep() {
    $collection = $this->createCModelObjectCollection();
    $collection->setName('lorem');
    $collection->setModelFieldsets(
      [
        CUser::FIELDSET_DEFAULT, CUser::FIELDSET_EXTRA, CUser::RELATION_RGPD.'.'.CRGPDConsent::FIELDSET_DEFAULT,
        CUser::RELATION_RGPD.'.'.CRGPDConsent::FIELDSET_EXTRA
      ]
    );
    $collection->setModelRelations('none');

    foreach ($collection as $item) {
      $this->invokePrivateMethod($collection, 'propageSettings', $item);
      $this->assertEquals($item->getName(), 'lorem');
      $this->assertEquals($collection->getModelFieldsets(), $item->getModelFieldsets());
      $this->assertEmpty($item->getModelRelations());
    }
  }

  public function testIsIterable() {
    $collection = $this->createCModelObjectCollection();
    $items      = $collection->getItems();
    $this->isIterable($collection, $items, count($items));
  }

  public function testIsCountable() {
    $collection = $this->createCModelObjectCollection();
    $this->isCountable($collection, count($collection->getItems()));
  }

  public function testTransform() {
    $collection        = $this->createCModelObjectCollection();
    $datas_transformed = $collection->transform();

    $this->assertIsArray($datas_transformed);
    $this->assertCount(5, $datas_transformed);
    foreach ($datas_transformed as $datas) {

      $this->assertArrayHasKey('_type', $datas['datas']);
      $this->assertArrayHasKey('_id', $datas['datas']);
    }
  }

  public function testMetas() {
    $collection = $this->createCModelObjectCollection();
    $this->invokePrivateMethod($collection, 'setDefaultMetas');
    $metas = $collection->getMetas();
    $this->assertArrayHasKey('count', $metas);
    $this->assertEquals($metas['count'], 5);
  }

}