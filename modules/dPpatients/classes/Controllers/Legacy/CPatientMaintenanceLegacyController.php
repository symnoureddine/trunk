<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Controllers\Legacy;

use Ox\Core\CLegacyController;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CPatientSignature;


/**
 * Description
 */
class CPatientMaintenanceLegacyController extends CLegacyController
{
    public function ajax_vw_table_duplicates(): void
    {
        $this->checkPermAdmin();

        $start = CView::get("start", "num default|0");
        $step  = CView::get("step", "num default|20");

        CView::checkin();

        CView::enforceSlave();

        $patient_signature = new CPatientSignature();
        $duplicates        = $patient_signature->findDuplicates($start, $step);

        $tpl_vars = [
            "duplicates"       => $duplicates,
            "start_duplicates" => $start,
            "step"             => $step,
        ];

        $this->renderSmarty('inc_identito_vigilance_tab_patients.tpl', $tpl_vars);
    }

    public function vw_identito_vigilance_pat(): void
    {
        $this->checkPermAdmin();

        CView::checkin();

        $start = 0;

        $this->renderSmarty(
            'vw_identito_vigilance_pat.tpl',
            [
                'start_duplicates' => $start,
                'start_homonymes'  => $start,
            ]
        );
    }
}
