<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Controllers\Legacy;


use Exception;
use Ox\Core\CLegacyController;
use Ox\Core\CView;
use Ox\Mediboard\Maternite\CDepistageGrossesse;
use Ox\Mediboard\Maternite\CDossierPerinat;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Maternite\CNaissance;
use Ox\Mediboard\Maternite\CSurvEchoGrossesse;
use Ox\Mediboard\Patients\CConstantesMedicales;

class CPerinatalFolderController extends CLegacyController
{
    /**
     * Edit the perinatal folder in the light view
     *
     * @throws Exception
     */
    public function ajax_vw_edit_perinatal_folder(): void
    {
        $this->checkPermEdit();
        $grossesse_id = CView::get("grossesse_id", "ref class|CGrossesse");
        CView::checkin();

        $grossesse = CGrossesse::findOrNew($grossesse_id);
        $patiente  = $grossesse->loadRefParturiente();
        $dossier   = $grossesse->loadRefDossierPerinat();
        $pere      = $grossesse->loadRefPere();
        $pere->loadRefDossierMedical();
        $grossesse->loadRefsNaissances();
        $last_sejour     = $grossesse->loadLastSejour();
        $last_constantes = CConstantesMedicales::getLatestFor(
            $patiente,
            null,
            ["poids", "taille"],
            $last_sejour,
            false
        );

        $constantes_maman = $dossier->loadRefConstantesAntecedentsMaternels();

        $difference_poids = 0;

        if ($constantes_maman->poids && $last_constantes[0]->poids) {
            $difference_poids = $last_constantes[0]->poids - $constantes_maman->poids;
        }

        $depistages     = $grossesse->loadBackRefs("depistages", "date ASC");
        $last_depistage = end($depistages);

        $depistage = $last_depistage;

        if (!$last_depistage) {
            $last_depistage = new CDepistageGrossesse();
            $depistage      = new CDepistageGrossesse();
        }

        $tpl_vars = [
            'grossesse'          => $grossesse,
            'last_poids'         => $last_constantes,
            'constantes_maman'   => $constantes_maman,
            'depistage'          => $depistage,
            'last_depistage'     => $last_depistage,
            'naissance'          => new CNaissance(),
            'echographique'      => new CSurvEchoGrossesse(),
            'pathologies_fields' => $dossier->getMotherPathologiesFields(),
        ];

        $this->renderSmarty('inc_vw_edit_perinatal_folder', $tpl_vars);
    }

    /**
     * Show the mother's pathology list
     */
    public function motherPathologiesAutocomplete(): void
    {
        $this->checkPermRead();
        $input_field        = CView::get("input_field", "str");
        $keywords           = CView::get("{$input_field}", "str");
        $dossier_perinat_id = CView::get("dossier_perinat_id", "ref class|CDossierPerinat");
        CView::checkin();

        $dossier = CDossierPerinat::findOrNew($dossier_perinat_id);

        $tpl_vars = [
            'dossier'            => $dossier,
            'pathologies_fields' => $dossier->getMotherPathologiesFields($keywords),
        ];

        $this->renderSmarty('vw_mother_pathologies_autocomplete', $tpl_vars);
    }

    /**
     * Show the mother's pathology list
     */
    public function motherPathologiesTags(): void
    {
        $this->checkPermRead();
        $dossier_perinat_id = CView::get("dossier_perinat_id", "ref class|CDossierPerinat");
        CView::checkin();

        $dossier = CDossierPerinat::findOrNew($dossier_perinat_id);

        $tpl_vars = [
            'dossier'            => $dossier,
            'pathologies_fields' => $dossier->getMotherPathologiesFields(),
        ];

        $this->renderSmarty('dossier_perinatal_light/inc_vw_mother_pathologies_tags', $tpl_vars);
    }
}
