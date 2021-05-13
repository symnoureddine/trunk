<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Transformer;

use Ox\Import\Framework\Entity\Antecedent;
use Ox\Import\Framework\Entity\CImportCampaign;
use Ox\Import\Framework\Entity\Consultation;
use Ox\Import\Framework\Entity\ConsultationAnesth;
use Ox\Import\Framework\Entity\Correspondant;
use Ox\Import\Framework\Entity\EvenementPatient;
use Ox\Import\Framework\Entity\ExternalReferenceStash;
use Ox\Import\Framework\Entity\File;
use Ox\Import\Framework\Entity\Medecin;
use Ox\Import\Framework\Entity\Patient;
use Ox\Import\Framework\Entity\PlageConsult;
use Ox\Import\Framework\Entity\Sejour;
use Ox\Import\Framework\Entity\Traitement;
use Ox\Import\Framework\Entity\User;
use Ox\Import\Framework\Transformer\AbstractTransformer;
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
class Transformer extends AbstractTransformer
{
    /**
     * @inheritDoc
     */
    public function transformUser(
        User $external_user,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CUser {
        return CUser::findOrFail($external_user->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformPatient(
        Patient $external_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPatient {
        return CPatient::findOrFail($external_patient->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformMedecin(
        Medecin $external_medecin,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CMedecin {
        return CMedecin::findOrFail($external_medecin->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformPlageConsult(
        PlageConsult $external_plage_consult,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPlageconsult {
        return CPlageconsult::findOrFail($external_plage_consult->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformConsultation(
        Consultation $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultation {
        return CConsultation::findOrFail($external_consultation->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformConsultationAnesth(
        ConsultationAnesth $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultAnesth {
        return CConsultAnesth::findOrFail($external_consultation->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformSejour(
        Sejour $external_sejour,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CSejour {
        return CSejour::findOrFail($external_sejour->getExternalID());
    }

    /**
     * @inheritDoc
     */
    public function transformFile(
        File $external_file,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CFile {
        return CFile::findOrFail($external_file->getExternalID());
    }

    public function transformAntecedent(
        Antecedent $external_atcd,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CAntecedent {
        return CAntecedent::findOrFail($external_atcd->getExternalId());
    }

    public function transformTraitement(
        Traitement $external_trt,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CTraitement {
        return CTraitement::findOrFail($external_trt->getExternalId());
    }

    public function transformCorrespondant(
        Correspondant $external_correspondant,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CCorrespondant {
        return CCorrespondant::findOrFail($external_correspondant->getExternalID());
    }

    public function transformEvenementPatient(
        EvenementPatient $external_evenement_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CEvenementPatient {
        return CEvenementPatient::findOrFail($external_evenement_patient->getExternalId());
    }
}
