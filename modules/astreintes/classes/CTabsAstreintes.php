<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes;

use Ox\Core\Module\AbstractTabsRegister;

class CTabsAstreintes extends AbstractTabsRegister
{
    public function registerAll(): void
    {
        $this->registerFile("vw_astreinte_cal", TAB_READ);
        $this->registerFile("vw_list_astreinte", TAB_EDIT);
        $this->registerFile("vw_list_categories", TAB_EDIT, self::TAB_SETTINGS);
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
