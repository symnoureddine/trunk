<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Resources;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Api\Serializers\CJsonApiSerializer;
use Ox\Core\Tests\Resources\CLoremIpsum;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\Rgpd\CRGPDConsent;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\System\CUserLog;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

class CItemTest extends UnitTestMediboard
{

    public function testFailedConstructFromString(): void
    {
        $this->expectException(CApiException::class);
        $item = new CItem('lorem ipsum');
    }

    public function testFailedConstructFromInt(): void
    {
        $this->expectException(CApiException::class);
        $item = new CItem(1234);
    }

    public function testFailedConstructFromNull(): void
    {
        $this->expectException(CApiException::class);
        $item = new CItem(null);
    }

    public function testFailedConstructFromBool(): void
    {
        $this->expectException(CApiException::class);
        $item = new CItem(true);
    }

    public function testConstructFromArray(): void
    {
        $datas = [
            'lorem' => 'ipsum',
            'foo'   => 'bar',
            'toto'  => 'tata',
        ];
        $item  = new CItem($datas);
        $this->assertEquals($item->getDatas(), $datas);
    }

    public function testConstructFromObject(): void
    {
        $lorem = new CLoremIpsum(123, 'foo', 'testConstructFromObject');
        $item  = new CItem($lorem);
        $this->assertEquals($item->getDatas(), $lorem);
    }

    public function testConstructFromCModelObject(): void
    {
        $user = $this->getRandomObjects(CUser::class);
        $item = new CItem($user);
        $this->assertEquals($item->getDatas(), $user);
    }

    public function testMetas()
    {
        $user = $this->getRandomObjects(CUser::class);
        $item = new CItem($user);
        $this->invokePrivateMethod($item, 'setDefaultMetas');
        $metas = $item->getMetas();
        $this->assertIsArray($metas);
    }

    public function testAdditionalDatas()
    {
        $user  = $this->getRandomObjects(CUser::class);
        $item  = new CItem($user);
        $datas = $item->addAdditionalDatas(
            [
                'foo' => 'bar',
            ]
        )->transform();

        $this->assertArrayHasKey('foo', $datas['datas']);
        $this->assertEquals($datas['datas']['foo'], 'bar');
    }


