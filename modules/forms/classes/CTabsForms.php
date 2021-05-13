<?php

/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Forms;

use Ox\Core\Module\AbstractTabsRegister;

/**
 * Description
 */
class CTabsForms extends AbstractTabsRegister
{

    public function registerAll(): void
    {
        $this->registerFile("view_ex_class", TAB_EDIT);
        $this->registerFile("view_ex_list", TAB_EDIT);
        $this->registerFile("view_ex_concept", TAB_EDIT);
        $this->registerFile("view_ex_class_category", TAB_EDIT);
        $this->registerFile("view_ex_object_explorer", TAB_EDIT);
        $this->registerFile("vw_import_ex_class", TAB_EDIT);
        $this->registerFile("view_stats", TAB_READ);
        $this->registerFile("vw_ref_checker", TAB_ADMIN);
        $this->registerFile('configure', TAB_ADMIN, self::TAB_CONFIGURE);
    }
}
