<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 */
class CPatientGenerator extends CObjectGenerator {
  static $mb_class = CPatient::class;
  static $ds = array(
    "INSEE" => array('cp', 'ville')
  );

  /** @var CPatient  */
  protected $object;
  protected $codes_used = array();

  /**
   * Generate a CPatient
   *
   * @return CPatient
   * @throws Exception
   */
  function generate() {
    if (!$this->force && (rand(0, 100) > CAppUI::conf('populate CPatient_pct_create'))) {
      $where          = array('deces' => 'IS NULL');
      $count_patients = $this->object->countList($where);

      if ($count_patients) {
        $this->object = $this->getRandomObject($count_patients, $where);
        $this->trace(static::TRACE_LOAD);

        return $this->object;
      }
    }

    $where = array(
      "sex" => "!= 'u'"
    );

    $names                = $this->getRandomNames(2, $where);
    $this->object->nom    = $names[0]->firstname;
    $this->object->prenom = $names[1]->firstname;
    $this->object->sexe   = $names[0]->sex;

    $this->object->civilite = "guess";

    $cp_commune          = $this->getCommune();
    $this->object->cp    = $cp_commune['code_postal'];
    $this->object->ville = $cp_commune['commune'];

    if ($this->object->_specs['adresse']->notNull) {
      $this->object->adresse = 'sample adresse';
    }

    if ($this->object->_specs['tel']->notNull) {
      $this->object->tel = '0505050505';
    }

    $this->object->pays = 'France';

    $start_date              = '-' . CAppUI::conf("populate CPatient_max_years_old") . ' years';
    $end_date                = '-' . CAppUI::conf("populate CPatient_age_min") . ' years';
    $this->object->naissance = CMbDT::getRandomDate($start_date, $end_date, 'Y-m-d');

    if ($msg = $this->object->store()) {
      dump($msg);
      if (PHP_SAPI !== 'cli') {
        CAppUI::stepAjax($msg, UI_MSG_ERROR);
      }
    }
    else {
      CAppUI::setMsg("CPatient-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE);
    }

    if (!$this->force) {
      $min_atcd = CAppUI::conf('populate CPatient_atcd_min_count');
      $max_atcd = CAppUI::conf('populate CPatient_atcd_max_count');
      $min_atcd = ($min_atcd <= $max_atcd) ? $min_atcd : $max_atcd;

      $atcd_count = rand($min_atcd, $max_atcd);
      if ($atcd_count > 0) {
        $dossier_medical_id = CDossierMedical::dossierMedicalId($this->object->_id, $this->object->_class);

        for ($i = 0; $i < $atcd_count; $i++) {
          $this->createAtcd($dossier_medical_id);
        }
      }
    }

    return $this->object;
  }

  /**
   * Create an ATCD
   *
   * @param int $dossier_medical_id Patient's CDossierMedical->_id
   *
   * @return void
   * @throws Exception
   */
  protected function createAtcd($dossier_medical_id) {
    $atcd                     = new CAntecedent();
    $atcd->dossier_medical_id = $dossier_medical_id;
    $code_cim = $this->getRandomCIM10Code($this->codes_used);
    $atcd->rques              = $code_cim['code'] . ' : ' . $code_cim['libelle'];
    $this->codes_used[]       = $code_cim['code'];

    if ($msg = $atcd->store()) {
      CAppUI::stepAjax($msg, UI_MSG_ERROR);
    }
    else {
      CAppUI::setMsg("CAntecedent-msg-create", UI_MSG_OK);
    }

    $this->trace(static::TRACE_STORE, $atcd);
  }
}
