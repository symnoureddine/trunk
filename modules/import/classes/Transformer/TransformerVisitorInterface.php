<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Transformer;

use Ox\Import\Framework\Entity\Antecedent;
use Ox\Import\Framework\Entity\CImportCampaign;
use Ox\Import\Framework\Entity\Consultation;
use Ox\Import\Framework\Entity\ConsultationAnesth;
use Ox\Import\Framework\Entity\Correspondant;
use Ox\Import\Framework\Entity\EvenementPatient;
use Ox\Import\Framework\Entity\File;
use Ox\Import\Framework\Entity\Medecin;
use Ox\Import\Framework\Entity\Patient;
use Ox\Import\Framework\Entity\PlageConsult;
use Ox\Import\Framework\Entity\ExternalReferenceStash;
use Ox\Import\Framework\Entity\Sejour;
use Ox\Import\Framework\Entity\Traitement;
use Ox\Import\Framework\Entity\User;
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
interface TransformerVisitorInterface
{
    /**
     * Transform an external user
     *
     * @param User                        $external_user
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CUser
     */
    public function transformUser(
        User $external_user,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CUser;

    /**
     * Transform an external patient
     *
     * @param Patient                     $external_patient
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CPatient
     */
    public function transformPatient(
        Patient $external_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPatient;

    /**
     * Transform an external medecin
     *
     * @param Medecin                     $external_medecin
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CMedecin
     */
    public function transformMedecin(
        Medecin $external_medecin,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CMedecin;

    /**
     * Transform an external plage consult
     *
     * @param PlageConsult                $external_plage_consult
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CPlageconsult
     */
    public function transformPlageConsult(
        PlageConsult $external_plage_consult,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPlageconsult;

    /**
     * Transform an external consultation
     *
     * @param Consultation                $external_consultation
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CConsultation
     */
    public function transformConsultation(
        Consultation $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultation;

    /**
     * Transform an external consultation anesth
     *
     * @param ConsultationAnesth          $external_consultation
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CConsultAnesth
     */
    public function transformConsultationAnesth(
        ConsultationAnesth $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultAnesth;

    /**
     * Transform an external sejour
     *
     * @param Sejour                      $external_sejour
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CSejour
     */
    public function transformSejour(
        Sejour $external_sejour,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CSejour;

    /**
     * Transform an external file
     *
     * @param File                        $external_file
     * @param ExternalReferenceStash|null $reference_stash
     *
     * @return CFile
     */
    public function transformFile(
        File $external_file,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CFile;

    public function transformAntecedent(
        Antecedent $external_atcd,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CAntecedent;

    public function transformTraitement(
        Traitement $external_trt,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CTraitement;

    public function transformCorrespondant(
        Correspondant $external_correspondant,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CCorrespondant;

    public function transformEvenementPatient(
        EvenementPatient $external_evenement_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CEvenementPatient;

}
