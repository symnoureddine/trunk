<?php

/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsPasswordKeeper extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile('vw_keychains', TAB_EDIT);
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
