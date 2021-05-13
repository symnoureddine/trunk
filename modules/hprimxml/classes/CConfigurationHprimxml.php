<?php
/**
 * @package Mediboard\Hprimxml
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hprimxml;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationHprimxml
 */
class CConfigurationHprimxml extends AbstractConfigurationRegister {

  /**
   * @return void
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "hprimxml" => array(
            "CHPrimXMLDocument" => array(
              "emetteur_application_code"    => "str default|Mediboard",
              "emetteur_application_libelle" => "str default|Mediboard SIH",
            )
          )
        )
      )
    );
  }
}