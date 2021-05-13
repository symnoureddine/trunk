<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLogger;
use Ox\Core\CMbDT;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CMedecinExercicePlace;

/**
 * Description
 */
class CMedecinExercicePlaceManager
{
    /** @var array */
    private $errors = [];

    /** @var array */
    private $infos = [];

    public function removeOldMedecinExercicePlaces(int $count = 100): void
    {
        $days = CAppUI::conf('rpps disable_days_withtout_update');
        if (!$days) {
            return;
        }

        $med_ex_ids = $this->loadMedecinExercicePlacesToDisable((int)$days, $count);

        if (!$med_ex_ids) {
            return;
        }

        $med_ex_place = new CMedecinExercicePlace();
        $count = count($med_ex_ids);
        if ($msg = $med_ex_place->deleteAll($med_ex_ids)) {
            $this->errors[] = $msg;
        }

        $this->infos[] = CAppUI::tr('CMedecinExercicePlaceManager-Msg-Old exercice place disabled', $count);
    }

    public function disableMedecinsWithoutExercicePlace(int $count = 100): void
    {
        $medecins = $this->loadMedecinsWithoutExercicePlace($count);

        if (!$medecins) {
            return;
        }

        $count_disable = 0;
        /** @var CMedecin $_med */
        foreach ($medecins as $_med) {
            $_med->actif = '0';
            if ($msg = $_med->store()) {
                $this->errors[] = $msg;
                continue;
            }

            $count_disable++;
        }

        $this->infos[] = CAppUI::tr('CMedecinExercicePlaceManager-Msg-CMedecins disabled', $count_disable);
    }

    private function loadMedecinExercicePlacesToDisable(int $days, int $count): array
    {
        $med_ex_place = new CMedecinExercicePlace();
        $ds = $med_ex_place->getDS();

        $where = [
            'rpps_file_version' => $ds->prepare('< ?', CMbDT::date("-{$days} DAY")),
        ];

        return $med_ex_place->loadIds($where, null, $count);
    }

    private function loadMedecinsWithoutExercicePlace(int $count): array
    {
        $medecin = new CMedecin();
        $ljoin = [
            'medecin_exercice_place' => '`medecin_exercice_place`.medecin_id = `medecin`.medecin_id',
        ];

        $where = [
            '`medecin_exercice_place`.medecin_exercice_place_id IS NULL',
            '`medecin`.actif = "1"'
        ];

        return $medecin->loadList($where, null, $count, null, $ljoin);
    }

    public function getInfos(): array
    {
        return $this->infos;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
