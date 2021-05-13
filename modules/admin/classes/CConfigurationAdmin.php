<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Admin;

use Ox\Core\Handlers\HandlerParameterBag;
use Ox\Mediboard\Admin\Rgpd\CRGPDHandler;
use Ox\Mediboard\Admin\Rgpd\CRGPDManager;
use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationAdmin
 */
class CConfigurationAdmin extends AbstractConfigurationRegister {
  /**
   * @inheritDoc
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "admin" => array(
            "CBrisDeGlace"          => array(
              "enable_bris_de_glace" => "bool default|0",
            ),
            "CLogAccessMedicalData" => array(
              "enable_log_access" => "bool default|0",
              "round_datetime"    => "enum list|1m|10m|1h|1d default|1h localize",
            ),
            'CLDAP'                 => array(
              'restrict_new_users_to_LDAP' => 'bool default|0',
            ),
            'CRGPDConsent'          => CRGPDManager::getRGPDConfigurationModel(),
          ),
        ),
      )
    );
  }

  /**
   * @inheritDoc
   */
  public function getObjectHandlers(HandlerParameterBag $parameter_bag): void {
      $parameter_bag
          ->register(CRGPDHandler::class, false);
  }
}

