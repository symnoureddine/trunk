<?php

/**
 * @package Mediboard\Patient\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Tests\Unit;

use DateTimeImmutable;
use Exception;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbModelNotFoundException;
use Ox\Core\CModelObject;
use Ox\Mediboard\Patients\CPatient;
use Ox\Tests\UnitTestMediboard;

class CPatientTest extends UnitTestMediboard
{
    /** @var array $patients_example */
    protected static $patients_example = [];

    /**
     * Load a couple patient examples
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $patient                = new CPatient();
        self::$patients_example = array_values($patient->loadList(null, null, 10));
    }

    /**
     * Tests if it count the amount of ids in a specific array
     * @throws CMbException
     */
    public function testCountBloodRelatives(): void
    {
        $total = [
            "bros"     => [[101, 'biologique'], [102, 'civil']],
            "children" => [],
            "parent_1" => [200, 'biologique'],
            "parent_2" => 0,
        ];

        $this->assertEquals(3, CPatient::countBloodRelatives($total));
    }

    /**
     * Tests if it can transform Ids from a specific array to objects
     * @throws CMbModelNotFoundException
     */
    public function testTransformRelativesPatient(): void
    {
        $ex = &self::$patients_example;

        $total = [
            "bros"     => [[$ex[0]->_id, 'biologique'], [$ex[1]->_id, 'civil']],
            "children" => [[$ex[2]->_id, 'biologique']],
            "parent_1" => null,
            "parent_2" => [$ex[3]->_id, 'biologique'],
        ];

        $p1 = CPatient::find($ex[0]->_id);
        $p2 = CPatient::find($ex[1]->_id);
        $p3 = CPatient::find($ex[2]->_id);
        $p4 = CPatient::find($ex[3]->_id);


        $expected = [
            "bros"     => [[$p1, 'biologique'], [$p2, 'civil']],
            "children" => [[$p3, 'biologique']],
            "parent_1" => null,
            "parent_2" => [$p4, 'biologique'],
        ];

        $this->assertEquals($expected, CPatient::transformRelativesPatient($total));
    }

    /**
     * @param string $cp CP to test
     *
     * @config       dPpatients INSEE france 1
     * @config       dPpatients INSEE suisse 1
     * @config       dPpatients INSEE allemagne 1
     * @config       dPpatients INSEE espagne 1
     * @config       dPpatients INSEE portugal 1
     * @config       dPpatients INSEE gb 1
     *
     * @dataProvider cpProvider
     */
    public function testCPSize(string $cp): void
    {
        $cp_fields = ['cp', 'cp_naissance', 'assure_cp', 'assure_cp_naissance'];

        // Ugly way to empty the props cache due to change in configurations used in props
        CModelObject::$spec['CPatient'] = null;

        $patient = new CPatient();
        foreach ($cp_fields as $_cp) {
            $patient->{$_cp} = $cp;
        }
        $patient->repair();

        foreach ($cp_fields as $_cp) {
            $this->assertEquals($cp, $patient->{$_cp});
        }
    }

    /**
     * @return array
     */
    public function cpProvider()
    {
        return [
            ["3750-012"],
            ["12"],
            ["17000"],
            ["6534887"],
        ];
    }

    /**
     * Tests the rest age function
     *
     * @throws Exception
     */
    public function testGetRestAge(): void
    {
        $patient            = new CPatient();
        $patient->naissance = "2019-09-10";

        // < 1 year
        $value    = $patient->getRestAge(DateTimeImmutable::createFromFormat("Y-m-d", "2019-10-15"));
        $expected = ["rest_months" => 1, "rest_weeks" => 0, "rest_days" => 5, "locale" => "1 month"];
        $this->assertEquals($expected, $value);

        // < 2 year
        $value    = $patient->getRestAge(DateTimeImmutable::createFromFormat("Y-m-d", "2021-05-15"));
        $expected = ["rest_months" => 20, "rest_weeks" => 0, "rest_days" => 5, "locale" => "20 months"];
        $this->assertEquals($expected, $value);

        // < 3 year
        $value    = $patient->getRestAge(DateTimeImmutable::createFromFormat("Y-m-d", "2025-02-03"));
        $expected = ["rest_months" => 4, "rest_weeks" => 3, "rest_days" => 3, "locale" => "5 years, 4 months"];
        $this->assertEquals($expected, $value);
    }

    /**
     * Test to verify that the date of birth is not greater than the current year.
     *
     * @throws Exception
     */
    public function testCheckBirthdate(): void
    {
        /** @var CPatient $patient */
        $patient            = $this->getRandomObjects(CPatient::class);
        $patient->naissance = "2002-09-10";
        $msg = $patient->store();

        // Ok
        $this->assertNull($msg);

        // Ok
        $patient->naissance = "2020-09-10";
        $msg = $patient->store();
        $this->assertNull($msg);

        // Not ok
        $patient->naissance = CMbDT::date('+2 years', $patient->naissance);
        $msg = $patient->store();
        $this->assertNotNull($msg);
    }
}
