<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Resources;

use Ox\Core\Api\Resources\CAbstractResource;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Api\Transformers\CAbstractTransformer;
use Ox\Core\Api\Transformers\CArrayTransformer;
use Ox\Core\Api\Transformers\CModelObjectTransformer;
use Ox\Core\Api\Transformers\CObjectTransformer;
use Ox\Core\Tests\Resources\CLoremIpsum;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CUserLog;
use Ox\Tests\UnitTestMediboard;

class CTransformersTest extends UnitTestMediboard {

  /**
   * @dataProvider provideResources
   */
  public function testConstructTransformers(CAbstractResource $resource, $transformer_class) {
    $transformer = $this->invokePrivateMethod($resource, 'createTransformer');
    $this->assertInstanceOf($transformer_class, $transformer);
  }


  /**
   * @dataProvider provideResources
   */
  public function testCreateDatas(CAbstractResource $resource, $transformer_class){
    /** @var CAbstractTransformer $transformer */
    $transformer = $this->invokePrivateMethod($resource, 'createTransformer');
    $datas = $transformer->createDatas();
    $this->assertIsArray($datas);
    $this->assertArrayHasKey('datas',$datas);
    $this->assertArrayHasKey('_type',$datas['datas']);
    $this->assertArrayHasKey('_id',$datas['datas']);
  }

  public function provideResources() {
    return [
      'resource_array'        => [
        new CItem(['foo' => 'bar']),
        CArrayTransformer::class
      ],
      'resource_object'       => [
        new CItem(new CLoremIpsum(123, 'toto', 'tata')),
        CObjectTransformer::class
      ],
      'resource_model_object' => [
        new CItem($this->getRandomObjects(CUser::class)),
        CModelObjectTransformer::class
      ]
    ];
  }

  public function testCreateId(){
    $item = new CItem(['foo' => 'bar']);
    $transfomer =  $this->invokePrivateMethod($item, 'createTransformer');
    $id = $this->invokePrivateMethod($transfomer, 'createIdFromData', 'loremipsum');
    $this->assertEquals($id, 'a1a9b039cffc4137f69c065b8978765b');
  }

}