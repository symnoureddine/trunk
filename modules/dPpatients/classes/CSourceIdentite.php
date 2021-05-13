<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\OpenData\CCommuneFrance;

/**
 * Source d'identité du patient
 */
class CSourceIdentite extends CMbObject
{
    public const TRAITS_STRICTS_REFERENCE = [
        'nom'                     => 'nom',
        'nom_naissance'           => 'nom_jeune_fille',
        'prenom_naissance'        => 'prenom',
        'prenoms'                 => 'prenoms',
        'prenom_usuel'            => 'prenom_usuel',
        'date_naissance'          => 'naissance',
        'sexe'                    => 'sexe',
        'pays_naissance_insee'    => 'pays_naissance_insee',
        '_pays_naissance_insee'   => '_pays_naissance_insee',
        'commune_naissance_insee' => 'commune_naissance_insee',
        'cp_naissance'            => 'cp_naissance',
        '_lieu_naissance'         => 'lieu_naissance'
    ];

    /** @var int Primary key */
    public $source_identite_id;

    /** @var int */
    public $patient_id;

    /** @var int */
    public $active;

    /** @var string */
    public $mode_obtention;

    /** @var string */
    public $type_justificatif;

    /** @var string */
    public $date_fin_validite;

    /** @var string */
    public $nom;

    /** @var string */
    public $nom_naissance;

    /** @var string */
    public $prenom_naissance;

    /** @var string */
    public $prenoms;

    /** @var string */
    public $prenom_usuel;

    /** @var string */
    public $date_naissance;

    /** @var string */
    public $date_naissance_corrigee;

    /** @var string */
    public $sexe;

    /** @var string */
    public $pays_naissance_insee;

    /** @var string */
    public $commune_naissance_insee;

    /** @var string */
    public $cp_naissance;

    /** @var string */
    public $debut;

    /** @var string */
    public $fin;

    /** @var string */
    public $_pays_naissance_insee;

    /** @var string */
    public $_lieu_naissance;

    /** @var string */
    public $_oid;

    /** @var string */
    public $_ins_type;

    /** @var string */
    public $_ins;

    /** @var string */
    public $_previous_ins;

    /** @var bool */
    public $_traits_stricts_readonly;

    /** @var CPatient */
    public $_ref_patient;

    /** @var CFile */
    public $_ref_justificatif;

    /** @var CPatientINSNIR */
    public $_ref_patient_ins_nir;

    /** @var CPatientINSNIR[] */
    public $_ref_patients_ins_nir;

    /** @var bool */
    public static $in_manage;

    /** @var bool */
    public static $update_patient_status = true;

    public $_no_synchro_eai = false;
    public $_generate_IPP   = true;

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec = parent::getSpec();

        $spec->table = 'source_identite';
        $spec->key   = 'source_identite_id';

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                            = parent::getProps();
        $props['patient_id']              = 'ref class|CPatient notNull back|sources_identite cascade';
        $props['active']                  = 'bool default|0';
        $props['mode_obtention']          = 'enum list|manuel|carte_vitale|insi|code_barre|rfid|import|interop notNull';
        $props['type_justificatif']       = 'enum list|passeport|carte_identite|acte_naissance|livret_famille|carte_sejour|doc_asile|carte_identite_electronique';
        $props['date_fin_validite']       = 'date';
        $props['nom']                     = 'str confidential';
        $props['nom_naissance']           = 'str notNull confidential';
        $props['prenom_naissance']        = 'str notNull';
        $props['prenoms']                 = 'str notNull';
        $props['prenom_usuel']            = 'str';
        $props['date_naissance']          = 'birthDate notNull';
        $props['date_naissance_corrigee'] = 'bool default|0';
        $props['sexe']                    = 'enum list|m|f|i';
        $props['pays_naissance_insee']    = 'numchar length|3';
        $props['commune_naissance_insee'] = 'str length|5';
        $props['cp_naissance']            = 'numchar length|5';
        $props['debut']                   = 'date';
        $props['fin']                     = 'date moreThan|debut';
        $props['_pays_naissance_insee']   = 'str';
        $props['_lieu_naissance']         = 'str';
        $props['_oid']                    = 'str';
        $props['_ins']                    = 'str';
        $props['_ins_type']               = 'str';
        $props['_previous_ins']           = 'str';

