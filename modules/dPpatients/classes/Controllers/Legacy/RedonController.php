<?php

namespace Ox\Mediboard\Patients\Controllers\Legacy;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CLegacyController;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CConstantesMedicales;
use Ox\Mediboard\Patients\CReleveRedon;

class RedonController extends CLegacyController
{
    /**
     * Store all redon statements
     *
     * @return mixed
     * @throws Exception
     */
    public function storeAllReleveRedons()
    {
        $this->checkPermEdit();

        $releve_redons = CView::post("redons", "str");

        CView::checkin();

        $releve_redons         = json_decode(stripslashes($releve_redons), true);
        $counter_redon         = 0;
        $constante_medicale_id = 0;

        foreach ($releve_redons as $_releve_redon) {
            $releve_redon                            = new CReleveRedon();
            $releve_redon->redon_id                  = $_releve_redon["redon_id"];
            $releve_redon->date                      = $_releve_redon["date"];
            $releve_redon->qte_observee              = $_releve_redon["qte_observee"];
            $releve_redon->vidange_apres_observation = $_releve_redon["vidange_apres_observation"];

            if ($msg = $releve_redon->store()) {
                CAppUI::stepAjax($msg, UI_MSG_ERROR);
                return $msg;
            }

            $redon       = $releve_redon->loadRefRedon();
            $releve_cste = CConstantesMedicales::findOrNew($constante_medicale_id);

            if ($_releve_redon["qte_diff"]) {
                $releve_cste->datetime                     = "current";
                $releve_cste->context_class                = "CSejour";
                $releve_cste->context_id                   = $redon->sejour_id;
                $releve_cste->patient_id                   = $redon->loadRefSejour()->patient_id;
                $releve_cste->{$redon->constante_medicale} = $_releve_redon["qte_diff"];

                if ($msg = $releve_cste->store()) {
                    return $msg;
                }

                $releve_redon->constantes_medicales_id = $releve_cste->_id;

                if ($msg = $releve_redon->store()) {
                    return $msg;
                }

                $releve_cste->{$redon->constante_medicale} = $_releve_redon["qte_diff"];

                if ($msg = $releve_cste->store()) {
                    return $msg;
                }

                $constante_medicale_id = $releve_cste->_id;
            }

            $counter_redon++;
        }

        CAppUI::stepAjax(CAppUI::tr("CReleveRedon-msg-create") . " x $counter_redon");
    }
}
