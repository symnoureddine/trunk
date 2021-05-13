<?php

/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Test;

use Ox\Core\CMbDT;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Ssr\CEvenementSSR;
use Ox\Mediboard\Ssr\CPlageGroupePatient;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

class CPlageGroupePatientTest extends UnitTestMediboard
{
    /**
     * Test to calculate dates for the CPlageGroupePatient
     *
     * @throws TestsException
     */
    public function testLoadRefSejoursAssocies(): void
    {
        /** @var CEvenementSSR $evenement_ssr */
        $evenement_ssr = $this->getRandomObjects(CEvenementSSR::class, 1);

        if (!$evenement_ssr->plage_groupe_patient_id) {
            /** @var CPlageGroupePatient $plage_groupe_patient */
            $groupe_patient = $this->getRandomObjects(CPlageGroupePatient::class, 1);

            $evenement_ssr->plage_groupe_patient_id = $groupe_patient->_id;
            $evenement_ssr->store();
        }

        $plage_groupe_patient = $evenement_ssr->loadRefPlageGroupePatient();

        $this->assertEmpty($plage_groupe_patient->loadRefSejoursAssocies());
    }

    /**
     * }
     * Test to calculate dates for the CPlageGroupePatient
     *
     * @throws TestsException
     */
    public function testCalculateDatesForPlageGroup(): void
    {
        /** @var CSejour $sejour */
        $sejour = $this->getRandomObjects(CSejour::class, 1);
        /** @var CPlageGroupePatient $plage_groupe_patient */
        $plage_groupe_patient = $this->getRandomObjects(CPlageGroupePatient::class, 1);

        $sejour->entree = CMbDT::dateTime("- 2 DAY");
        $sejour->sortie = CMbDT::dateTime("+ 10 DAY");

        $now               = CMbDT::date();
        $first_day_of_week = CMbDT::date("$plage_groupe_patient->groupe_day this week");
        if ($now > $first_day_of_week) {
            $first_day_of_week = CMbDT::date("+1 week", $first_day_of_week);
        }

        $days = $plage_groupe_patient->calculateDatesForPlageGroup($sejour, $first_day_of_week);

        $this->assertGreaterThanOrEqual(1, count($days));

        $period_ok = $sejour->entree < reset($days);

        $this->assertTrue($period_ok);
    }

    /**
     * Test of all events realized over a period of time
     *
     * @throws TestsException
     */
    public function testAllEventsRealized(): void
    {
        /** @var CPlageGroupePatient $plage_groupe */
        $plage_groupe = $this->getRandomObjects(CPlageGroupePatient::class, 1);

        if ($plage_groupe->_id && !$plage_groupe->actif) {
            $plage_groupe->actif = 1;
        }

        $first_day_week = CMbDT::date("this week monday");
        $date_of_week   = CMbDT::date("this $plage_groupe->groupe_day", $first_day_week);
        $debut          = $date_of_week . " " . $plage_groupe->heure_debut;

        $ok = $plage_groupe->allEventsRealized($debut, null);

        $this->assertFalse($ok);
    }
}
