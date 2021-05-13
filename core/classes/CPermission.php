<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Kernel\Exception\CPermissionException;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Admin\CPermModule;
use Ox\Mediboard\Admin\CPermObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CPermission
 */
class CPermission
{

    /** @var Request */
    public $request;

    /** @var string */
    public $controller;

    /** @var string */
    public $permission;

    /** @var string */
    public $module_name;

    /** @var CModule */
    public $module;

    /**
     * CPermission constructor.
     *
     * @param Request $request
     */
    public function __construct(CController $controller, Request $request)
    {
        $this->request     = $request;
        $this->controller  = $controller;
        $this->permission  = $request->attributes->get('permission');
        $this->module_name = $controller->getModuleName();
    }

    /**
     * Load user's perms (module and object)
     * @return void
     */
    public static function loadUserPerms()
    {
        CPermModule::loadUserPerms();
        CPermObject::loadUserPerms();
    }

    /**
     * Check module pemissions and access level
     * @return void
     * @throws CPermissionException
     */
    public function check(): void
    {
        // Init module
        $this->module = CModule::getActive($this->module_name);

        // Installed
        if ($this->module === null || $this->module === []) {
            throw new CPermissionException(
                Response::HTTP_FORBIDDEN,
                "The module {$this->module_name} is not actif.",
                [],
                0
            );
        }

        // Obsolete
        if (CModule::getObsolete($this->module_name)) {
            throw new CPermissionException(
                Response::HTTP_FORBIDDEN,
                "The module {$this->module_name} need maintenance.",
                [],
                1
            );
        }

        // Cando ?
        $permission = $this->permission ?? 'read';
        $can        = $this->module->canDo();
        $is_allowed = false;
        switch ($permission) {
            case 'none':
                $is_allowed = true;
                break;
            case 'read':
                $is_allowed = $can->read === true;
                break;
            case 'edit':
                $is_allowed = $can->edit === true;
                break;
            case 'admin':
                $is_allowed = $can->admin === true;
                break;
        }
        if (!$is_allowed) {
            throw new CPermissionException(
                Response::HTTP_FORBIDDEN,
                "Permission {$permission} denied for module {$this->module_name}.",
                [],
                3
            );
        }
    }
}
