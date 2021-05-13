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
 * Question du motifs
 */
class CMotifQuestion extends CMbObject {
  public $question_id;

  // DB Fields
  public $motif_id;

  // Form fields
  public $degre;
  public $nom;
  public $actif;
  public $num_group;

  /** @var CMotif */
  public $_ref_motif;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'motif_question';
    $spec->key   = 'question_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props              = parent::getProps();
    $props["motif_id"]  = "ref class|CMotif notNull back|questions";
    $props["degre"]     = "num notNull min|1 max|4";
    $props["nom"]       = "text notNull";
    $props["actif"]     = "bool default|1";
    $props["num_group"] = "num";

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->nom;
  }

  /**
   * Chargement du motif de la question
   *
   * @param bool $cache cache
   *
   * @return CMotif
   */
  function loadRefMotif($cache = true) {
    return $this->_ref_motif = $this->loadFwdRef("motif_id", $cache);
  }
}
