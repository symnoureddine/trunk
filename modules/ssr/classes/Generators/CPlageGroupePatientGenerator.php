<?php

/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Generators;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Ssr\CCategorieGroupePatient;
use Ox\Mediboard\Ssr\CPlageGroupePatient;

/**
 * Description
 */
class CPlageGroupePatientGenerator extends CObjectGenerator
{
    /** @var string */
    public static $mb_class    = CPlageGroupePatient::class;
    /** @var array */
    public static $dependances = [CCategorieGroupePatient::class];

    /** @var CPlageGroupePatient */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $categorie_groupe_patient = (new CCategorieGroupePatientGenerator())->generate();
        $evenement_ssr            = (new CEvenementSSRGenerator())->generate();

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "categorie_groupe_patient_id" => "= '$categorie_groupe_patient->_id'",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

            $element_prescription = $evenement_ssr->loadRefPrescriptionLineElement()->loadRefElement();

            $this->object->categorie_groupe_patient_id = $categorie_groupe_patient->_id;
            $this->object->elements_prescription       = "$element_prescription->_id";
            $this->object->nom                         = "Plage n° " . rand(0, 10000);
            $this->object->groupe_day                  = $days[array_rand($days)];
            $this->object->heure_debut                 = CMbDT::time("-2 HOURS");
            $this->object->heure_fin                   = CMbDT::time("+2 HOURS");
            $this->object->actif                       = random_int(0, 1);

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CPlageGroupePatient-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }
}
