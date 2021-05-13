<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Transformer;

use Ox\Core\CMbDT;
use Ox\Core\CMbPath;
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
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CCorrespondant;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CEvenementPatient;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CTraitement;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Description
 */
class DefaultTransformer extends AbstractTransformer
{
    /**
     * @inheritDoc
     */
    public function transformUser(
        User $external_user,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CUser {
        $user = new CUser();

        $user->user_username   = $external_user->getUsername();
        $user->user_first_name = $external_user->getFirstName();
        $user->user_last_name  = $external_user->getLastName();
        $user->user_sexe       = $external_user->getGender();
        $user->user_birthday   = $this->formatDateTimeToStrDate($external_user->getBirthday());
        $user->user_email      = $external_user->getEmail();
        $user->user_phone      = $external_user->getPhone();
        $user->user_mobile     = $external_user->getMobile();
        $user->user_address1   = $external_user->getAddress();
        $user->user_zip        = $external_user->getZip();
        $user->user_city       = $external_user->getCity();
        $user->user_country    = $external_user->getCountry();

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function transformPatient(
        Patient $external_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPatient {
        $patient = $external_patient->getMbObject() ?? new CPatient();

        $patient->nom             = $external_patient->getNom() ?? null;
        $patient->prenom          = $external_patient->getPrenom() ?? null;
        $patient->naissance       = $this->formatDateTimeToStrDate($external_patient->getNaissance()) ?? null;
        $patient->nom_jeune_fille = $external_patient->getNomJeuneFille() ?? null;
        $patient->profession      = $external_patient->getProfession() ?? null;
        $patient->email           = $external_patient->getEmail() ?? null;
        $patient->tel             = $this->sanitizeTel($external_patient->getTel()) ?? null;
        $patient->tel2            = $this->sanitizeTel($external_patient->getTel2()) ?? null;
        $patient->tel_autre       = $this->sanitizeTel($external_patient->getTelAutre()) ?? null;
        $patient->adresse         = $external_patient->getAdresse() ?? null;
        $patient->cp              = $external_patient->getCp() ?? null;
        $patient->ville           = $external_patient->getVille() ?? null;
        $patient->pays            = $external_patient->getPays() ?? null;
        $patient->matricule       = $external_patient->getMatricule() ?? null;
        $patient->sexe            = $external_patient->getSexe() ?? null;
        $patient->civilite        = $external_patient->getCivilite() ?? null;
        $patient->rques           = $external_patient->getRques() ?? null;

        $patient->medecin_traitant =
            $reference_stash->getMbIdByExternalId('medecin', $external_patient->getMedecinTraitant());

        if ($patient->sexe === 'u') {
            $patient->guessSex();
            if ($patient->sexe === 'u') {
                $patient->sexe = null;
            }
        }

        return $patient;
    }

    /**
     * @inheritDoc
     */
    public function transformMedecin(
        Medecin $external_medecin,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CMedecin {
        $medecin = $external_medecin->getMbObject() ?? new CMedecin();

        $medecin->nom         = $external_medecin->getNom() ?? null;
        $medecin->prenom      = $external_medecin->getPrenom() ?? null;
        $medecin->titre       = $external_medecin->getTitre() ?? null;
        $medecin->email       = $external_medecin->getEmail() ?? null;
        $medecin->tel         = $this->sanitizeTel($external_medecin->getTel()) ?? null;
        $medecin->tel_autre   = $this->sanitizeTel($external_medecin->getTelAutre()) ?? null;
        $medecin->adresse     = $external_medecin->getAdresse() ?? null;
        $medecin->cp          = $external_medecin->getCp() ?? null;
        $medecin->ville       = $external_medecin->getVille() ?? null;
        $medecin->disciplines = $external_medecin->getDisciplines() ?? null;
        $medecin->sexe        = $external_medecin->getSexe() ?? null;
        $medecin->rpps        = $external_medecin->getRpps() ?? null;
        $medecin->adeli       = $external_medecin->getAdeli() ?? null;

        return $medecin;
    }

    /**
     * @inheritDoc
     */
    public function transformPlageConsult(
        PlageConsult $external_plage_consult,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CPlageconsult {
        $plage_consult = $external_plage_consult->getMbObject() ?? new CPlageconsult();

        // Never change the date of a CPlageconsult
        if (!$plage_consult->_id) {
            $plage_consult->date  = $this->formatDateTimeToStrDate($external_plage_consult->getDate()) ?? null;
            $plage_consult->debut = $this->formatDateTimeToStrTime($external_plage_consult->getDebut()) ?? null;
            $plage_consult->fin   = $this->formatDateTimeToStrTime($external_plage_consult->getFin()) ?? null;
        }

        $plage_consult->freq    = $this->formatDateTimeToStrTime($external_plage_consult->getFreq()) ?? null;
        $plage_consult->libelle = $external_plage_consult->getLibelle() ?? null;

        $plage_consult->chir_id = $reference_stash->getMbIdByExternalId(
            'utilisateur',
            $external_plage_consult->getChirId()
        );

        return $plage_consult;
    }

    /**
     * @inheritDoc
     */
    public function transformConsultation(
        Consultation $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultation {
        $consultation = $external_consultation->getMbObject() ?? new CConsultation();

        $consultation->heure            = $this->formatDateTimeToStrTime($external_consultation->getHeure()) ?? null;
        $consultation->duree            = $external_consultation->getDuree() ?? null;
        $consultation->motif            = $external_consultation->getMotif() ?? null;
        $consultation->rques            = $external_consultation->getRques() ?? null;
        $consultation->examen           = $external_consultation->getExamen() ?? null;
        $consultation->traitement       = $external_consultation->getTraitement() ?? null;
        $consultation->histoire_maladie = $external_consultation->getHistoireMaladie() ?? null;
        $consultation->conclusion       = $external_consultation->getConclusion() ?? null;
        $consultation->resultats        = $external_consultation->getResultats() ?? null;
        $consultation->chrono           = $external_consultation->getChrono() ?? null;

        $plage_consult_type
            = ($external_consultation->getDefaultRefs()) ? 'plage_consultation' : 'plage_consultation_autre';


        $consultation->plageconsult_id =
            $reference_stash->getMbIdByExternalId($plage_consult_type, $external_consultation->getPlageconsultId());

        $consultation->patient_id =
            $reference_stash->getMbIdByExternalId('patient', $external_consultation->getPatientId());

        return $consultation;
    }

    /**
     * @inheritDoc
     */
    public function transformConsultationAnesth(
        ConsultationAnesth $external_consultation,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CConsultAnesth {
        // TODO: Implement transformConsultationAnesth() method.
    }

    /**
     * @inheritDoc
     */
    public function transformSejour(
        Sejour $external_sejour,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CSejour {
        $sejour = $external_sejour->getMbObject() ?? new CSejour();

        $sejour->type          = $external_sejour->getType() ?? null;
        $sejour->libelle       = $external_sejour->getLibelle() ?? null;
        $sejour->entree_prevue = $this->formatDateTimeToStr($external_sejour->getEntreePrevue()) ?? null;
        $sejour->entree_reelle = $this->formatDateTimeToStr($external_sejour->getEntreeReelle()) ?? null;
        $sejour->sortie_prevue = $this->formatDateTimeToStr($external_sejour->getSortiePrevue()) ?? null;
        $sejour->sortie_reelle = $this->formatDateTimeToStr($external_sejour->getSortieReelle()) ?? null;

        $sejour->patient_id   = $reference_stash->getMbIdByExternalId('patient', $external_sejour->getPatientId());
        $sejour->praticien_id = $reference_stash->getMbIdByExternalId(
            'utilisateur',
            $external_sejour->getPraticienId()
        );

        // Todo: Try to remove
        $sejour->group_id = CGroups::loadCurrent()->_id;

        return $sejour;
    }

    /**
     * @inheritDoc
     */
    public function transformFile(
        File $external_file,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CFile {
        $file = $external_file->getMbObject() ?? new CFile();

        $file->file_date = $this->formatDateTimeToStr($external_file->getFileDate()) ?? null;
        $file->file_name = $external_file->getFileName() ?? null;
        $file->file_type = $external_file->getFileType() ?? null;

        if ($external_file->getFileContent()) {
            $file->setContent($external_file->getFileContent());
        } elseif ($external_file->getFilePath()) {
            $file->setCopyFrom($external_file->getFilePath());
        }


        if ($reference_stash && $external_file->getAuthorId()) {
            $file->author_id = $reference_stash->getMbIdByExternalId('utilisateur', $external_file->getAuthorId());
        }

        if (!$file->author_id) {
            $file->author_id = CMediusers::get()->_id;
        }

        if ($sejour_id = $this->getContextId($external_file->getSejourId(), 'sejour', $reference_stash)) {
            $file->object_class = 'CSejour';
            $file->object_id    = $sejour_id;
        } elseif (
            $consult_id = $this->getContextId(
                $external_file->getConsultationId(),
                $external_file->getDefaultRefs() ? 'consultation' : 'consultation_autre',
                $reference_stash
            )
        ) {
            $file->object_class = 'CConsultation';
            $file->object_id    = $consult_id;
        } elseif ($event_id = $this->getEvenementId($external_file, $reference_stash)) {
            $file->object_class = 'CEvenementPatient';
            $file->object_id    = $event_id;
        } elseif ($patient_id = $external_file->getPatientId()) {
            $file->object_class = 'CPatient';
            $file->object_id    = $reference_stash->getMbIdByExternalId('patient', $patient_id);
        }

        if ($cat_name = $external_file->getFileCatName()) {
            $file->file_category_id = $this->getCategorie($cat_name);
        }

        $file->fillFields();
        $file->updateFormFields();

        return $file;
    }

    protected function getCategorie(string $cat_name): ?int
    {
        $cat = new CFilesCategory();
        $cat->nom = $cat_name;
        $cat->loadMatchingObjectEsc();

        return $cat->_id;
    }

    public function transformAntecedent(
        Antecedent $external_atcd,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CAntecedent {
        $atcd = $external_atcd->getMbObject() ?? new CAntecedent();

        $patient_id = $reference_stash->getMbIdByExternalId('patient', $external_atcd->getPatientId());

        if ($patient_id) {
            $atcd->rques   = $external_atcd->getText() ?? null;
            $atcd->comment = $external_atcd->getComment() ?? null;
            $atcd->date    = ($atcd->_id) ? null : CMbDT::date();
            if ($date = $external_atcd->getDate()) {
                $atcd->date = $this->formatDateTimeToStrDate($external_atcd->getDate());
            }

            $atcd->type = $external_atcd->getType() ?? null;

            if ($user_id = $reference_stash->getMbIdByExternalId('utilisateur', $external_atcd->getOwnerId())) {
                $atcd->owner_id = $user_id;
            }

            $atcd->dossier_medical_id = CDossierMedical::dossierMedicalId($patient_id, 'CPatient');
        }

        return $atcd;
    }

    public function transformTraitement(
        Traitement $external_trt,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CTraitement {
        $trt = $external_trt->getMbObject() ?? new CTraitement();

        $patient_id = $reference_stash->getMbIdByExternalId('patient', $external_trt->getPatientId());

        if ($patient_id) {
            $trt->traitement = $external_trt->getTraitement() ?? null;
            $trt->debut      = ($trt->_id) ? null : CMbDT::date();
            if ($debut = $external_trt->getDebut()) {
                $trt->debut = $this->formatDateTimeToStrDate($external_trt->getDebut());
            }

            if ($fin = $external_trt->getFin()) {
                $trt->fin = $this->formatDateTimeToStrDate($external_trt->getFin());
            }

            if ($user_id = $reference_stash->getMbIdByExternalId('utilisateur', $external_trt->getOwnerId())) {
                $trt->owner_id = $user_id;
            }

            $trt->dossier_medical_id = CDossierMedical::dossierMedicalId($patient_id, 'CPatient');
        }

        return $trt;
    }

    public function transformCorrespondant(
        Correspondant $external_correspondant,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CCorrespondant {
        $correspondant = $external_correspondant->getMbObject() ?? new CCorrespondant();

        $correspondant->patient_id =
            $reference_stash->getMbIdByExternalId('patient', $external_correspondant->getPatientId());
        $correspondant->medecin_id =
            $reference_stash->getMbIdByExternalId('medecin', $external_correspondant->getMedecinId());

        if ($correspondant->patient_id && $correspondant->medecin_id) {
            $correspondant->loadMatchingObjectEsc();
        }

        return $correspondant;
    }

    public function transformEvenementPatient(
        EvenementPatient $external_evenement_patient,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): CEvenementPatient {
        $evenement_patient = $external_evenement_patient->getMbObject() ?? new CEvenementPatient();

        $evenement_patient->date        = $this->formatDateTimeToStrDate(
                $external_evenement_patient->getDate()
            ) ?? null;
        $evenement_patient->libelle     = $external_evenement_patient->getLibelle() ?? null;
        $evenement_patient->description = $external_evenement_patient->getDescription() ?? null;
        $evenement_patient->type        = $external_evenement_patient->getType() ?? null;

        $patient_id =
            $reference_stash->getMbIdByExternalId('patient', $external_evenement_patient->getPatientId());

        $evenement_patient->dossier_medical_id = CDossierMedical::dossierMedicalId($patient_id, 'CPatient');

        $evenement_patient->praticien_id =
            $reference_stash->getMbIdByExternalId('utilisateur', $external_evenement_patient->getPraticienId());


        return $evenement_patient;
    }

    protected function getEvenementId(File $file, ExternalReferenceStash $reference_stash)
    {
        return $reference_stash->getMbIdByExternalId(
            'evenement_patient',
            $file->getEvenementId()
        );
    }

    protected function getContextId(
        ?string $external_id,
        string $type,
        ExternalReferenceStash $reference_stash
    ): ?string {
        return $reference_stash->getMbIdByExternalId($type, $external_id);
    }
}
