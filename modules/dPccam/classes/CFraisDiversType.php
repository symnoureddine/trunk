<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\CMbObject;

/**
 * Type de frais divers
 */
class CFraisDiversType extends CMbObject {
  public $frais_divers_type_id;

  // DB fields
  public $code;
  public $libelle;
  public $tarif;
  public $facturable;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "frais_divers_type";
    $spec->key   = "frais_divers_type_id";
    $spec->uniques["code"] = array("code");
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["code"]        = "str notNull maxLength|16";
    $props["libelle"]     = "str notNull";
    $props["tarif"]       = "currency notNull";
    $props["facturable"]  = "bool notNull default|0";
    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = "$this->libelle ($this->code)";
  }
}
