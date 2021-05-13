<?php
/**
 * @package Mediboard\api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Api;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Description
 */
class CConfigurationAPI extends AbstractConfigurationRegister {
  /**
   * @return mixed
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "api" => array(
            "WithingsAPI" => array(
              "api_id"     => "str",
              "api_secret" => "str",
            ),
            "FitbitAPI"   => array(
              "api_id"     => "str",
              "api_secret" => "str",
            ),
          ),
        ),
      )
    );
  }
}
