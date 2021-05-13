<?php

/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes\Tests\Unit;

use Ox\Mediboard\Astreintes\CCategorieAstreinte;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

//class CCategorieAstreinteTest extends UnitTestMediboard
//{
//    public function setUp(): void
//    {
//        $this->markTestSkipped();
//    }
//
//    /**
//     * Create categorie astreinte object
//     *
//     * @return array
//     * @throws TestsException
//     */
//    public function testCreateCategorieAstreinte(): array
//    {
//        $categories_astreinte = $this->getRandomObjects("CCategorieAstreinte", 2);
//
//        $this->assertNotNull($categories_astreinte);
//
//        return $categories_astreinte;
//    }
//
//    /**
//     * Test to get the array of the categories names
//     *
//     * @param array $categories_astreinte
//     *
//     * @depends testCreateCategorieAstreinte
//     */
//    public function testGetPrefCategories(array $categories_astreinte): void
//    {
//        $cat_names = CCategorieAstreinte::getPrefCategories();
//
//        $this->assertNotNull($cat_names);
//        $this->assertIsArray($cat_names);
//
//        foreach ($categories_astreinte as $_categorie) {
//            if (!in_array($_categorie->_id, array_keys($cat_names))) {
//                continue;
//            }
//
//            $this->assertEquals($cat_names[$_categorie->_id], $_categorie->name);
//        }
//
//        $this->assertGreaterThanOrEqual(count($categories_astreinte), count($cat_names));
//    }
//
//    /**
//     * Test to get the categorie name
//     *
//     * @param array $categories_astreinte
//     *
//     * @depends testCreateCategorieAstreinte
//     */
//    public function testGetName(array $categories_astreinte): void
//    {
//        foreach ($categories_astreinte as $_categorie) {
//            $name = CCategorieAstreinte::getName($_categorie->_id);
//
//            $this->assertEquals($name, $_categorie->name);
//            $this->assertIsString($name);
//        }
//    }
//}
