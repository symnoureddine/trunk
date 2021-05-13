<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Urgences;

use Ox\Core\CMbObject;

/**
 * Réponse aux questions
 */
class CMotifReponse extends CMbObject {
  public $reponse_id;

  // DB Fields
  public $rpu_id;
  public $question_id;

  // Form fields
  public $result;

  /** @var CMotifQuestion */
  public $_ref_question;
  /** @var CRPU */
  public $_ref_rpu;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'motif_reponse';
    $spec->key   = 'reponse_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props                = parent::getProps();
    $props["rpu_id"]      = "ref class|CRPU notNull back|reponses_rpu";
    $props["question_id"] = "ref class|CMotifQuestion notNull back|reponses";
    $props["result"]      = "bool";

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    if (!$this->_ref_question) {
      $this->loadRefQuestion();
    }
    $this->_view = $this->_ref_question->nom;
  }

  /**
   * Chargement de la question
   *
   * @param bool $cache cache
   *
   * @return CMotifQuestion
   */
  function loadRefQuestion($cache = true) {
    $this->_ref_question = $this->loadFwdRef("question_id", $cache);

    return $this->_ref_question;
  }

  /**
   * Chargement du RPU
   *
   * @return CRPU
   */
  function loadRefRPU() {
    return $this->_ref_rpu = $this->loadFwdRef("rpu_id");
  }

  /**
   * @see parent::store()
   */
  function store() {
    // Standard Store
    if ($msg = parent::store()) {
      return $msg;
    }

    if ($this->_id) {
      $this->loadRefRpu()->majCCMU();
    }

    return null;
  }
}