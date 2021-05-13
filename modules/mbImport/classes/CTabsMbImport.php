<?php

/**
 * @package Mediboard\MbImport
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsMbImport extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile('vw_import', TAB_ADMIN);
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
