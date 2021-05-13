<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Matcher;

use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Import\Framework\Configuration\ConfigurableInterface;
use Ox\Import\Framework\Configuration\ConfigurationTrait;
use Ox\Import\Framework\Matcher\MatcherVisitorInterface;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CCorrespondant;
use Ox\Mediboard\Patients\CEvenementPatient;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CTraitement;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Description
 */
class Matcher implements MatcherVisitorInterface, ConfigurableInterface
{
    use ConfigurationTrait;

    /**
     * @inheritDoc
     */
    public function matchUser(CUser $user): CUser
    {
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function matchPatient(CPatient $patient): CPatient
    {
        return $patient;
    }

    /**
     * @inheritDoc
     */
    public function matchMedecin(CMedecin $medecin): CMedecin
    {
        return $medecin;
    }

    /**
     * @inheritDoc
     */
    public function matchPlageConsult(CPlageconsult $plage_consult): CPlageconsult
    {
        return $plage_consult;
    }

    /**
     * @inheritDoc
     */
    public function matchConsultation(CConsultation $consultation): CConsultation
    {
        return $consultation;
    }

    /**
     * @inheritDoc
     */
    public function matchConsultationAnesth(CConsultAnesth $consultation): CConsultAnesth
    {
        return $consultation;
    }

    /**
     * @inheritDoc
     */
    public function matchSejour(CSejour $sejour): CSejour
    {
        return $sejour;
    }

    /**
     * @inheritDoc
     */
    public function matchFile(CFile $file): CFile
    {
        return $file;
    }


    public function matchAntecedent(CAntecedent $antecedent): CAntecedent
    {
        return $antecedent;
    }

    public function matchTraitement(CTraitement $trt): CTraitement
    {
        return $trt;
    }

    public function matchCorrespondant(CCorrespondant $correspondant): CCorrespondant
    {
        return $correspondant;
    }

    public function matchEvenementPatient(CEvenementPatient $evenement_patient): CEvenementPatient
    {
        return $evenement_patient;
    }
}
