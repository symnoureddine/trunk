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
 * Constante de l'échelle de tri
 */
class CEchelleTriConstante extends CMbObject {
  public $echelle_cte_id;

  // DB Fields
  public $rpu_id;

  // Form fields
  public $degre;
  public $name;
  public $value;
  public $unit;

  /** @var CRPU */
  public $_ref_rpu;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'echelle_tri_cte';
    $spec->key   = 'echelle_cte_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props           = parent::getProps();
    $props["rpu_id"] = "ref class|CRPU notNull cascade back|constantes_rpu";
    $props["degre"]  = "enum list|1|2|3|4";
    $props["name"]   = "str";
    $props["value"]  = "str";
    $props["unit"]   = "str";

    return $props;
  }

  /**
   * Chargement du RPU
   *
   * @return CRPU
   */
  function loadRefRPU() {
    return $this->_ref_rpu = $this->loadFwdRef("rpu_id", true);
  }
}