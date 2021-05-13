<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ihe;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationIhe
 */
class CConfigurationIhe extends AbstractConfigurationRegister {

  /**
   * @return void
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "ihe" => array(
            "ITI" => array(
              "outpatient_sejour_type"  => "str default|urg|exte",
              "hospi_de_jour"           => "enum list|normal|A01/A03 default|normal",
              "fields_generate_A01_A04" => "str default|entree_prevue|entree_reelle|mode_entree|entree_preparee|entree_modifiee|mode_entree_id|date_entree_reelle_provenance|provenance|type|charge_id",
            ),
            "RAD" => array(
              "send_order_by_consult"      => "bool default|0",
              "send_order_by_prescription" => "bool default|0",
            ),
          ),
        ),
      )
    );
  }
}