<?php
/**
 * @package Mediboard\BloodSalvage
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\BloodSalvage;

use Ox\Core\CMbObject;

/**
 * CCellSaver
 */
class CCellSaver extends CMbObject {
  public $cell_saver_id;

  //DB Fields
  public $marque;
  public $modele;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'cell_saver';
    $spec->key   = 'cell_saver_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props           = parent::getProps();
    $props["marque"] = "str notNull maxLength|50";
    $props["modele"] = "str notNull maxLength|50";

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = "$this->marque $this->modele";
  }
}
