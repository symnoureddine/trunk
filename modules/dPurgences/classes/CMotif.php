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
 * Motif de l'urgence
 */
class CMotif extends CMbObject {
  public $motif_id;

  // DB Fields
  public $chapitre_id;

  // Form fields
  public $nom;
  public $code_diag;
  public $degre_min;
  public $degre_max;
  public $definition;
  public $observations;
  public $param_vitaux;
  public $recommande;
  public $actif;

  /** @var CChapitreMotif */
  public $_ref_chapitre;

  /** @var CMotifQuestion[] */
  public $_ref_questions;
  /** @var array */
  public $_ref_questions_by_group;

  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'motif_urgence';
    $spec->key   = 'motif_id';

    return $spec;
  }

  function getProps() {
    $props                 = parent::getProps();
    $props["chapitre_id"]  = "ref class|CChapitreMotif notNull back|motif";
    $props["nom"]          = "text notNull";
    $props["code_diag"]    = "num notNull";
    $props["degre_min"]    = "num notNull min|1 max|4";
    $props["degre_max"]    = "num notNull min|1 max|4";
    $props["definition"]   = "text";
    $props["observations"] = "text";
    $props["param_vitaux"] = "text";
    $props["recommande"]   = "text";
    $props["actif"]        = "bool default|1";

    return $props;
  }

  /**
   * updateFormFields
   *
   * @return void
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->nom;
  }

  /**
   * Chargement des motifs du chapitre
   *
   * @param bool $cache cache
   *
   * @return CChapitreMotif
   */
  function loadRefChapitre($cache = true) {
    return $this->_ref_chapitre = $this->loadFwdRef("chapitre_id", $cache);
  }

  /**
   * Chargement des questions du motif
   *
   * @param bool $actif uniquement les actifs
   *
   * @return CMotifQuestion
   */
  function loadRefsQuestions($actif = false) {
    $where = array();
    if ($actif) {
      $where["actif"] = " = '1'";
    }

    return $this->_ref_questions = $this->loadBackRefs("questions", 'num_group ASC, degre ASC', null, null, null, null, null, $where);
  }

  /**
   * Classement des questions du motif par groupe
   *
   * @return array
   */
  function loadRefsQuestionsByGroup() {
    $this->_ref_questions_by_group = array();

    $question_by_num_group = array();
    foreach ($this->_ref_questions as $question) {
      if ($question->actif) {
        $question_by_num_group[$question->num_group ? $question->num_group : 0][] = $question;
      }
    }
    foreach ($question_by_num_group as $num_group => $questions) {
      $name_group = "";
      $last_degre = 0;
      foreach ($questions as $_question) {
        if (!$_question->num_group) {
          $this->_ref_questions_by_group[$_question->degre][] = $_question;
        }
        elseif ($_question->degre != $last_degre) {
          if ($name_group) {
            $name_group .= "-";
          }
          $name_group .= $_question->degre;
          $last_degre = $_question->degre;
        }
      }
      if ($name_group) {
        $this->_ref_questions_by_group[$name_group] = $questions;
      }
    }

    return $this->_ref_questions_by_group;
  }
}
