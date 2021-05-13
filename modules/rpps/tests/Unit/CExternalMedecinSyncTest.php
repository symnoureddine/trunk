<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Tests\Unit;

use Ox\Core\CSQLDataSource;
use Ox\Import\Rpps\CExternalMedecinBulkImport;
use Ox\Import\Rpps\CExternalMedecinSync;
use Ox\Import\Rpps\Entity\CDiplomeAutorisationExercice;
use Ox\Import\Rpps\Entity\CPersonneExercice;
use Ox\Import\Rpps\Entity\CSavoirFaire;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CExternalMedecinSyncTest extends UnitTestMediboard
{
    public function setUp(): void
    {
        parent::setUp();

        $ds = CSQLDataSource::get(CExternalMedecinBulkImport::DSN, true);
        if (!$ds) {
            $this->markTestSkipped('Datasource RPPS is not available');
        }
    }

    public function testSynchronizeSomeMedecinsWithMutexAlreadyPuted()
    {
        $sync = new CExternalMedecinSync();
        $this->invokePrivateMethod($sync, 'putMutex');

        $this->expectExceptionMessage('Mutex is already in use');
        $sync->synchronizeSomeMedecins();

        $this->invokePrivateMethod($sync, 'releaseMutex');
    }

    public function testCountEmptyTables()
    {
        $import = new CExternalMedecinBulkImport();
        $import->createSchema();

        $ext_med = new CExternalMedecinSync();

        $this->assertEquals(
            [
                CPersonneExercice::class            => [
                    'sync'      => '0',
                    'not_sync'  => '0',
                    'total'     => '0',
                    'pct'       => '0,0000',
                    'threshold' => 'critical',
                    'width'     => '0',
                ],
                CSavoirFaire::class                 => [
                    'sync'      => '0',
                    'not_sync'  => '0',
                    'total'     => '0',
                    'pct'       => '0,0000',
                    'threshold' => 'critical',
                    'width'     => '0',
                ],
                CDiplomeAutorisationExercice::class => [
                    'sync'      => '0',
                    'not_sync'  => '0',
                    'total'     => '0',
                    'pct'       => '0,0000',
                    'threshold' => 'critical',
                    'width'     => '0',
                ],
            ],
            $ext_med->getAvancement()
        );
    }

    public function testCountAvancement()
    {
        $mock = $smarty = $this->getMockBuilder(CExternalMedecinSync::class)
            ->onlyMethods(['getCounts'])
            ->getMock();

        $mock->method('getCounts')->willReturn(
            $this->countAvancementCallBack(false),
            $this->countAvancementCallBack(true)
        );

        $this->assertEquals(
            [
                CPersonneExercice::class            => [
                    'sync'      => '55 000',
                    'not_sync'  => '100 000',
                    'total'     => '155 000',
                    'pct'       => '35,4839',
                    'threshold' => 'critical',
                    'width'     => '35',
                ],
                CSavoirFaire::class                 => [
                    'sync'      => '49 000',
                    'not_sync'  => '1 000',
                    'total'     => '50 000',
                    'pct'       => '98,0000',
                    'threshold' => 'ok',
                    'width'     => '98',
                ],
                CDiplomeAutorisationExercice::class => [
                    'sync'      => '40 000',
                    'not_sync'  => '20 000',
                    'total'     => '60 000',
                    'pct'       => '66,6667',
                    'threshold' => 'warning',
                    'width'     => '67',
                ],
            ],
            $mock->getAvancement()
        );
    }

    private function countAvancementCallBack(bool $sync)
    {
        return [
            CPersonneExercice::class            => ($sync) ? 55000 : 100000,
            CSavoirFaire::class                 => ($sync) ? 49000 : 1000,
            CDiplomeAutorisationExercice::class => ($sync) ? 40000 : 20000,
        ];
    }
}
