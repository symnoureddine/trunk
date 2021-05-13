<?php

/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Generators;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\Generators\CPatientGenerator;

/**
 * Générateur de grossesse
 */
class CGrossesseGenerator extends CObjectGenerator
{
    /** @var CGrossesse $mb_class */
    public static $mb_class = CGrossesse::class;
    /** @var array $dependances */
    public static $dependances = [CPatient::class];

    /** @var CGrossesse */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate(): CGrossesse
    {
        $patient = (new CPatientGenerator())->setForce($this->force)->generate();

        if ($patient->sexe != 'f') {
            $patient  = new CPatient();
            $patients = $patient->loadList(
                [
                    "sexe"      => "= 'f'",
                    "naissance" => "BETWEEN '1990-01-01' AND '2000-12-31'",
                ],
                null,
                1
            );
            $patient  = reset($patients);

            if (!$patient->_id) {
                $patient->nom       = "Storm";
                $patient->prenom    = "Terra";
                $patient->sexe      = "f";
                $patient->civilite  = "mme";
                $patient->naissance = "1990-01-01";

                if ($msg = $patient->store()) {
                    CAppUI::setMsg($msg, UI_MSG_WARNING);
                }
            }
        }

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "parturiente_id" => "= '$patient->_id'",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $this->object->parturiente_id        = $patient->_id;
            $this->object->terme_prevu           = CMbDT::date("+1 month");
            $this->object->date_dernieres_regles = CMbDT::date("-8 month");

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CGrossesse-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }
}
