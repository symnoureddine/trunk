<?php
/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\NovxtelHospitality;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Novxtel - Hospitality
 */
class CConfigurationNovxtelHospitality extends AbstractConfigurationRegister {
	
  /**
   * @return mixed
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "novxtelHospitality" => array(
            "General" => array(
              "show_button_iframe" => "bool default|1"
            )
          )
        )
      )
    );
  }
}
