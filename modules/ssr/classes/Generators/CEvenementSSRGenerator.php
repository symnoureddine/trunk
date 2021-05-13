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
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;
use Ox\Mediboard\Prescription\Generators\CPrescriptionLineElementGenerator;
use Ox\Mediboard\Ssr\CEvenementSSR;

/**
 * Description
 */
class CEvenementSSRGenerator extends CObjectGenerator
{
    /** @var string */
    public static $mb_class = CEvenementSSR::class;

    /** @var CEvenementSSR */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $prescription_line_element = (new CPrescriptionLineElementGenerator())->generate();
        $kine                      = (new CMediusersGenerator())->generate("Rééducateur");

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "prescription_line_element_id" => "= '$prescription_line_element->_id'",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

            if ($kine->_id && !$kine->code_intervenant_cdarr) {
                $kine->code_intervenant_cdarr = 12;
                $kine->store();
            }

            $datetime_debut = CMbDT::date() . " 09:00:00";

            $sejour = $this->getSejour($datetime_debut);

            $this->object->sejour_id                    = $sejour->_id;
            $this->object->prescription_line_element_id = $prescription_line_element->_id;
            $this->object->duree                        = 30;
            $this->object->type_seance                  = "dediee";
            $this->object->therapeute_id                = $kine->_id;
            $this->object->debut                        = $datetime_debut;
            $this->object->realise                      = 0;
            $this->object->annule                       = 0;

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CEvenementSSR-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }

    /**
     * Get the right stay to ossociate CEvenementSSR
     *
     * @param string $datetime Datetime
     */
    public function getSejour(string $datetime): CSejour
    {
        $where         = [];
        $where[]       = "'$datetime' <= sejour.sortie && '$datetime' >= sejour.entree";
        $sejour        = new CSejour();
        $sejour->loadObject($where);

        return $sejour;
    }
}
