<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Matcher;

use Ox\Core\CMbDT;
use Ox\Import\Framework\Configuration\ConfigurableInterface;
use Ox\Import\Framework\Configuration\ConfigurationTrait;
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
class DefaultMatcher implements MatcherVisitorInterface, ConfigurableInterface
{
    use ConfigurationTrait;

    /**
     * @inheritDoc
     */
    public function matchUser(CUser $user): CUser
    {
        $safe_user                = new CUser();
        $safe_user->user_username = $user->user_username;
        $safe_user->loadMatchingObjectEsc();

        if ($safe_user && $safe_user->_id) {
            $user = $safe_user;
        }

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function matchPatient(CPatient $patient): CPatient
    {
        // TODO Améliorer le matching de patient en prennant en compte les possibles espaces multiples
        // Todo: Tester aussi les cas avec prénoms composés et diacritiques
        $patient->loadMatchingPatient();

        return $patient;
    }

    /**
     * @inheritDoc
     */
    public function matchMedecin(CMedecin $medecin): CMedecin
    {
        if ($medecin->rpps) {
            $medecin->loadByRpps($medecin->rpps);
        }

        if (!$medecin->_id && $medecin->adeli) {
            $medecin->loadByAdeli($medecin->adeli);
        }

        if (!$medecin->_id) {
            $ds = $medecin->getDS();

            // Todo: Care! Is property is NULL, then $ds->prepare performs  = '' which probably does not exist in DB
            $where = [
                'nom'    => $ds->prepare('= ?', $medecin->nom),
                'prenom' => ($medecin->prenom) ? $ds->prepare('= ?', $medecin->prenom) : 'IS NULL',
                'cp'     => ($medecin->cp) ? $ds->prepare('= ?', $medecin->cp) : 'IS NULL',
            ];

            if (!$medecin->loadObject($where) && $medecin->cp) {
                $short_cp = substr($medecin->cp, 0, 2);

                $where['cp'] = $ds->prepareLike("{$short_cp}___");

                $medecin->loadObject($where);
            }
        }

        return $medecin;
    }

    /**
     * @inheritDoc
     */
    public function matchPlageConsult(CPlageconsult $plage_consult): CPlageconsult
    {
        $ds = $plage_consult->getDS();

        $where = [
            'chir_id' => $ds->prepare('= ?', $plage_consult->chir_id),
            'date'    => $ds->prepare('= ?', $plage_consult->date),
        ];

        $plage_consult->loadObject($where);

        return $plage_consult;
    }

    /**
     * @inheritDoc
     */
    public function matchConsultation(CConsultation $consultation): CConsultation
    {
        $ds = $consultation->getDS();

        $where = [
            'patient_id'      => $ds->prepare('= ?', $consultation->patient_id),
            'plageconsult_id' => $ds->prepare('= ?', $consultation->plageconsult_id),
        ];

        $consultation->loadObject($where);

        return $consultation;
    }

    /**
     * @inheritDoc
     */
    public function matchConsultationAnesth(CConsultAnesth $consultation): CConsultAnesth
    {
        // TODO: Implement matchConsultationAnesth() method.
    }

    /**
     * @inheritDoc
     */
    public function matchSejour(CSejour $sejour): CSejour
    {
        $ds = $sejour->getDS();

        $entree = $sejour->entree_reelle ?: $sejour->entree_prevue;

        $where = [
            'patient_id' => $ds->prepare('= ?', $sejour->patient_id),
            'group_id'   => $ds->prepare('= ?', $sejour->group_id),
            'entree'     => $ds->prepare(
                'BETWEEN ?1 AND ?2',
                CMbDT::dateTime('-1 DAY', $entree),
                CMbDT::dateTime('+1 DAY', $entree)
            ),
        ];

        $sejour->loadObject($where);

        return $sejour;
    }

    /**
     * @inheritDoc
     */
    public function matchFile(CFile $file): CFile
    {
        $ds = $file->getDS();

        $date = CMbDT::date($file->file_date);

        $where = [
            'object_class' => $ds->prepare('= ?', $file->object_class),
            'object_id'    => $ds->prepare('= ?', $file->object_id),
            'file_name'    => $ds->prepare('= ?', $file->file_name),
            'file_date'    => $ds->prepareLike("$date%"),
        ];

        $file->loadObject($where);

        return $file;
    }

    public function matchAntecedent(CAntecedent $antecedent): CAntecedent
    {
        $ds = $antecedent->getDS();

        $where = [
            'dossier_medical_id' => $ds->prepare('= ?', $antecedent->dossier_medical_id),
            'rques'              => $ds->prepare('= ?', $antecedent->rques),
        ];

        if ($antecedent->type) {
            $where['type'] = $ds->prepare('= ?', $antecedent->type);
        }

        $antecedent->loadObject($where);

        return $antecedent;
    }

    public function matchTraitement(CTraitement $trt): CTraitement
    {
        $ds = $trt->getDS();

        $where = [
            'dossier_medical_id' => $ds->prepare('= ?', $trt->dossier_medical_id),
            'traitement'         => $ds->prepare('= ?', $trt->traitement),
        ];

        $trt->loadObject($where);

        return $trt;
    }

    public function matchCorrespondant(CCorrespondant $correspondant): CCorrespondant
    {
        $ds = $correspondant->getDS();

        $where = [
            'patient_id' => $ds->prepare('= ?', $correspondant->patient_id),
            'medecin_id' => $ds->prepare('= ?', $correspondant->medecin_id),
        ];

        $correspondant->loadObject($where);

        return $correspondant;
    }

    public function matchEvenementPatient(CEvenementPatient $evenement_patient): CEvenementPatient
    {
        $ds = $evenement_patient->getDS();

        $where = [
            'dossier_medical_id' => $ds->prepare('= ?', $evenement_patient->dossier_medical_id),
            'praticien_id'       => $ds->prepare('= ?', $evenement_patient->praticien_id),
            'date'               => $ds->prepare('= ?', $evenement_patient->date),
            'type'               => $ds->prepare('= ?', $evenement_patient->type),
        ];

        $evenement_patient->loadObject($where);

        return $evenement_patient;
    }
}
