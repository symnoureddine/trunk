<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Controllers\Legacy;

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CLegacyController;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Hospi\CUniteFonctionnelle;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CModeEntreeSejour;
use Ox\Mediboard\PlanningOp\CSejour;

class CHospitalizeController extends CLegacyController
{
    /**
     * Hospitalize the patient
     */
    public function ajax_hospitalize(): void
    {
        CCanDo::checkEdit();

        $consult_id = CView::get("consult_id", "ref class|CConsultation");

        CView::checkin();

        $consult = new CConsultation();
        $consult->load($consult_id);

        CAccessMedicalData::logAccess($consult);

        $sejour  = $consult->loadRefSejour();
        $patient = $consult->loadRefPatient();

        $sejour->loadRefPraticien();

        if ($sejour->_ref_praticien->isSageFemme()) {
            $sejour->praticien_id   = "";
            $sejour->_ref_praticien = new CMediusers();
        }

        $use_custom_mode_entree = CAppUI::conf("dPplanningOp CSejour use_custom_mode_entree");

        $modes_entree = CModeEntreeSejour::listModeEntree($sejour->group_id);

        if ($use_custom_mode_entree && count($modes_entree)) {
            foreach ($modes_entree as $_mode_entree) {
                if ($_mode_entree->code == "8") {
                    $sejour->mode_entree_id = $_mode_entree->_id;
                    break;
                }
            }
        } else {
            $sejour->mode_entree = "8";
        }

        $sejour->uf_soins_id = CAppUI::gconf("maternite placement uf_soins_id_dhe");

        $sejour->type = "comp";
        $collisions   = $sejour->getCollisions();

        $sejours_futur    = [];
        $count_collision  = count($collisions);
        $sejour_collision = null;
        $check_merge      = null;

        if ($count_collision == 1) {
            $sejour_collision = current($collisions);
            $sejour_collision->loadRefPraticien();
            $check_merge = $sejour->checkMerge($collisions);
        } else {
            $where = [
                "entree_reelle" => "IS NULL",
                "sejour_id"     => "!= '$sejour->_id'",
                "patient_id"    => "= '$patient->_id'",
                "annule"        => "= '0'",
            ];

            /** @var CSejour[] $sejours_futur */
            $sejours_futur = $sejour->loadList($where, "entree DESC", null, "type");
            foreach ($sejours_futur as $_sejour_futur) {
                $_sejour_futur->loadRefPraticien()->loadRefFunction();
            }
        }

        $this->renderSmarty(
            'inc_hospitalize',
            [
                'sejour'           => $sejour,
                'modes_entree'     => $modes_entree,
                'count_collision'  => $count_collision,
                'sejours_futur'    => $sejours_futur,
                'sejour_collision' => $sejour_collision,
                'check_merge'      => $check_merge,
                'affectations'     => [],
                'ufs'              => CUniteFonctionnelle::getUFs($sejour),
            ]
        );
    }
}
