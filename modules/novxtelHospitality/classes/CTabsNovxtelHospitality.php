<?php

/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\NovxtelHospitality;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsNovxtelHospitality extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
