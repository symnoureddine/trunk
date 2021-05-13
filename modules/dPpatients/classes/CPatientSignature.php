<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CRequest;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Description
 */
class CPatientSignature extends CMbObject
{
    /**
     * @var integer Primary key
     */
    public $patient_signature_id;
    public $patient_id;
    public $signature;

    /** @var  CPatient */
    public $_ref_patient;

    public static $fields = [
        "prenom",
        "naissance",
        "nom",
        "nom_jeune_fille",
    ];

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = "patient_signature";
        $spec->key      = "patient_signature_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props               = parent::getProps();
        $props["patient_id"] = "ref class|CPatient notNull cascade back|signatures";
        $props["signature"]  = "str notNull";

        return $props;
    }

    /**
     * Load the ref patient
     *
     * @return CPatient
     * @throws Exception
     */
    function loadRefPatient()
    {
        return $this->_ref_patient = $this->loadFwdRef("patient_id");
    }

    /**
     * R�cup�re tous les patients homonymes entre $start et $step tri�s par ordre alphab�tique
     *
     * @param int  $start Indice de d�but du tableau de retour
     * @param int  $step  Nombre d'�l�ments dans le tableau retourn�
     * @param bool $all   Faut-il renvoyer tous les homonymes ou non
     *
     * @return array
     * @throws Exception
     */
    function findHomonymes($start = 0, $step = 20, $all = false)
    {
        $ds = $this->getDS();

        $query = new CRequest();
        $query->addTable('patient_link');
        $query->addWhere(['type' => '= "HOMA"']);
        $count = $ds->loadResult($query->makeSelectCount());

        // Reinitialize select because of count(*)
        $query->select = [];
        $query->addSelect(['patient_id1', 'patient_id2']);
        $res = $ds->loadList($query->makeSelect());

        $patients = [];
        if (!$all) {
            foreach ($res as $_link) {
                $patient_1 = new CPatient();
                $patient_2 = new CPatient();
                $patient_1->load($_link['patient_id1']);
                $patient_2->load($_link['patient_id2']);
                $patients["{$patient_1->nom}-{$patient_1->prenom}-{$patient_1->_id}-{$patient_2->_id}"] = [
                    'patient_1' => $patient_1,
                    'patient_2' => $patient_2,
                ];
            }

            ksort($patients);

            return [
                'count_homonymes' => count($patients),
                'patients'        => array_slice($patients, $start, $step),
            ];
        }

        foreach ($res as $_link) {
            $patients["{$_link['patient_id1']}-{$_link['patient_id2']}"] = true;
        }

        return
            [
                $patients,
                $count,
            ];
    }

    /**
     * R�cup�re tous les doublons potentiels (en ignorant les patients homonymes)
     *
     * @param int $start Index � partir duquel commence le tableau retourn�
     * @param int $step  Nombre d'�l�ments dans le tableau retourn�
     *
     * @return array
     * @throws Exception
     */
    function findDuplicates($start = 0, $step = 20)
    {
        $result = $this->loadDuplicateSignatures();

        $count_duplicates = count($result);

        // TODO Remove homonymes

        $result = array_slice($result, $start, $step);

        $result = array_map(
            function ($elt) {
                $patient_ids = explode(',', $elt['patient_ids']);

                return [
                    'signature'   => $elt['signature'],
                    'patient_ids' => array_unique($patient_ids),
                ];
            },
            $result
        );

        $duplicates = [];
        foreach ($result as $_sign) {
            $pat_load   = new CPatient();
            $pats_array = $pat_load->loadAll($_sign['patient_ids']);

            foreach ($pats_array as $_patient) {
                $_patient->_ref_creation_log = $_patient->loadCreationLog();
            }

            $first_pat = reset($pats_array);

            $duplicates[] = [
                'signature' => $_sign['signature'],
                'naissance' => ($first_pat) ? $first_pat->naissance : '',
                'ids'       => $_sign['patient_ids'],
                'patients'  => $pats_array,
            ];
        }

        $duplicates_return                     = [];
        $duplicates_return['count_duplicates'] = $count_duplicates;
        $duplicates_return['duplicates']       = $duplicates;


        return $duplicates_return;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function loadDuplicateSignatures(): array
    {
        // R�cup�ration de la liste des signatures qui existent au moins en double dans la base
        $ds    = $this->getDS();
        $query = new CRequest();
        $query->addSelect(['S1.signature', 'GROUP_CONCAT(CONCAT(S1.patient_id, ",", S2.patient_id)) as patient_ids']);
        $query->addTable(['patient_signature S1', 'patient_signature S2']);
        $query->addWhere(
            [
                'S1.signature'            => '= S2.signature',
                'S1.patient_id'           => '!= S2.patient_id',
                'S1.patient_signature_id' => '!= S2.patient_signature_id',
            ]
        );
        $query->addGroup(['S1.signature']);
        $query->addOrder('S1.signature ASC');

        // TODO Set cache for 1h
        return $ds->loadList($query->makeSelect());
    }

    /**
     * Export duplicates (CSV format)
     *
     * @return void
     * @throws Exception
     */
    public function exportDuplicates()
    {
        $lignes_signatures = $this->loadDuplicateSignatures();

        $export = new CCSVFile(null, CCSVFile::PROFILE_OPENOFFICE);
        $export->setColumnNames(['signature', 'patient', 'naissance', 'date', 'auteur']);
        $export->writeLine(['signature', 'patient', 'naissance', 'date', 'auteur']);

        foreach ($lignes_signatures as $_sign) {
            $pat_load = new CPatient();
            $ids      = explode(',', $_sign['patient_ids']);

            // Suppression des doublons d'ids
            $ids = array_unique($ids, SORT_NUMERIC);
            $ids = array_values($ids);

            $pats_array = $pat_load->loadAll($ids);

            foreach ($pats_array as $_patient) {
                $_ref_creation_log = $_patient->loadCreationLog();

                $export->writeLine(
                    [
                        'signature' => $_sign['signature'],
                        'patient'   => $_patient->_view,
                        'naissance' => $_patient->naissance,
                        'date'      => $_ref_creation_log->date,
                        'auteur'    => ($_ref_creation_log->user_id) ? CMediusers::get(
                            $_ref_creation_log->user_id
                        )->_view : null,
                    ]
                );
            }
        }

        $export->stream('doublons_patients', true);
    }

    /**
     * Ajoute un patient � la table des signatures
     *
     * @param int $patient_id Identifiant du patient pour lequel on va ajouter une signature
     *
     * @return bool
     * @throws Exception
     */
    static function addPatientSignature($patient_id)
    {
        $patient = new CPatient();
        $patient->load($patient_id);
        if (!$patient->_id) {
            return false;
        }

        $signature             = new self();
        $signature->patient_id = $patient_id;
        $signature->signature  = self::createSignature($patient);
        if ($msg = $signature->store()) {
            CAppUI::setMsg($msg, UI_MSG_WARNING);

            return false;
        }

        if ($patient->nom_jeune_fille && $patient->nom_jeune_fille != $patient->nom) {
            $signature             = new self();
            $signature->patient_id = $patient_id;
            $signature->signature  = self::createSignature($patient, true);
            if ($msg = $signature->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);

                return false;
            }
        }

        return true;
    }

    /**
     * Cr�e une signature pour le patient donn� en param�tre � partir de sa date de naissance, de son nom, de son pr�nom
     *
     * @param CPatient $patient Patient dont on veut cr�er la signature
     * @param bool     $nom_jf  Faut-il utiliser le nom ou le nom de jeune fille pour la signature
     *
     * @return string
     */
    static function createSignature(CPatient $patient, $nom_jf = false)
    {
        $regexp = "/[\s'-]+/";

        $sign_naissance = preg_replace($regexp, "", $patient->naissance);
        $sign_nom       = preg_replace($regexp, "", $patient->nom);
        if ($nom_jf && $patient->nom_jeune_fille != $patient->nom) {
            $sign_nom = preg_replace($regexp, "", $patient->nom_jeune_fille);
        }

        $sign_prenom = preg_replace($regexp, "", $patient->prenom);

        return CMbString::removeDiacritics(
            CMbString::lower(sprintf("%s_%s_%s", $sign_nom, $sign_prenom, $sign_naissance))
        );
    }

    /**
     * @param string $nom       Le nom ou le nom de jeune fille � utiliser pour la signature
     * @param string $prenom    Le pr�nom � utiliser pour la signature
     * @param string $naissance La date de naissance � utiliser pour la signature
     *
     * @return string
     */
    static function createSignatureFromInfos($nom, $prenom, $naissance)
    {
        $regexp = "/[\s'-]+/";

        $sign_naissance = preg_replace($regexp, "", $naissance);
        $sign_nom       = preg_replace($regexp, "", $nom);
        $sign_prenom    = preg_replace($regexp, "", $prenom);

        return CMbString::removeDiacritics(
            CMbString::lower(sprintf("%s_%s_%s", $sign_nom, $sign_prenom, $sign_naissance))
        );
    }

    /**
     * @param CPatient $patient    Le patient qui a �t� modifi�
     * @param array    $old_fields Les champs qui ont �t� modifi�s
     *
     * @return bool
     * @throws Exception
     */
    static function updatePatientSignature(CPatient $patient, $old_fields)
    {
        if ($old_fields["nom"] != $patient->nom || $old_fields["prenom"] != $patient->prenom
            || $old_fields["naissance"] != $patient->naissance
        ) {
            $signature             = new CPatientSignature();
            $signature->patient_id = $patient->_id;
            $signature->signature  = self::createSignatureFromInfos(
                $old_fields['nom'],
                $old_fields['prenom'],
                $old_fields['naissance']
            );
            $signature->loadMatchingObjectEsc();

            $signature->signature = self::createSignatureFromInfos(
                $patient->nom,
                $patient->prenom,
                $patient->naissance
            );
            $signature->store();
        }

        if ($patient->nom_jeune_fille
            && ($old_fields["nom_jeune_fille"] != $patient->nom_jeune_fille || $old_fields["prenom"] != $patient->prenom
                || $old_fields["naissance"] != $patient->naissance)
        ) {
            $signature             = new CPatientSignature();
            $signature->patient_id = $patient->_id;
            $signature->signature  = self::createSignatureFromInfos(
                $old_fields["nom_jeune_fille"],
                $old_fields["prenom"],
                $old_fields["naissance"]
            );
            $signature->loadMatchingObjectEsc();

            $signature->signature = self::createSignatureFromInfos(
                $patient->nom_jeune_fille,
                $patient->prenom,
                $patient->naissance
            );
            $signature->store();
        }
        // Suppression du NJF
        if (!$patient->nom_jeune_fille && $old_fields["nom_jeune_fille"] != "") {
            $signature             = new CPatientSignature();
            $signature->patient_id = $patient->_id;
            $signature->signature  = self::createSignatureFromInfos(
                $old_fields["nom_jeune_fille"],
                $old_fields["prenom"],
                $old_fields["naissance"]
            );
            $signature->loadMatchingObjectEsc();
            if ($signature->_id) {
                $signature->delete();
            }
        }

        return true;
    }

    public function deleteOldSignatures(): void
    {
        $old_signature_ids = $this->loadIds(
            ['patients.patient_id IS NULL'],
            null,
            null,
            null,
            ['patients ON (patient_signature.patient_id = patients.patient_id)']
        );

        if ($old_signature_ids) {
            CView::disableSlave();
            $this->deleteAll($old_signature_ids);
            CView::enforceSlave();
        }
    }
}
