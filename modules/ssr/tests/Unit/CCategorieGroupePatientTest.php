<?php

/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Test;

use Ox\Mediboard\Ssr\CCategorieGroupePatient;
use Ox\Mediboard\Ssr\CPlageGroupePatient;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

class CCategorieGroupePatientTest extends UnitTestMediboard
{
    /**
     * Test to create patient group category object
     *
     * @return CCategorieGroupePatient
     * @throws TestsException
     */
    public function testCreateCategorieGroupePatient(): CCategorieGroupePatient
    {
        $categorie_groupe = $this->getRandomObjects("CCategorieGroupePatient", 1);

        $this->assertInstanceOf(CCategorieGroupePatient::class, $categorie_groupe);
        $this->assertNotNull($categorie_groupe->_id);

        return $categorie_groupe;
    }

    /**
     * Test to load the group ranges
     */
    public function testLoadRefPlagesGroupe(): void
    {
        /** @var CCategorieGroupePatient $category_groupe */
        $category_groupe = $this->getRandomObjects("CCategorieGroupePatient", 1);
        /** @var CPlageGroupePatient $plage */
        $plage           = $this->getRandomObjects("CPlageGroupePatient", 1);
        $category_groupe = $plage->loadRefCategorieGroupePatient();

        $plages = $category_groupe->loadRefPlagesGroupe();

        $this->assertInstanceOf(CPlageGroupePatient::class, reset($plages));
        $this->assertEquals($category_groupe->_id, reset($plages)->categorie_groupe_patient_id);
    }

    /**
     * Test of update form field
     */
    public function testUpdateFormFields(): void
    {
        /** @var CCategorieGroupePatient $categorie_groupe */
        $categorie_groupe = $this->getRandomObjects("CCategorieGroupePatient", 1);
        $categorie_groupe->updateFormFields();

        $this->assertEquals($categorie_groupe->_view, $categorie_groupe->nom);
    }
}
