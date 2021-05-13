<?php
/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Tests\Unit\Entity;

use Ox\Import\Rpps\CExternalMedecinBulkImport;
use Ox\Import\Rpps\Entity\CSavoirFaire;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CSavoirFaireTest extends UnitTestMediboard
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Create schema for tables to be available
        $import = new CExternalMedecinBulkImport();
        $import->createSchema();
    }

    public function testSynchronizeEmpty()
    {
        $savoir_faire = new CSavoirFaire();
        $this->assertEquals(new CMedecin(), $savoir_faire->synchronize());
    }
}
