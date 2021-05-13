<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\Cache;
use Ox\Core\CController;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\Api\FilterableTrait;
use Ox\Mediboard\System\Api\SimpleFilter;
use Ox\Mediboard\System\CPreferences;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description
 */
class CPreferencesController extends CController
{
    use FilterableTrait;

    public const CACHE_PREFIX = self::class;

    private const NO_MODULE_PREF_NAME = 'common';

    /**
     * @api
     */
    public function listPreferences(string $mod_name, CRequestApi $request_api): JsonResponse
    {
        $module_prefs = $this->loadModulePrefs($mod_name);

        $module_prefs = $this->applyFilter($request_api, $module_prefs);

        return $this->returnResponse(CPreferences::getPrefValuesForList($module_prefs));
    }

    /**
     * @api
     *
     * User checkRead is automatic with dependancie injection
     */
    public function listUserPreferences(string $mod_name, CUser $user, CRequestApi $request_api): JsonResponse
    {
        $module_prefs = $this->loadModulePrefs($mod_name);

        $module_prefs = $this->applyFilter($request_api, $module_prefs);

        return $this->returnResponse(CPreferences::getAllPrefsForList($user, $module_prefs));
    }

    /**
     * @api
     */
    public function listProfilePreferences(
        string $mod_name,
        string $profile_name,
        CRequestApi $request_api
    ): JsonResponse {
        $user                = new CUser();
        $user->user_username = $profile_name;
        $user->template      = '1';
        $user->loadMatchingObjectEsc();

        if (!$user->_id || !$user->getPerm(PERM_READ)) {
            throw new CApiException("Profile '{$profile_name}' does not exists or is not active");
        }

        return $this->listUserPreferences($mod_name, $user, $request_api);
    }

    private function returnResponse(array $preferences): JsonResponse
    {
        $ressource = new CItem($preferences);
        $ressource->setName('preferences');

        if (!$this->isFilterEnabled()) {
            $ressource->setEtag(CEtag::TYPE_PREFERENCES);
        }

        return $this->renderApiResponse($ressource);
    }

    private function loadModulePrefs(string $mod_name): array
    {
        $cache = new Cache(self::CACHE_PREFIX, $mod_name, Cache::INNER_OUTER);
        if (!$cache->exists()) {
            if ($mod_name !== self::NO_MODULE_PREF_NAME) {
                $mod_name = $this->getActiveModule($mod_name);
            }

            // Load prefs names from module preferences file
            CPreferences::loadModule($mod_name);

            $cache->put(CPreferences::$modules[$mod_name] ?? []);
        }

        // Return loaded prefs
        return $cache->get();
    }
}
