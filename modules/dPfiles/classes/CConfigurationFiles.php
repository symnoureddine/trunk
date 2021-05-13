<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Ox\Core\Handlers\HandlerParameterBag;
use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationFiles
 */
class CConfigurationFiles extends AbstractConfigurationRegister {
  /**
   * @inheritDoc
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "dPfiles" => array(
            "General"         => array(
              "upload_max_filesize" => "str default|2M",
              "extensions_yoplet"   => "str",
              "yoplet_upload_url"   => "str",
            ),
            "CFile"           => array(
              "merge_to_pdf" => "bool default|0",
            ),
            "CFilesCategory"  => array(
              "show_empty" => "bool default|1",
            ),
            "CDocumentSender" => array(
              "system_sender" => "enum list||CEcDocumentSender localize",
              "auto_max_load" => "num min|0 default|50",
              "auto_max_send" => "num min|0 default|10",
            ),
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
          ->register(CFileTraceabilityHandler::class, false);
  }
}
