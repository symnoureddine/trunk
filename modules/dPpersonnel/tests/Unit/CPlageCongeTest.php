<?php

/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel\Tests\Unit;

use Exception;
use Ox\Core\CMbDT;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

//class CPlageCongeTest extends UnitTestMediboard
//{
//
//    public function setUp(): void
//    {
//        $this->markTestSkipped();
//    }
//
//    /**
//     * Create plage conge object
//     *
//     * @return CPlageConge
//     * @throws TestsException
//     */
//    public function testCreatePlageConge(): CPlageConge
//    {
//        $plage_conge = $this->getRandomObjects("CPlageConge", 1);
//
//        $this->assertNotNull($plage_conge->_id);
//
//        return $plage_conge;
//    }
//
//    /**
//     * Create many plage conge objects
//     *
//     * @param int $number
//     *
//     * @return array
//     * @throws TestsException
//     */
//    public function testCreateManyPlageConges(int $number = 5): array
//    {
//        $plage_conges = $this->getRandomObjects("CPlageConge", $number);
//
//        foreach ($plage_conges as $_plage) {
//            $this->assertNotNull($_plage->_guid);
//        }
//
//        return $plage_conges;
//    }
//
//    /**
//     * Test of the reference plage conge load
//     *
//     * @param array $plage_conges
//     *
//     * @depends testCreateManyPlageConges
//     * @throws Exception
//     */
//    public function testLoadListForRange(array $plage_conges): void
//    {
//        $user_id     = 0;
//        $time_moment = ["day", "week", "month", "year"];
//
//        foreach ($plage_conges as $_plage) {
//            $user_id = $_plage->user_id;
//        }
//
//        $number_min = random_int(0, 10);
//        $period_min = $time_moment[array_rand($time_moment)];
//        $number_max = random_int(0, 10);
//        $period_max = $time_moment[array_rand($time_moment)];
//
//        $min         = CMbDT::dateTime("-$number_min $period_min");
//        $max         = CMbDT::dateTime("+$number_max $period_max");
//        $plage_conge = new CPlageConge();
//
//        $plages        = $plage_conge->loadListForRange($user_id, $min, $max);
//        $number_plages = count($plages);
//
//        $this->assertIsArray($plages);
//
//        if ($number_plages) {
//            $this->assertGreaterThan(0, $number_plages);
//        } else {
//            $this->assertEquals(0, $number_plages);
//        }
//    }
//
//    /**
//     * Test of the reference user load
//     *
//     * @param CPlageConge $plage_conge
//     *
//     * @depends testCreatePlageConge
//     * @throws Exception
//     */
//    public function testLoadRefUser(CPlageConge $plage_conge): void
//    {
//        $user = $plage_conge->loadRefUser();
//
//        $this->assertNotNull($user->_id);
//        $this->assertEquals($plage_conge->user_id, $user->_id);
//    }
//
//    /**
//     * Test to make  a pseudo plage
//     */
//    public function testMakePseudoPlage(): void
//    {
//        $plage = CPlageConge::makePseudoPlage(CMediusers::get()->_id, "deb", CMbDT::date());
//
//        $this->assertIsObject($plage);
//        $this->assertObjectHasAttribute("_activite", $plage);
//        $this->assertNotEmpty($plage->_activite);
//    }
//
//    /**
//     * Test to update form field
//     *
//     * @param CPlageConge $plage_conge
//     *
//     * @depends testCreatePlageConge
//     */
//    public function testUpdateFormFields(CPlageConge $plage_conge): void
//    {
//        $plage_conge->updateFormFields();
//
//        $this->assertSame($plage_conge->libelle, $plage_conge->_shortview);
//        $this->assertSame($plage_conge->libelle, $plage_conge->_view);
//        $this->assertEquals($plage_conge->_shortview, $plage_conge->_view);
//    }
//
//    /**
//     * Test to check
//     *
//     * @param array $plage_conges
//     *
//     * @depends testCreateManyPlageConges
//     */
//    public function testCheck(array $plage_conges): void
//    {
//        foreach ($plage_conges as $_plage) {
//            $msg = $_plage->check();
//            $this->assertIsString($msg);
//            $this->assertNotNull($msg);
//        }
//    }
//
//    /**
//     * Test to get perm on an object
//     *
//     * @param array $plage_conges
//     *
//     * @depends testCreateManyPlageConges
//     */
//    public function testGetPerm(array $plage_conges): void
//    {
//        foreach ($plage_conges as $_plage) {
//            $this->assertIsBool($_plage->getPerm("PERM_READ"));
//            $this->assertFalse($_plage->getPerm("PERM_READ"));
//        }
//    }
//
//    /**
//     * Test of the reference replacer load
//     *
//     * @param CPlageConge $plage_conge
//     *
//     * @depends testCreatePlageConge
//     * @throws Exception
//     */
//    public function testLoadRefReplacer(CPlageConge $plage_conge): void
//    {
//        $replacer = $plage_conge->loadRefReplacer();
//
//        if ($replacer->_id) {
//            $this->assertNotEmpty($replacer->_id);
//            $this->assertEquals($plage_conge->replacer_id, $replacer->_id);
//        } else {
//            $this->assertNull($replacer->_id);
//        }
//    }
//
//    /**
//     * Test to load for an user
//     *
//     * @throws TestsException
//     */
//    public function testLoadFor(): void
//    {
//        $this->testCreateManyPlageConges();
//
//        $users = $this->getRandomObjects("CMediusers", 3);
//
//        $user_id = array_rand($users, 1);
//
//        $plage_conge = new CPlageConge();
//        $plage_conge->loadFor($user_id, CMbDT::date());
//
//        if ($plage_conge->_id) {
//            $this->assertNotNull($plage_conge->_id);
//            $this->assertSame($user_id, $plage_conge->user_id);
//        } else {
//            $this->assertNull($plage_conge->_id);
//        }
//    }
//
//    /**
//     * Test to load by many user ids
//     */
//    public function testLoadForIdsForDate(): void
//    {
//        $this->testCreateManyPlageConges(15);
//
//        $users = $this->getRandomObjects("CMediusers", 3);
//
//        $user_ids = array_keys($users);
//
//        $this->assertIsArray($user_ids);
//
//        $plage_conge   = new CPlageConge();
//        $plages        = $plage_conge->loadForIdsForDate($user_ids, CMbDT::date());
//        $number_plages = count($plages);
//
//        if ($number_plages) {
//            $this->assertGreaterThan(0, $number_plages);
//        } else {
//            $this->assertEquals(0, $number_plages);
//        }
//    }
//
//    /**
//     * Test to load by user_id
//     */
//    public function testLoadRefsReplacementsFor(): void
//    {
//        $users = $this->getRandomObjects("CMediusers", 3);
//
//        $user_id = array_rand($users, 1);
//
//        $this->assertIsInt($user_id);
//
//        /** @var CPlageConge $plage_conge */
//        $plage_conge   = $this->getRandomObjects("CPlageConge", 1);
//        $plages        = $plage_conge->loadRefsReplacementsFor($user_id, CMbDT::date());
//        $number_plages = count($plages);
//
//        if ($number_plages) {
//            $this->assertGreaterThan(0, $number_plages);
//        } else {
//            $this->assertEquals(0, $number_plages);
//        }
//    }
//}
