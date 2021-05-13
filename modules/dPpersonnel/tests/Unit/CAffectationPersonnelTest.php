<?php

/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel\Tests\Unit;

use Ox\Core\CMbDT;
use Ox\Mediboard\Personnel\CAffectationPersonnel;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

class CAffectationPersonnelTest extends UnitTestMediboard
{
    /**
     * Create affectation personnel object
     *
     * @return CAffectationPersonnel
     * @throws TestsException
     */
    public function testCreateAffectationPersonnel(): CAffectationPersonnel
    {
        $affectation_personnel = $this->getRandomObjects("CAffectationPersonnel", 1);

        $this->assertNotNull($affectation_personnel->_id);

        return $affectation_personnel;
    }

    /**
     * Create many affectation personnel objects
     *
     * @return array
     * @throws TestsException
     */
    public function testCreateManyAffectationPersonnels(): array
    {
        $affectation_personnels = $this->getRandomObjects("CAffectationPersonnel", 5);

        foreach ($affectation_personnels as $_affectation) {
            $this->assertNotNull($_affectation->_guid);
        }

        return $affectation_personnels;
    }

    /**
     * Test of the reference personnel load
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testLoadRefPersonnel(CAffectationPersonnel $affectation_personnel): void
    {
        $personnel = $affectation_personnel->loadRefPersonnel();

        $this->assertEquals($affectation_personnel->personnel_id, $personnel->_id);
    }

    /**
     * Test of the reference object load
     *
     * @param array $affectation_personnels
     *
     * @depends testCreateManyAffectationPersonnels
     */
    public function testLoadRefObject(array $affectation_personnels): void
    {
        foreach ($affectation_personnels as $_affectation) {
            $object  = $_affectation->loadRefObject();
            $objects = explode("|", $_affectation->_props['object_class']);

            // unset enum list
            unset($objects[0]);

            $this->assertContains($object->_class, $objects, "testArray doesn't contains value as value");
        }
    }

    /**
     * Test of Forward references global loader
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testLoadRefsFwd(CAffectationPersonnel $affectation_personnel): void
    {
        $affectation_personnel->loadRefsFwd();

        $this->assertEquals($affectation_personnel->personnel_id, $affectation_personnel->_ref_personnel->_id);
    }

    /**
     * Test to find assignments with target and identical staff
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testGetSiblings(CAffectationPersonnel $affectation_personnel): void
    {
        $siblings = $affectation_personnel->getSiblings();

        $this->assertIsArray($siblings);
        $this->assertCount(0, $siblings);
    }

    /**
     * Test to check
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testCheck(CAffectationPersonnel $affectation_personnel): void
    {
        $msg = $affectation_personnel->check();

        $this->assertIsString($msg);
    }

    /**
     * Test to update form field
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testUpdateFormFields(CAffectationPersonnel $affectation_personnel): void
    {
        $affectation_personnel->updateFormFields();
        $debut_time = CMbDT::time($affectation_personnel->_debut_dt);
        $fin_time   = CMbDT::time($affectation_personnel->_fin_dt);

        $this->assertStringContainsString($debut_time, $affectation_personnel->_debut);
        $this->assertStringContainsString($fin_time, $affectation_personnel->_fin);
    }

    /**
     * Test to update plain fields
     *
     * @param CAffectationPersonnel $affectation_personnel
     *
     * @depends testCreateAffectationPersonnel
     */
    public function testUpdatePlainFields(CAffectationPersonnel $affectation_personnel): void
    {
        $affectation_personnel->updatePlainFields();

        if (in_array($affectation_personnel->object_class, ["COperation", "CBloodSalvage"])) {
            if ($affectation_personnel->debut) {
                $this->assertNotNull($affectation_personnel->debut);
                $this->assertNotNull($affectation_personnel->fin);
                $this->assertEquals(1, $affectation_personnel->realise);
            } else {
                $this->assertNull($affectation_personnel->debut);
                $this->assertNull($affectation_personnel->fin);
                $this->assertEquals(0, $affectation_personnel->realise);
            }
        } else {
            $this->assertEquals("CPlageOp", $affectation_personnel->object_class);
        }
    }
}
