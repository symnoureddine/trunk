<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr;

use Ox\Mediboard\Tarmed\CActeTarmed;
use Ox\Mediboard\Tarmed\CTarmed;

/**
 * Classe d'association entre éléments de prescription et les actes Tarmed
 */
class CElementPrescriptionToTarmed extends CElementPrescriptionToReeducation {
  // DB Table key
  public $element_prescription_to_tarmed_id;

  public $_ref_tarmed = array();

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'element_prescription_to_tarmed';
    $spec->key   = 'element_prescription_to_tarmed_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props         = parent::getProps();
    $props["element_prescription_id"] .= " back|tarmeds";
    $props["code"] = "str maxLength|10 notNull";

    return $props;
  }

  /**
   * Charge l'acte tarmed associée
   *
   * @return CActeTarmed
   */
  function loadRefTarmed() {
    $tarmed = CTarmed::get($this->code);

    return $this->_ref_tarmed = $tarmed;
  }

  /**
   * @see parent::loadView()
   */
  function loadView() {
    parent::loadView();
    $this->loadRefTarmed();
  }
}
