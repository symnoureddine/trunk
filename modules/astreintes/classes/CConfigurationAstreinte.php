<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationAstreinte
 */
class CConfigurationAstreinte extends AbstractConfigurationRegister {

  /**
   * @return mixed
   */
  public function register() {
    $configs = array(
      "CGroups" => array(
        "astreintes" => array(
          "General" => array(
            "astreinte_admin_color"             => "color",
            "astreinte_informatique_color"      => "color",
            "astreinte_medical_color"           => "color",
            "astreinte_personnelsoignant_color" => "color",
            "astreinte_technique_color"         => "color"
          ),
        ),
      ),
    );

    CConfiguration::register($configs);
  }
}

