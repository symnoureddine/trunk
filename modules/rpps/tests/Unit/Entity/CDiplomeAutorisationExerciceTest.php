<?php
/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Tests\Unit\Entity;

use Ox\Import\Rpps\CExternalMedecinBulkImport;
use Ox\Import\Rpps\Entity\CDiplomeAutorisationExercice;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CDiplomeAutorisationExerciceTest extends UnitTestMediboard
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
        $diplome = new CDiplomeAutorisationExercice();
        $this->assertEquals(new CMedecin(), $diplome->synchronize());
    }
}
