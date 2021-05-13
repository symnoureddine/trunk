<?php

/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Cabinet\Generators\CActeNGAPGenerator;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Hospi\Generators\CLitGenerator;
use Ox\Mediboard\Hospi\Generators\CServiceGenerator;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\Generators\CPatientGenerator;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Class CSejourGenerator.
 * Generate a CSejour object
 */
class CSejourGenerator extends CObjectGenerator
{
    /** @var string  */
    static $mb_class    = CSejour::class;

    /** @var string[]  */
    static $dependances = [CMediusers::class, CFunctions::class, CPatient::class];

    /** @var \string[][]  */
    static $ds          = [
        "cim10" => ["DP"],
    ];

    /** @var array The possible types of CSejour */
    public static $types = ['random', 'ambu', 'comp'];

    /** @var CSejour */
    protected $object;


    /**
     * Generate a CSejour
     *
     * @return CSejour
     * @throws Exception
     */
    function generate()
    {
        $praticien                  = (new CMediusersGenerator())->generate();
        $this->object->praticien_id = $praticien->_id;

        $function               = $praticien->loadRefFunction();
        $this->object->group_id = $function->group_id;

        $patient                  = (new CPatientGenerator())->setForce($this->force)->generate();
        $this->object->patient_id = $patient->_id;

        $codes               = $this->getRandomCIM10Code();
        $this->object->DP    = $codes['code'];
        $this->object->rques = $codes['code'] . ' : ' . $codes['libelle'];

        $start_date  = CMbDT::dateTime('-' . CAppUI::conf('populate CSejour_max_years_start') . ' YEARS');
        $end_date    = CMbDT::dateTime('+' . CAppUI::conf('populate CSejour_max_years_end') . ' YEARS');
        $date_entree = CMbDT::getRandomDate($start_date, $end_date);

        $date_entree = ($date_entree < $patient->naissance) ? $patient->naissance . ' 08:00:00' : $date_entree;

        if ($date_entree > CMbDT::dateTime()) {
            $this->object->entree_prevue = $date_entree;
        } else {
            $this->object->entree_reelle = $date_entree;
        }

        if (!$this->type || $this->type == 'random') {
            if (rand(0, 100) < CAppUI::conf('populate CSejour_pct_ambu')) {
                $this->type = 'ambu';
            } else {
                $this->type = 'comp';
            }
        }

        if ($this->type == 'ambu') {
            $this->object->type = 'ambu';
            $start_date         = CMbDT::dateTime("+2 HOUR", $date_entree);
            $end_date           = CMbDT::date($start_date) . ' 23:59:59';
            $date_sortie        = CMbDT::getRandomDate($start_date, $end_date);
        } else {
            $this->object->type = 'comp';
            $max_duration       = CAppUI::conf('populate CSejour_max_days_hospi');
            $duration           = rand(1, $max_duration);
            $start_date         = CMbDT::dateTime("+1 DAYS", $date_entree);
            $end_date           = CMbDT::dateTime("+{$duration} DAYS", $date_entree);
            $date_sortie        = CMbDT::getRandomDate($start_date, $end_date);
        }

        if ($date_sortie > CMbDT::dateTime()) {
            $this->object->sortie_prevue = $date_sortie;
        } else {
            $this->object->sortie_reelle = $date_sortie;
        }

        $service = (new CServiceGenerator())->setGroup($this->object->group_id)->generate();
        if ($service && $service->_id) {
            $this->object->service_id = $service->_id;
        }

        if ($collisions = $this->object->getCollisions()) {
            $this->object = reset($collisions);
        }

        if ($msg = $this->object->store()) {
            CAppUI::stepAjax($msg, UI_MSG_ERROR);
        }

        if ($this->force) {
            return $this->object;
        }

        CAppUI::setMsg("CSejour-msg-create", UI_MSG_OK);
        $this->trace(static::TRACE_STORE);

        if ($service && $service->_id) {
            $lit = (new CLitGenerator())->setGroup($this->object->group_id)->generate();

            $affectation             = new CAffectation();
            $affectation->service_id = $service->_id;
            $affectation->lit_id     = $lit->_id;
            $affectation->sejour_id  = $this->object->_id;
            $affectation->entree     = CMbDT::getRandomDate($this->object->entree, $this->object->sortie);
            $affectation->sortie     = CMbDT::getRandomDate($affectation->entree, $this->object->sortie);

            if ($msg = $affectation->store()) {
                CAppUI::stepAjax($msg, UI_MSG_WARNING);
            }
        }

        (new CActeNGAPGenerator())
            ->setGroup($this->object->group_id)
            ->setTargetObject($this->object)
            ->setExecutant($praticien)->generate();

        return $this->object;
    }
}
