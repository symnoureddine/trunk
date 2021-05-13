<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet;

use Ox\Core\CMbObject;

/**
 * Examens complémentaires prévus pendant la consultation, en particulier pour un anesthésiste
 */
class CExamComp extends CMbObject {
  public $exam_id;

  // DB References
  public $consultation_id;

  // DB fields
  public $examen;
  public $realisation;
  public $fait;
  public $date_bilan;
  public $labo;

  /** @var CConsultation */
  public $_ref_consult;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'exams_comp';
    $spec->key   = 'exam_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["consultation_id"] = "ref notNull class|CConsultation back|examcomp";
    $props["examen"]          = "text helped";
    $props["realisation"]     = "enum notNull list|avant|pendant";
    $props["fait"]            = "num min|0 max|1";
    $props["date_bilan"]      = "date";
    $props["labo"]            = "text helped";
    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->examen;
  }

  /**
   * Charge la consultation associée
   *
   * @return CConsultation
   */
  function loadRefConsult() {
    return $this->_ref_consult = $this->loadFwdRef("consultation_id", true);
  }

  /**
   * @see parent::getPerm()
   */
  function getPerm($permType) {
    return $this->loadRefConsult()->getPerm($permType);
  }
}
