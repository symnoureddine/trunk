<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationHl7
 */
class CConfigurationHl7 extends AbstractConfigurationRegister {
  /**
   * @inheritDoc
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "hl7" => array(
            "CHL7" => array(
              "sending_application"                   => "str",
              "sending_facility"                      => "str",
              "assigning_authority_namespace_id"      => "str",
              "assigning_authority_universal_id"      => "str",
              "assigning_authority_universal_type_id" => "str",
            ),
            "ORU"  => array(
              "handle_file_name" => "str",
            ),
          ),
        ),
      )
    );
  }
}