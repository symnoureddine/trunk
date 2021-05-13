<?php
/**
 * @package Mediboard\Labo
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Labo;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationLabo
 */
class CConfigurationLabo extends AbstractConfigurationRegister {

  /**
   * @return mixed
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "dPlabo" => array(
            "CCatalogueLabo" => array(
              "remote_name" => "str default|LABO",
              "remote_url"  => "str default|http://localhost/mediboard/modules/dPlabo/remote/catalogue.xml",
            ),

            "CPackExamensLabo" => array(
              "remote_url" => "str default|http://localhost/mediboard/modules/dPlabo/remote/pack.xml",
            ),
          ),
        ),
      )
    );
  }
}
