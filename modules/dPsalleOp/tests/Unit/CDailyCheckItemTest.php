<?php

namespace Ox\Mediboard\SalleOp;

use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;
use PHPUnit\Framework\TestCase;

class CDailyCheckItemTest extends UnitTestMediboard {

  /**
   * Test du constructeur
   */
  public function test__construct() {
    $this->assertInstanceOf(CDailyCheckItem::class, new CDailyCheckItem());
  }

  /**
   * Create item checklist
   *
   * @return CDailyCheckItem
   * @throws TestsException
   */
  public function testCreateDailyCheckItem() {
    $item = $this->getRandomObjects("CDailyCheckItem", 1);

    $this->assertNotNull($item->_id);

    return $item;
  }

  /**
   * Test of Forward references global loader
   *
   * @param CDailyCheckItem $item
   *
   * @depends testCreateDailyCheckItem
   */
  public function testLoadRefsFwd(CDailyCheckItem $item) {
    $item->loadRefsFwd();

    $this->assertEquals($item->list_id, $item->_ref_list->_id);
    $this->assertEquals($item->item_type_id, $item->_ref_item_type->_id);
  }

  /**
   * Test to update form field
   *
   * @param CDailyCheckItem $item
   *
   * @depends testCreateDailyCheckItem
   */
  public function testUpdateFormFields(CDailyCheckItem $item) {
    $item->updateFormFields();
    $this->assertEquals($item->_view, "$item->_ref_item_type (".$item->getAnswer().")");
  }

  /**
   * Test du chargement de la Vérification
   *
   * @param CDailyCheckItem $item
   *
   * @depends testCreateDailyCheckItem
   */
  public function testLoadRefItemType(CDailyCheckItem $item) {
    $this->assertInstanceOf(CDailyCheckItemType::class, $item->loadRefItemType());
  }

  /**
   * Test du chargement de la Liste de vérification
   *
   * @param CDailyCheckItem $item
   *
   * @depends testCreateDailyCheckItem
   */
  public function testLoadRefList(CDailyCheckItem $item) {
    $this->assertInstanceOf(CDailyCheckList::class, $item->loadRefList());
  }

  /**
   * Test du formatage de la réponse
   *
   * @param CDailyCheckItem $item
   *
   * @depends testCreateDailyCheckItem
   */
  public function testGetAnswer(CDailyCheckItem $item) {
    $this->assertEquals("CDailyCheckItem.checked.".$item->checked, $item->getAnswer());
  }
}
