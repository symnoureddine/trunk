<?php

/**
 * @package Mediboard\Sa
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Sa;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsSa extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}