        return $props;
    }

    /**
     * @inheritDoc
     */
    public function updateFormFields()
    {
        parent::updateFormFields();

        $this->_view = $this->getFormattedValue('mode_obtention');

        if ($this->type_justificatif) {
            $this->_view .= ' (' . $this->getFormattedValue('type_justificatif') . ')';
        }

        $this->_traits_stricts_readonly = $this->mode_obtention === 'insi';
    }

    public function getNomPays(): ?string
    {
        return $this->_pays_naissance_insee = $this->pays_naissance_insee ?
            CPaysInsee::getNomFR($this->pays_naissance_insee) : null;
    }

    public function getNomCommune(): ?string
    {
        return $this->_lieu_naissance = $this->commune_naissance_insee ?
            (new CCommuneFrance())->loadByInsee($this->commune_naissance_insee)->commune : null;
    }

    public function updateLieuNaissance(): void
    {
        $this->getNomPays();
        $this->getNomCommune();
    }

    public function loadRefPatient(): CPatient
    {
        return $this->_ref_patient = $this->loadFwdRef('patient_id', true);
    }

    public function loadRefJustificatif(): CFile
    {
        return $this->_ref_justificatif = $this->loadUniqueBackRef('files');
    }

    public function updatePlainFields(): void
    {
        parent::updatePlainFields();

        $anonyme = is_numeric($this->nom);
        if ($this->nom) {
            $this->nom = CPatient::applyModeIdentitoVigilance($this->nom, false, null, $anonyme);
        }

        if ($this->nom_naissance) {
            $this->nom_naissance = CPatient::applyModeIdentitoVigilance($this->nom_naissance, false, null, $anonyme);
        }

        if ($this->prenom_usuel) {
            $this->prenom_usuel = CPatient::applyModeIdentitoVigilance($this->prenom_usuel, true, null, $anonyme);
        }

        if ($this->prenom_naissance) {
            $this->prenom_naissance = CPatient::applyModeIdentitoVigilance(
                $this->prenom_naissance,
                true,
                null,
                $anonyme
            );
        }

        if ($this->prenoms) {
            $prenoms = explode(' ', $this->prenoms);

            foreach ($prenoms as $_key => $_prenom) {
                $prenoms[$_key] = CPatient::applyModeIdentitoVigilance($_prenom, true, null, $anonyme);
            }

            $this->prenoms = implode(' ', $prenoms);
        }
    }

    /**
     * @inheritDoc
     */
    public function store(): ?string
    {
        foreach (self::TRAITS_STRICTS_REFERENCE as $_trait_source => $_trait_patient) {
            $this->completeField($_trait_source);
        }

        $create = !$this->_id;

        $this->completeField('patient_id', 'mode_obtention', 'prenoms', 'nom', 'nom_naissance', 'debut');

        // En attendant que le nom de naissance du patient soit correctement renseigné
        if ($this->nom && !$this->nom_naissance) {
            $this->nom_naissance = $this->nom;
        }

        // Ne pas permettre de créer une autre source insi avec le même nir et oid
        if ($create && $this->mode_obtention === 'insi' && $this->_oid && $this->_ins) {
            $patient_ins_nir = new CPatientINSNIR();
            $ds              = $this->getDS();

            $where = [
                'ins_nir' => $ds->prepare('= ?', $this->_ins),
                'oid'     => $ds->prepare('= ?', $this->_oid),
                'active'  => "= '1'",
            ];

            $ljoin = [
                'source_identite' => 'source_identite.source_identite_id = patient_ins_nir.source_identite_id',
            ];

            if ($patient_ins_nir->loadObject($where, null, null, $ljoin)) {
                return CAppUI::tr('CSourceIdentite-Cannot create duplicate source with same ins and oid');
            }
        }

        if ($this->_pays_naissance_insee) {
            $this->pays_naissance_insee = CPaysInsee::getPaysNumByNomFR($this->_pays_naissance_insee);
        }

        // Si pas de date de début et que l'on est sur une création la date de début de la source = date de création
        if (!$this->_id && !$this->debut) {
            $this->debut = 'now';
        }

        if ($msg = parent::store()) {
            return $msg;
        }

        if ($this->mode_obtention === 'insi' && $this->_oid && $this->_ins) {
            CPatientINSNIR::createUpdate(
                $this->patient_id,
                $this->nom_naissance,
                $this->prenom_naissance,
                $this->date_naissance,
                $this->_ins,
                'INSi',
                $this->_oid,
                $this->_ins_type === 'NIA',
                $this->_id
            );

            if ($this->_previous_ins) {
                foreach (json_decode($this->_previous_ins) as $_previous_ins) {
                    CPatientINSNIR::createUpdate(
                        $this->patient_id,
                        $this->nom_naissance,
                        $this->prenom_naissance,
                        $this->date_naissance,
                        $_previous_ins->ins,
                        'INSi',
                        $_previous_ins->oid,
                        false,
                        $this->_id,
                        true
                    );
                }
            }
        }

        if ($msg = $this->mapPatientFields()) {
            return $msg;
        }

        if (self::$update_patient_status) {
            $patient                       = $this->loadRefPatient();
            $patient->_ignore_eai_handlers = $this->_ignore_eai_handlers;
            $patient->_no_synchro_eai      = $this->_no_synchro_eai;
            $patient->_generate_IPP        = $this->_generate_IPP;

            return (new PatientStatus($patient))->updateStatus();
        }

        return null;
    }

    public function loadRefPatientINSNIR(): CPatientINSNIR
    {
        return $this->_ref_patient_ins_nir = $this->loadFirstBackRef('patient_ins_nir', 'patient_ins_nir_id');
    }

    public function loadRefsPatientsINSNIR(): array
    {
        $this->_ref_patients_ins_nir = $this->loadBackRefs('patient_ins_nir', 'patient_ins_nir_id');

        $this->loadRefPatientINSNIR();

        if (is_countable($this->_ref_patients_ins_nir)) {
            array_shift($this->_ref_patients_ins_nir);
        }

        return $this->_ref_patients_ins_nir;
    }

    public function mapFields(CPatient $patient): void
    {
        foreach (static::TRAITS_STRICTS_REFERENCE as $_trait_source => $_trait_patient) {
            if ($this->{$_trait_source}) {
                $patient->{$_trait_patient} = $this->{$_trait_source};
            }
        }
    }

    public function mapPatientFields(): ?string
    {
        $this->completeField('patient_id', ...array_keys(static::TRAITS_STRICTS_REFERENCE));

        $patient = $this->loadRefPatient();

        if (($patient->source_identite_id !== $this->_id) || !$this->active) {
            return null;
        }

        $field_changed = false;

        foreach (static::TRAITS_STRICTS_REFERENCE as $_trait_source => $_trait_patient) {
            if ($field_changed && ($patient->{$_trait_patient} !== $this->{$_trait_source})) {
                $field_changed = true;
            }
            if ($this->{$_trait_source}) {
                $patient->{$_trait_patient} = $this->{$_trait_source};
            }
        }

        $patient_ins_nir = $this->loadRefPatientINSNIR();

        if ($patient_ins_nir->_id && ($patient->matricule !== $patient_ins_nir->ins_nir)) {
            $patient->matricule = $patient_ins_nir->ins_nir;
            $field_changed      = true;
        }

        if ($field_changed) {
            return $patient->store();
        }

        return null;
    }

    public static function manageSource(CPatient $patient): ?string
    {
        if (self::$in_manage || !self::$update_patient_status) {
            return null;
        }

        self::$in_manage = true;

        // Recherche de la source d'identité en fonction du mode d'obtention du contexte patient
        $source_identite = new self();

        $source_identite->patient_id     = $patient->_id;
        $source_identite->mode_obtention = $patient->_force_manual_source ?
            'manuel' : ($patient->_vitale_lastname ? 'carte_vitale' : $patient->_mode_obtention);
        $source_identite->active         = '1';
        $source_identite->loadMatchingObject();

        // Copie des traits stricts
        foreach (static::TRAITS_STRICTS_REFERENCE as $_trait_source => $_trait_patient) {
            $_field_trait_patient = ($patient->_map_source_form_fields ? '_source_' : null) . $_trait_patient;
            if (!$patient->$_field_trait_patient) {
                continue;
            }
            $source_identite->{$_trait_source} = $patient->{$_field_trait_patient};
        }

        $source_identite->_oid                    = $patient->_oid;
        $source_identite->_ins                    = $patient->_ins;
        $source_identite->_ins_type               = $patient->_ins_type;
        $source_identite->date_naissance_corrigee = $patient->_source_naissance_corrigee;

        $source_identite->_ignore_eai_handlers = $patient->_ignore_eai_handlers;
        $source_identite->_generate_IPP        = $patient->_generate_IPP;
        $source_identite->_no_synchro_eai      = $patient->_no_synchro_eai;

        // Création ou mise à jour de la source
        if ($msg = $source_identite->store()) {
            self::$in_manage = true;

            return $msg;
        }

        // Ajout de la pièce justificative dans une nouvelle source
        if ($patient->_type_justificatif && count($_FILES) && isset($_FILES['formfile'])) {
            $source_identite                    = new self();
            $source_identite->patient_id        = $patient->_id;
            $source_identite->mode_obtention    = 'manuel';
            $source_identite->type_justificatif = $patient->_type_justificatif;
            $source_identite->active            = 1;

            foreach (self::TRAITS_STRICTS_REFERENCE as $_source_field => $_patient_field) {
                $_patient_field = "_source_{$_patient_field}";

                if (!isset($patient->$_patient_field) || !$patient->$_patient_field) {
                    continue;
                }

                $source_identite->$_source_field = $patient->$_patient_field;
            }

            $source_identite->date_fin_validite = $patient->_source__date_fin_validite;

            if ($msg = $source_identite->store()) {
                self::$in_manage = false;

                return $msg;
            }

            $file = new CFile();
            [$file->object_class, $file->object_id] = [$source_identite->_class, $source_identite->_id];
            $key_form_file   = isset($_FILES['formfile']['name'][1]) ? 1 : 0;
            $file->file_name = 'Paper.jpg';
            $file->file_type = $_FILES['formfile']['type'][$key_form_file];
            $file->author_id = CMediusers::get()->_id;
            $file->updateFormFields();
            $file->fillFields();
            $file->setContent(file_get_contents($_FILES['formfile']['tmp_name'][$key_form_file]));

            if ($msg = $file->store()) {
                self::$in_manage = false;

                return $msg;
            }
        }

        // Si le patient n'a pas de source d'identité ou celle que l'on crée est meilleure,
        // alors on l'associe au patient
        if (
            !$patient->source_identite_id
            || self::isBetterModeObtention(
                $patient,
                $source_identite->mode_obtention,
                $source_identite->type_justificatif
            )
        ) {
            $patient->source_identite_id = $source_identite->_id;

            if ($msg = $patient->store()) {
                self::$in_manage = false;

                return $msg;
            }
        }

        self::$in_manage = false;

        return null;
    }

    public static function isBetterModeObtention(
        CPatient $patient,
        string $new_mode_obtention,
        string $new_type_justificatif = null
    ): bool {
        $actual_source_identite = $patient->loadRefSourceIdentite();
        $actual_mode_obtention  = $actual_source_identite->mode_obtention;

        if ($patient->_force_manual_source) {
            return true;
        }

        switch ($actual_mode_obtention) {
            case 'manuel':
            case 'import':
            case 'interop':
                if ($new_mode_obtention === 'insi') {
                    return true;
                } elseif ($actual_source_identite->type_justificatif) {
                    return false;
                } elseif (in_array($new_mode_obtention, ['manuel', 'import', 'interop']) && $new_type_justificatif) {
                    return true;
                } elseif ($new_mode_obtention === 'carte_vitale') {
                    return true;
                }

                return false;

            case 'carte_vitale':
                if ($new_mode_obtention === 'insi') {
                    return true;
                } elseif (in_array($new_mode_obtention, ['manuel', 'import', 'interop']) && $new_type_justificatif) {
                    return true;
                }

                return false;

            default:
                return false;
        }
    }
}
