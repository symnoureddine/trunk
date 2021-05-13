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
use Ox\Core\CMbObject;
use Ox\Core\CMbString;

/**
 * Description
 */
class CPatientINSNIR extends CMbObject
{
    private const OID_INS_NIR = '1.2.250.1.213.1.4.8';
    private const OID_INS_NIA = '1.2.250.1.213.1.4.9';

    /**
     * @var integer Primary key
     */
    public $patient_ins_nir_id;

    public $created_datetime;
    public $last_update;
    public $patient_id;
    public $ins_nir;
    public $oid;
    public $is_nia;
    public $source_identite_id;
    public $name;
    public $firstname;
    public $birthdate;
    public $provider;

    public $_is_ins_nir = false;
    public $_is_ins_nia = false;

    /** @var CSourceIdentite */
    public $_ref_source_identite;

    /**
     * @param $patient_id
     *
     * Vérifie les données d'un patient qui est supposé correspondre aux données retournées par la TD0.0
     *
     * @return bool|null
     * @throws Exception
     */
    public function compare($patient_id)
    {
        $patient = new CPatient();
        $patient->load($patient_id);
        if (!$patient) {
            return true;
        }

        return (CMbString::lower($this->name) != CMbString::lower($patient->_nom_naissance)
            || CMbString::lower($this->firstname) != CMbString::lower($patient->prenom)
            || $this->birthdate != $patient->naissance
            || $this->ins_nir != $patient->matricule);
    }

    /**
     * @inheritDoc
     */
    public function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'patient_ins_nir';
        $spec->key   = 'patient_ins_nir_id';

        return $spec;
    }

    /**
     * @inheritDoc
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props['patient_id']         = 'ref class|CPatient notNull back|patient_ins_nir';
        $props['created_datetime']   = 'dateTime notNull';
        $props['last_update']        = 'dateTime notNull';
        $props['ins_nir']            = 'str notNull';
        $props['oid']                = 'str';
        $props['is_nia']             = 'bool default|0';
        $props['source_identite_id'] = 'ref class|CSourceIdentite back|patient_ins_nir cascade';
        $props['name']               = 'str';
        $props['firstname']          = 'str';
        $props['birthdate']          = 'birthDate';
        $props['provider']           = 'str notNull';

        return $props;
    }

    /**
     * @inheritdoc
     */
    public function store()
    {
        if (!$this->_id) {
            $this->created_datetime = $this->last_update = 'now';
        }

        if ($this->objectModified()) {
            $this->last_update = 'now';
        }

        return parent::store();
    }

    /**
     * @inheritdoc
     */
    public function updateFormFields()
    {
        parent::updateFormFields();

        if ($this->oid === self::OID_INS_NIA) {
            $this->_is_ins_nia = true;
        }

        if ($this->oid === self::OID_INS_NIR) {
            $this->_is_ins_nir = true;
        }
    }

    public function loadRefSourceIdentite(): CSourceIdentite
    {
        return $this->_ref_source_identite = $this->loadFwdRef('source_identite_id', true);
    }

    /**
     * Create or update Patient INS NIR
     *
     * @param int    $patient_id
     * @param string $name
     * @param string $first_name
     * @param string $birth_date
     * @param string $ins_nir
     * @param string $provider
     * @param string $oid
     * @param int    $source_identite_id
     *
     * @return string|null
     * @throws Exception
     */
    public static function createUpdate(
        $patient_id,
        $name,
        $first_name,
        $birth_date,
        $ins_nir,
        $provider,
        $oid = null,
        $is_nia = false,
        $source_identite_id = null,
        $force_new = false
    ) {
        $patient_ins_nir             = new self();
        $patient_ins_nir->patient_id = $patient_id;

        if (!$force_new) {
            $patient_ins_nir->loadMatchingObject();
        }

        $patient_ins_nir->provider           = $provider;
        $patient_ins_nir->ins_nir            = $ins_nir;
        $patient_ins_nir->name               = $name;
        $patient_ins_nir->firstname          = $first_name;
        $patient_ins_nir->birthdate          = $birth_date;
        $patient_ins_nir->oid                = $oid;
        $patient_ins_nir->is_nia             = $is_nia;
        $patient_ins_nir->source_identite_id = $source_identite_id;

        if ($msg = $patient_ins_nir->store()) {
            CAppUI::stepAjax($msg, UI_MSG_ERROR);
        }

        return $patient_ins_nir;
    }
}
