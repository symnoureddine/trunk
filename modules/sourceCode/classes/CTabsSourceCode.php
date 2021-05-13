<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsSourceCode extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile('vw_sourcecode', TAB_READ);
        $this->registerFile('vw_gitlab', TAB_READ);
        $this->registerFile('vw_gitlab_ci', TAB_READ);
        $this->registerFile('vw_gitlab_report', TAB_READ);
        $this->registerFile('vw_tests', TAB_EDIT);
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
