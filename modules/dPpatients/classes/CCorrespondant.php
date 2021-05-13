<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CMbObject;
use Ox\Import\Framework\ImportableInterface;
use Ox\Import\Framework\Matcher\MatcherVisitorInterface;
use Ox\Import\Framework\Persister\PersisterVisitorInterface;

/**
 * Liaison entre le médecin et le patient
 */
class CCorrespondant extends CMbObject implements ImportableInterface {

  // DB Table key
  public $correspondant_id;

  // DB Fields
  public $medecin_id;
  public $patient_id;

  /** @var CMedecin */
  public $_ref_medecin;

  /** @var CPatient */
  public $_ref_patient;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "correspondant";
    $spec->key   = "correspondant_id";

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props               = parent::getProps();
    $props["medecin_id"] = "ref notNull class|CMedecin back|patients_correspondants";
    $props["patient_id"] = "ref notNull class|CPatient back|correspondants";

    return $props;
  }

    /**
     * @inheritdoc
     */
    function updateFormFields() {
        parent::updateFormFields();

        $medecin = $this->loadRefMedecin();

        $this->_view = $medecin->_view;
    }

  /**
   * @see parent::loadRefsFwd()
   */
  function loadRefsFwd() {
    $this->loadRefPatient();
    $this->loadRefMedecin();
  }

  /**
   * Charge le patient
   *
   * @return CPatient
   * @throws \Exception
   */
  function loadRefPatient() {
    return $this->_ref_patient = $this->loadFwdRef("patient_id");
  }

  /**
   * Charge le médecin
   *
   * @return CMedecin
   * @throws \Exception
   */
  function loadRefMedecin() {
    return $this->_ref_medecin = $this->loadFwdRef("medecin_id");
  }

    public function matchForImport(MatcherVisitorInterface $matcher): ImportableInterface
    {
        return $matcher->matchCorrespondant($this);
    }

    public function persistForImport(PersisterVisitorInterface $persister): ImportableInterface
    {
        return $persister->persistObject($this);
    }
}
