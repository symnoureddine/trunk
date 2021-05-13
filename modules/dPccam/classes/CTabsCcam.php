<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Module\AbstractTabsRegister;


class CTabsCcam extends AbstractTabsRegister
{
    public function registerAll(): void
    {
        $this->registerFile("vw_find_code", TAB_READ);
        $this->registerFile("vw_full_code", TAB_READ);
        $this->registerFile("vw_idx_favoris", TAB_READ);
        $this->registerFile("vw_find_acte", TAB_READ);
        $this->registerFile('vw_ngap', TAB_READ);
        $this->registerFile("vw_idx_frais_divers_types", TAB_ADMIN);


        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
