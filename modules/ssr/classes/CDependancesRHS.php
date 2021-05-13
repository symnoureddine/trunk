<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr;

use Ox\Core\CMbObject;

/**
 * Ligne de dépendance RHS
 */
class CDependancesRHS extends CMbObject {
  // DB Table key
  public $dependances_id;

  // DB Fields
  public $rhs_id;

  public $habillage;
  public $deplacement;
  public $alimentation;
  public $continence;
  public $comportement;
  public $relation;

  // References
  public $_ref_rhs;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'dependances_rhs';
    $spec->key   = 'dependances_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    // DB Fields
    $props["rhs_id"] = "ref notNull class|CRHS back|dependances";

    $degre                 = "enum list|1|2|3|4";
    $props["habillage"]    = $degre;
    $props["deplacement"]  = $degre;
    $props["alimentation"] = $degre;
    $props["continence"]   = $degre;
    $props["comportement"] = $degre;
    $props["relation"]     = $degre;

    return $props;
  }

  /**
   * Charge le RHS parent
   *
   * @return CRHS
   */
  function loadRefRHS() {
    return $this->_ref_rhs = $this->loadFwdRef("rhs_id");
  }
}