    public function testFieldsets()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $item->setModelFieldsets('none');
        $this->assertEquals([], $item->getModelFieldsets());
    }

    public function testFieldsetsFailed()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $this->expectException(CApiException::class);
        $item->setModelFieldsets('lorem');
    }

    public function testFieldsetsFailedNotModelObject()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $this->expectException(CApiException::class);
        $item->setModelFieldsets('all');
    }

    public function testRelations()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $item->setModelRelations('all');
        $this->assertNotEmpty($item->getModelRelations());
    }

    public function testRelationsFailed()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $this->expectException(CApiException::class);
        $item->setModelRelations('lorem');
    }

    public function testRelationsFailedNotModelObject()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $this->expectException(CApiException::class);
        $item->setModelRelations('all');
    }

    public function testIsModelObject()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $this->assertTrue($item->isModelObjectResource());
    }

    public function testIsModelObjectFailed()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $this->assertFalse($item->isModelObjectResource());
    }

    public function testRequestUrl()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $item->setRequestUrl('http://www.lorem.ipsum');
        $this->assertEquals('http://www.lorem.ipsum', $item->getRequestUrl());
    }

    public function testRecursionDepth()
    {
        $item = new CItem($this->getRandomObjects(CSejour::class));
        $this->assertEquals($item->getRecursionDepth(), 0);
    }

    public function testFormat()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $item->setFormat(CRequestFormats::FORMAT_JSON);
        $this->assertEquals(CRequestFormats::FORMAT_JSON, $item->getFormat());
    }

    public function testFormatFailed()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $this->expectException(CApiException::class);
        $item->setFormat('toto_tata');
    }

    public function testSerializer()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $item->setSerializer(CJsonApiSerializer::class);
        $this->assertEquals(CJsonApiSerializer::class, $item->getSerializer());
    }

    public function testSerializerFailed()
    {
        $item = new CItem(['lorem' => 'ipsum']);
        $this->expectException(CApiException::class);
        $item->setSerializer('joe/bar/team');
    }

    public function testJsonSerializable()
    {
        $datas = ['lorem' => 'ipsum', 'id' => "1234"];
        $item  = new CItem($datas);
        $this->isJsonSerializable($item);
    }

    public function testCreateFromRequest()
    {
        $request     = Request::create('http://www.phpunit?relations=user&fieldsets=none');
        $request_api = new CRequestApi($request);

        $log      = new CUserLog();
        $log->_id = 1234;
        $item     = CItem::createFromRequest($request_api, $log);

        $this->assertEquals($item->getModelRelations(), ['user']);
        $this->assertEquals($item->getModelFieldsets(), []);
        $this->assertNotNull($item->getRequestUrl());
    }

    public function testFormatFieldsetsByRelations()
    {
        $fieldsets = ['foo', 'bar', 'patient.foo', 'patient.bar'];
        $expected  = [
            CItem::CURRENT_RESOURCE_NAME => ['foo', 'bar'],
            'patient'                    => ['foo', 'bar'],
        ];

        $item   = new CItem(new CUser());
        $actual = $this->invokePrivateMethod($item, 'formatFieldsetByRelation', $fieldsets);

        $this->assertEquals($expected, $actual);
    }

    public function testAddModelFieldsets()
    {
        $fieldsets = [CUser::FIELDSET_DEFAULT, CUser::FIELDSET_EXTRA, 'patient.foo', 'patient.bar'];
        $expected  = [
            CItem::CURRENT_RESOURCE_NAME => [CUser::FIELDSET_DEFAULT, CUser::FIELDSET_EXTRA],
            'patient'                    => ['foo', 'bar'],
        ];

        $item = new CItem(new CUser());
        $item->addModelFieldset($fieldsets);
        $actual = $item->getModelFieldsets();

        $this->assertEquals($expected, $actual);
    }

    public function testAddModelFieldsetException()
    {
        $fieldsets = ['foo', 'bar', 'patient.foo', 'patient.bar'];

        $item = new CItem(new CUser());
        $this->expectException(CAPIException::class);
        $item->addModelFieldset($fieldsets);
    }

    public function testAddModelFieldsetDeep()
    {
        $fieldsets = [
            CUser::FIELDSET_DEFAULT,
            CUser::FIELDSET_EXTRA,
            CUser::RELATION_RGPD . '.' . CRGPDConsent::FIELDSET_DEFAULT,
            CUser::RELATION_RGPD . '.' . CRGPDConsent::FIELDSET_EXTRA,
        ];

        $item = new CItem(new CUser());
        $item->addModelFieldset($fieldsets);
        $actual = $item->serialize();
        $this->assertNotEmpty($actual);
    }


    public function testRemoveFieldsets()
    {
        $fieldsets = [CUser::FIELDSET_DEFAULT, CUser::FIELDSET_EXTRA, 'foo.bar', 'bar.foo'];
        $expected  = [CItem::CURRENT_RESOURCE_NAME => [CUser::FIELDSET_EXTRA], 'bar' => ['foo'], 'foo' => []];

        $item = new CItem(new CUser());
        $item->addModelFieldset($fieldsets);

        $actual = $item->removeModelFieldset([CUser::FIELDSET_DEFAULT, 'foo.bar']);
        $this->assertTrue($actual);
        $this->assertEquals($expected, $item->getModelFieldsets());
    }

    public function testRemoveFieldsetsFailed()
    {
        $fieldsets = [CUser::FIELDSET_DEFAULT, CUser::FIELDSET_EXTRA, 'foo.bar', 'bar.foo'];

        $item = new CItem(new CUser());
        $item->addModelFieldset($fieldsets);

        $actual = $item->removeModelFieldset(['foo']);
        $this->assertFalse($actual);
    }

    public function testHasModelRelation()
    {
        $relations = [CUser::RELATION_RGPD];

        $item = new CItem(new CUser());
        $item->setModelRelations($relations);

        $this->assertTrue($item->hasModelrelation(CUser::RELATION_RGPD));
        $this->assertFalse($item->hasModelrelation('foo'));
    }

    public function testHasModelFieldsets()
    {
        $fieldsets = [CUser::FIELDSET_DEFAULT, CUser::RELATION_RGPD . '.' . CRGPDConsent::FIELDSET_EXTRA];

        $item = new CItem(new CUser());
        $item->setModelFieldsets($fieldsets);

        $this->assertTrue($item->hasModelFieldset(CUser::FIELDSET_DEFAULT));
        $this->assertTrue($item->hasModelFieldset(CUser::RELATION_RGPD . '.' . CRGPDConsent::FIELDSET_EXTRA));
        $this->assertFalse($item->hasModelFieldset('foo'));
        $this->assertFalse($item->hasModelFieldset(CUser::RELATION_RGPD . '.foo'));
    }

    public function testgetFieldsetsByRelation()
    {
        $fieldsets = [CUser::FIELDSET_DEFAULT, CUser::RELATION_RGPD . '.' . CRGPDConsent::FIELDSET_EXTRA];

        $item = new CItem(new CUser());
        $item->setModelFieldsets($fieldsets);

        $expected = [CRGPDConsent::FIELDSET_EXTRA];
        $actual   = $item->getFieldsetsByRelation(CUser::RELATION_RGPD);
        $this->assertEquals($expected, $actual);

        $expected = [CUser::FIELDSET_DEFAULT];
        $actual   = $item->getFieldsetsByRelation();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws CApiException
     */
    public function testAdddFieldsetOnRelation(): void
    {
        $expected  = null;
        $fieldsets = [CRGPDConsent::FIELDSET_DEFAULT, CRGPDConsent::FIELDSET_EXTRA, CRGPDConsent::RELATION_FILES];
        $item      = new CItem(new CUser());

        // nothing do if relation is not set
        $item->addFieldsetsOnRelation(CUSer::RELATION_RGPD, $fieldsets);
        $this->assertEquals($expected, $item->getFieldsetsByRelation(CUSer::RELATION_RGPD));

        $expected = $fieldsets;
        $item->setModelRelations(CUSer::RELATION_RGPD);
        $item->addFieldsetsOnRelation(CUSer::RELATION_RGPD, $fieldsets);
        $this->assertEquals($expected, $item->getFieldsetsByRelation(CUSer::RELATION_RGPD));
    }

    public function testSetEtag(): void
    {
        $item = new CItem(new CUser());
        $this->assertFalse($item->isEtaggable());

        $item->setEtag(CEtag::TYPE_LOCALES);
        $this->assertTrue($item->isEtaggable());
    }
}
