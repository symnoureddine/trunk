<?php

/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Tests\Unit;

use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

class CExamenNouveauNeTest extends UnitTestMediboard
{
    /**
     * @throws TestsException
     */
    public function testLoadRefGrossesse(): void
    {
        $examen = $this->getRandomObjects("CExamenNouveauNe", 1);
        $this->assertIsString($examen->grossesse_id);

        $grossesse = $examen->loadRefGrossesse();
        $this->assertEquals($grossesse->_id, $examen->grossesse_id);
    }

    /**
     * @throws TestsException
     */
    public function testLoadRefNaissance(): void
    {
        $examen = $this->getRandomObjects("CExamenNouveauNe", 1);
        $this->assertIsString($examen->naissance_id);

        $naissance = $examen->loadRefNaissance();
        $this->assertEquals($naissance->_id, $examen->naissance_id);
        $this->assertNotEmpty($naissance->sejour_maman_id);
        $this->assertNotEmpty($naissance->sejour_enfant_id);
    }

    /**
     * @throws TestsException
     */
    public function testLoadRefGuthrieUser(): void
    {
        $examen       = $this->getRandomObjects("CExamenNouveauNe", 1);
        $guthrie_user = $examen->loadRefGuthrieUser();

        if ($examen->guthrie_user_id) {
            $this->assertEquals($guthrie_user->_id, $examen->guthrie_user_id);
        } else {
            $this->assertNull($guthrie_user->_id);
        }
    }

    /**
     * @throws TestsException
     */
//    public function testCheckGuthrieExam(): void
//    {
//        $this->markTestSkipped("Voir avec Valentin pour config élément de prescription");
//    }

    /**
     * @throws TestsException
     */
//    public function testGetOEAExam(): void
//    {
//        $this->markTestSkipped("Voir avec Valentin pour config élément de prescription");
//    }

    /**
     * @throws TestsException
     */
    public function testGetJours(): void
    {
        $examen = $this->getRandomObjects("CExamenNouveauNe", 1);
        $this->assertEmpty($examen->_jours);

        $examen->getJours();

        $this->assertGreaterThan(0, $examen->_jours);
    }
}
