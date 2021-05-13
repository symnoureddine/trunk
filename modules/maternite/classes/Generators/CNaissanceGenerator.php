<?php

/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Maternite\CNaissance;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Générateur de naissance
 */
class CNaissanceGenerator extends CObjectGenerator
{
    /** @var CNaissance $mb_class */
    public static $mb_class = CNaissance::class;
    /** @var array $dependances */
    public static $dependances = [CSejour::class];

    /** @var CNaissance */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate(): CNaissance
    {
        $grossesse = (new CGrossesseGenerator())->setForce($this->force)->generate();

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "grossesse_id"     => "= '$grossesse->_id'",
                "sejour_maman_id"  => "IS NOT NULL",
                "sejour_enfant_id" => "IS NOT NULL",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $sejour_maman  = $this->createMotherStay($grossesse);
            $sejour_enfant = $this->createBabyAndStay($sejour_maman);

            $this->object->sejour_maman_id  = $sejour_maman->_id;
            $this->object->sejour_enfant_id = $sejour_enfant->_id;
            $this->object->grossesse_id     = $grossesse->_id;
            $this->object->rang             = 1;
            $this->object->_heure           = CMbDT::time();

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CNaissance-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }

    /**
     * Create a CSejour (Mother)
     *
     * @param CGrossesse $grossesse Prenancy
     *
     * @throws Exception
     */
    public function createMotherStay(CGrossesse $grossesse): CSejour
    {
        $maman = $grossesse->loadRefParturiente();

        $sejour    = new CSejour();
        $praticien = (new CMediusersGenerator())->generate();
        $function  = $praticien->loadRefFunction();

        $sejour->praticien_id = $praticien->_id;
        $sejour->group_id     = $function->group_id;
        $sejour->patient_id   = $maman->_id;
        $sejour->grossesse_id = $grossesse->_id;

        $sejour->entree_prevue = CMbDT::dateTime("- 2 DAY");
        $sejour->sortie_prevue = CMbDT::dateTime("+ 2 DAY");
        $sejour->type          = 'comp';

        if ($collisions = $sejour->getCollisions()) {
            $sejour = reset($collisions);
        }

        if ($msg = $sejour->store()) {
            CAppUI::stepAjax($msg, UI_MSG_WARNING);
        }

        return $sejour;
    }

    /**
     * Create a CPatient (Baby)
     *
     * @param CSejour $sejour_maman Mother stay
     */
    public function createBabyAndStay(CSejour $sejour_maman): CSejour
    {
        $maman               = $sejour_maman->loadRefPatient();
        $current_affectation = $sejour_maman->loadRefCurrAffectation();

        $date_min   = CMbDT::date("- 5 YEAR");
        $date_max   = CMbDT::date();
        $birth_date = CMbDT::getRandomDate($date_min, $date_max, "Y-m-d");

        $patient         = new CPatient();
        $patient->nom    = $maman->nom;
        $patient->prenom = "provisoire";

        $sexes    = ["f", "m"];
        $sexe_key = array_rand($sexes, 1);

        $patient->sexe             = $sexes[$sexe_key];
        $patient->civilite         = "enf";
        $patient->naissance        = $birth_date;
        $patient->_naissance       = true;
        $patient->_sejour_maman_id = $sejour_maman->_id;

        if ($msg = $patient->store()) {
            CAppUI::stepAjax($msg, UI_MSG_WARNING);
        }

        $sejour_enfant                = new CSejour();
        $sejour_enfant->entree_prevue = CMbDT::dateTime($birth_date);
        $sejour_enfant->entree_reelle = $sejour_enfant->entree_prevue;

        $sortie = $current_affectation->sortie ? $current_affectation->sortie : $sejour_maman->sortie;
        $sejour_enfant->sortie_prevue = $sortie;

        $sejour_enfant->patient_id    = $patient->_id;
        $sejour_enfant->praticien_id  = $sejour_maman->praticien_id;
        $sejour_enfant->group_id      = $sejour_maman->group_id;
        $sejour_enfant->libelle       = CAppUI::tr("CNaissance");

        // Indispensable pour indiquer aux handlers que l'on est dans le cas d'une naissance
        $sejour_enfant->_naissance           = true;
        $sejour_enfant->_apply_sectorisation = false;
        $sejour_enfant->service_id           = $sejour_maman->service_id;
        $sejour_enfant->mode_entree          = "N";

        if ($msg = $sejour_enfant->store()) {
            CAppUI::stepAjax($msg, UI_MSG_WARNING);
        }

        return $sejour_enfant;
    }
}
