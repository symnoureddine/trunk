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
use Ox\Tests\UnitTestMediboard;

/**
 * @group schedules
 */
class CExternalMedecinBulkImportTest extends UnitTestMediboard
{
    /** @var CSQLDataSource */
    private $ds;

    public function testImportTablesOk()
    {
        $this->ds = CSQLDataSource::get(CExternalMedecinBulkImport::DSN);
        if (!$this->ds) {
            $this->markTestSkipped('Datasource RPPS is not available');
        }

        $this->removeTables();

        $this->assertFalse($this->ds->hasTable('diplome_autorisation_exercice', false));
        $this->assertFalse($this->ds->hasTable('savoir_faire', false));
        $this->assertFalse($this->ds->hasTable('personne_exercice', false));

        $import = new CExternalMedecinBulkImport();
        $import->createSchema();

        $this->assertTrue($this->ds->hasTable('diplome_autorisation_exercice', false));
        $this->assertTrue($this->ds->hasTable('savoir_faire', false));
        $this->assertTrue($this->ds->hasTable('personne_exercice', false));
    }

    public function testImportTablesNoDs()
    {
        $import = new CExternalMedecinBulkImport(false);
        $this->assertFalse($import->createSchema());
    }

    private function removeTables()
    {
        $this->ds->exec('DROP TABLE IF EXISTS `diplome_autorisation_exercice`, `savoir_faire`, `personne_exercice`');
    }
}
