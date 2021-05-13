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
 * Chapitre du motif d'urgence
 */
class CChapitreMotif extends CMbObject {
  public $chapitre_id;

  public $nom;

  /** @var CMotif[] */
  public $_ref_motifs;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'motif_chapitre';
    $spec->key   = 'chapitre_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props        = parent::getProps();
    $props["nom"] = "str";

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
   * Chargement des motifs du chapitre
   *
   * @param bool $actif uniquement les actifs
   *
   * @return CMotif[]
   */
  function loadRefsMotifs($actif = false) {
    $motif                = new CMotif();
    $where                = array();
    $where["chapitre_id"] = " = '$this->_id'";
    if ($actif) {
      $where["actif"] = " = '1'";
    }
    $order = "code_diag";

    return $this->_ref_motifs = $motif->loadList($where, $order);
  }
}
