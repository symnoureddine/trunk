<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Unit\Api;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Mediboard\System\Api\SimpleFilter;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class SimpleFilterTest extends UnitTestMediboard
{

    public function testApplyWithtoutSearchWord()
    {
        $locales = $this->getPrefs();

        $request_api = new CRequestApi(new Request());
        $filter      = new SimpleFilter($request_api);

        // No change in the array
        $this->assertEquals($locales, $filter->apply($locales));
    }

    public function testIsEnableTrue()
    {
        $request_api = new CRequestApi(new Request(['search' => 'needle']));
        $filter      = new SimpleFilter($request_api);
        $this->assertTrue($filter->isEnabled());
    }

    public function testIsEnableFalse()
    {
        $request_api = new CRequestApi(new Request(['no_search' => 'needle']));
        $filter      = new SimpleFilter($request_api);
        $this->assertFalse($filter->isEnabled());
    }

    /**
     * @dataProvider applyFilterProvider
     */
    public function testApplyFilter(string $needle, array $expected_result)
    {
        $request_api = new CRequestApi(new Request(['search' => $needle]));

        $filter      = new SimpleFilter($request_api);
        $this->assertEquals(array_values($expected_result), array_values($filter->apply($this->getPrefs())));
    }

    public function applyFilterProvider()
    {
        return [
            'searchFound'     => [
                'show',
                [
                    "constantes_show_comments_tooltip",
                    "constantes_show_view_tableau",
                    "dPpatients_show_forms_resume",
                ],
            ],
            'searchNotFound'     => [
                'toto',
                [],
            ],
        ];
    }

    private function getPrefs(): array
    {
        return [
            "alert_bmr_bhre",
            "check_establishment_grid_mode",
            "constantes_show_comments_tooltip",
            "constantes_show_view_tableau",
            "constants_table_orientation",
            "display_all_docs",
            "dPpatients_show_forms_resume",
            "hide_diff_func_atcd",
            "LogicielLectureVitale",
            "medecin_cps_pref",
            "new_date_naissance_selector",
            "patient_recherche_avancee_par_defaut",
            "see_statut_patient",
            "sort_atc_by_date",
            "update_patient_from_vitale_behavior",
            "vCardExport",
            "VitaleVisionDir",
            "vue_globale_cats",
            "vue_globale_docs_func",
            "vue_globale_docs_prat",
            "vue_globale_importance",
        ];
    }
}
