<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Tests\Unit;

use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Import\Rpps\CMedecinExercicePlaceManager;
use Ox\Mediboard\Patients\CExercicePlace;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CMedecinExercicePlace;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CMedecinExercicePlaceManagerTest extends UnitTestMediboard
{
    /**
     * @config rpps disable_days_withtout_update 0
     */
    public function testRemoveOldMedecinExercicePlaceWithtoutConf(): void
    {
        $manager = new CMedecinExercicePlaceManager();
        $manager->removeOldMedecinExercicePlaces();
        $this->assertEmpty($manager->getInfos());
        $this->assertEmpty($manager->getErrors());
    }

    /**
     * @config rpps disable_days_withtout_update 3650
     */
    public function testRemoveOldMedecinExercicePlaceWithtoutPlaces(): void
    {
        $manager = new CMedecinExercicePlaceManager();
        $manager->removeOldMedecinExercicePlaces();
        $this->assertEmpty($manager->getInfos());
        $this->assertEmpty($manager->getErrors());
    }

    /**
     * @group schedules
     *
     * @config rpps disable_days_withtout_update 30
     * @config [CConfiguration] dPpatients CMedecin medecin_strict false
     */
    public function testRemoveOldMedecinExercicePlace(): void
    {
        $this->addOldMedecinExercicePlace(100);

        $initial_count = $this->countObjects('medecin_exercice_place');

        $manager = new CMedecinExercicePlaceManager();
        $manager->removeOldMedecinExercicePlaces(100);

        $this->assertEmpty($manager->getErrors());
        $this->assertNotEmpty($manager->getInfos());
        $this->assertEquals($initial_count - 100, $this->countObjects('medecin_exercice_place'));
    }

    /**
     * @group schedules
     */
    public function testDisableMedecinsWithoutExercicePlace(): void
    {
        $this->createMedecins(100);

        $initial_count = $this->countObjects('medecin', ['actif = "1"']);

        $manager = new CMedecinExercicePlaceManager();
        $manager->disableMedecinsWithoutExercicePlace(100);

        $this->assertEmpty($manager->getErrors());
        $this->assertNotEmpty($manager->getInfos());

        $this->assertEquals($initial_count - 100, $this->countObjects('medecin', ['actif = "1"']));
    }

    private function addOldMedecinExercicePlace(int $count): void
    {
        $places  = $this->getRandomObjects(CExercicePlace::class, $count);
        $medecin = $this->createMedecins(1)[0];

        foreach ($places as $_place) {
            $med_ex_place                    = new CMedecinExercicePlace();
            $med_ex_place->exercice_place_id = $_place->_id;
            $med_ex_place->medecin_id        = $medecin->_id;
            $med_ex_place->rpps_file_version = '1850-01-01';
            if ($msg = $med_ex_place->store()) {
                $this->fail($msg);
            }
        }
    }

    private function createMedecins(int $count): array
    {
        $meds = [];
        for ($i = 0; $i < $count; $i++) {
            $medecin      = new CMedecin();
            $medecin->nom = uniqid();
            if ($msg = $medecin->store()) {
                $this->fail($msg);
            }

            $meds[] = $medecin;
        }

        return $meds;
    }

    private function countObjects(string $table_name, array $where = []): int
    {
        $ds    = CSQLDataSource::get('std');
        $query = new CRequest();
        $query->addTable($table_name);
        $query->addWhere($where);

        return $ds->loadResult($query->makeSelectCount()) ?: 0;
    }
}
