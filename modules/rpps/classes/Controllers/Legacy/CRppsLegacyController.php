<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Controllers\Legacy;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLegacyController;
use Ox\Core\CLogger;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Import\Rpps\CExternalMedecinBulkImport;
use Ox\Import\Rpps\CExternalMedecinSync;
use Ox\Import\Rpps\CMedecinExercicePlaceManager;
use Ox\Import\Rpps\CRppsFileDownloader;
use Ox\Import\Rpps\Entity\CDiplomeAutorisationExercice;
use Ox\Import\Rpps\Entity\CPersonneExercice;
use Ox\Import\Rpps\Entity\CSavoirFaire;
use Ox\Mediboard\Patients\CMedecin;

/**
 * Description
 */
class CRppsLegacyController extends CLegacyController
{
    public function ajax_create_schema(): void
    {
        $this->checkPermAdmin();

        CView::checkin();

        $import = new CExternalMedecinBulkImport();

        if (!$import->createSchema()) {
            CAppUI::commonError();
        }

        CAppUI::stepAjax('CExternalMedecinBulkImport-msg-Tables created');

        CApp::rip();
    }

    public function ajax_populate_database(): void
    {
        $this->checkPermAdmin();

        CView::checkin();

        CApp::setTimeLimit(300);

        $downloader = new CRppsFileDownloader();
        $msg        = $downloader->downloadRppsFiles();

        CAppUI::stepAjax($msg);

        $import   = new CExternalMedecinBulkImport();
        $messages = $import->bulkImport();

        foreach ($messages as $_msg) {
            CAppUI::stepAjax(array_shift($_msg), UI_MSG_OK, ...$_msg);
        }

        CApp::rip();
    }

    public function configure(): void
    {
        $this->checkPermAdmin();

        $bulk_import    = new CExternalMedecinBulkImport();
        $can_load_local = $bulk_import->canLoadLocalInFile();

        $file_downloader = new CRppsFileDownloader();
        $is_downloadable = $file_downloader->isRppsFileDownloadable();

        $this->renderSmarty(
            'configure',
            [
                'can_load_local'  => $can_load_local,
                'is_downloadable' => $is_downloadable,
            ]
        );
    }

    public function cron_synchronize_medecin(): void
    {
        $this->checkPermEdit();

        $rpps  = CView::get('rpps', 'str');
        $adeli = CView::get('adeli', 'str');
        $step  = CView::get('step', 'num default|' . (CAppUI::conf('rpps sync_step')) ?: 50);

        CView::checkin();

        if (!$this->checkSyncEnabled()) {
            CApp::rip();
        }

        CAppUI::$localize = false;

        $sync = new CExternalMedecinSync();
        $sync->synchronizeSomeMedecins((int)$step);

        CAppUI::$localize = true;

        if ($errors = $sync->getErrors()) {
            CApp::log(CExternalMedecinSync::class . '::Errors : ' . count($errors), $errors, CLogger::LEVEL_WARNING);
        }

        if ($updated = $sync->getUpdated()) {
            CApp::log(CExternalMedecinSync::class . '::Updated : ' . count($updated), null, CLogger::LEVEL_INFO);
        }

        $stop = '1';
        if (count($errors) === 0 && count($updated) === 0) {
            $stop = '0';
        }

        CAppUI::stepAjax("Errors : " . (count($errors) . "\nUpdated : " . count($updated)));

        CAppUI::js("nextStep($stop)");
        CApp::rip();
    }

    public function cron_disable_exercice_places(): void
    {
        $this->checkPermEdit();

        $step  = CView::get('step', 'num default|100');

        CView::checkin();

        if (!$this->checkSyncEnabled()) {
            CApp::rip();
        }

        $manager = new CMedecinExercicePlaceManager();
        $manager->removeOldMedecinExercicePlaces($step);
        $manager->disableMedecinsWithoutExercicePlace($step);

        foreach ($manager->getInfos() as $_info) {
            CApp::log($_info);
        }

        foreach ($manager->getErrors() as $_err) {
            CApp::log($_err, null, CLogger::LEVEL_WARNING);
        }

        CApp::rip();
    }

    public function vw_rpps(): void
    {
        $this->checkPermAdmin();

        $this->renderSmarty('vw_rpps');
    }

    public function vw_sync_external(): void
    {
        $this->checkPermRead();

        CView::checkin();

        $sync       = new CExternalMedecinSync();
        $avancement = $sync->getAvancement();

        $this->renderSmarty(
            'vw_sync_external',
            [
                'avancement_personne_exercice' => $avancement[CPersonneExercice::class],
                'avancement_savoir_faire'      => $avancement[CSavoirFaire::class],
                'avancement_diplome'           => $avancement[CDiplomeAutorisationExercice::class],
            ]
        );
    }

    public function vw_sync_medecin(): void
    {
        $this->checkPermRead();

        CView::checkin();

        $medecin = new CMedecin();
        [$versions, $total] = $medecin->getSyncAvancement();

        $this->renderSmarty(
            'vw_sync_medecin',
            [
                'versions' => $versions,
                'total'    => $total,
            ]
        );
    }

    public function vw_synchronisation_state(): void
    {
        $this->checkPermRead();

        $this->renderSmarty('vw_synchronisation_state');
    }

    private function checkSyncEnabled(): bool
    {
        $ds = CSQLDataSource::get('rpps_import', true);
        if (!$ds || !$ds->hasTable('personne_exercice')) {
            return false;
        }

        return true;
    }
}
