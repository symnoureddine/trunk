<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Patients\CExercicePlace;

/**
 * Description
 */
class CExercicePlaceGenerator extends CObjectGenerator
{
    public static $mb_class = CExercicePlace::class;

    public function generate()
    {
        $this->object = new self::$mb_class();
        $this->object->raison_sociale = 'RS-' . uniqid();
        $this->object->exercice_place_identifier = uniqid();

        if ($msg = $this->object->store()) {
            CAppUI::setMsg($msg, UI_MSG_ERROR);
        } else {
            CAppUI::setMsg("CExercicePlace-msg-create", UI_MSG_OK);
            $this->trace(static::TRACE_STORE);
        }

        return $this->object;
    }
}
