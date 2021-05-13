<?php
/**
 * @package Mediboard\\${Module}
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel\Tests\Unit;

use Exception;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Personnel\CPersonnel;
use Ox\Tests\UnitTestMediboard;

class CPersonnelTest extends UnitTestMediboard
{
    /**
     * Create personnel object
     *
     * @return CPersonnel
     * @throws Exception
     */
    public function testCreatePersonnel(): CPersonnel
    {
        $personnel = $this->getRandomObjects("CPersonnel", 1);

        $this->assertNotNull($personnel->_id);

        return $personnel;
    }

    /**
     * Test of update form field
     */
    public function testUpdateFormFields(): void
    {
        $personnel = $this->getRandomObjects("CPersonnel", 1);
        $personnel->updateFormFields();

        $this->assertNotNull($personnel->_view);
    }

    /**
     * Test of load list personnel
     */
    public function testLoadListPers(): void
    {
        $list_nurse_sspi = CPersonnel::loadListPers("reveil");

        $this->assertArrayNotHasKey("personnel_id", $list_nurse_sspi);
    }

    /**
     * Test of load list emplacement
     *
     * @depends testCreatePersonnel
     */
    public function testLoadListEmplacement(CPersonnel $personnel): void
    {
        $list_emplacements = $personnel->loadListEmplacement();

        $this->assertNotNull($list_emplacements);
    }

    /**
     * Test of mass load list emplacement
     */
    public function testMassLoadListEmplacement(): void
    {
        /** @var array $personnels */
        $personnels = $this->getRandomObjects("CPersonnel", 5);
        CPersonnel::massLoadListEmplacement($personnels);

        foreach ($personnels as $_personnel) {
            $_personnel->loadListEmplacement();
            $this->assertNotNull($_personnel->_emplacements);
        }
    }
}

