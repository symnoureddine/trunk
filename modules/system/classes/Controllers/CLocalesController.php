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
use Ox\Core\CController;
use Ox\Core\Module\CModule;
use Ox\Mediboard\System\Api\LocalesFilter;
use Ox\Mediboard\System\CTranslationOverwrite;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description
 */
class CLocalesController extends CController
{
    /**
     * @param string $language
     * @param string $mod_name
     *
     * @return JsonResponse
     * @throws CApiException
     *
     * @api
     */
    public function listLocales(string $language, string $mod_name, CRequestApi $request_api): JsonResponse
    {
        $locales = $this->loadLocalesFiles($language, $mod_name);

        $overwrite = new CTranslationOverwrite();
        if ($overwrite->isInstalled()) {
            $locales = $overwrite->transformLocales($locales, $language);
        }

        $filter = new LocalesFilter($request_api);
        if ($filter->isEnabled()) {
            $locales = $filter->apply($locales);
        }

        $locales = $this->sanitize($locales);

        $ressource = new CItem($locales);
        $ressource->setName('locales');
        if (!$filter->isEnabled()) {
            $ressource->setEtag(CEtag::TYPE_LOCALES);
        }

        return $this->renderApiResponse($ressource);
    }

    private function loadLocalesFiles(string $language, string $mod_name): array
    {
        $root_dir = $this->getRootDir();

        // dP add super hack
        if (CModule::getActive($mod_name) === null) {
            $mod_name = 'dP' . $mod_name;

            if (CModule::getActive($mod_name) === null) {
                throw new CApiException("Module '{$mod_name}' does not exists or is not active");
            }
        }


        $files = [];
        if ($mod_name === 'core') {
            $files[] = $root_dir . '/locales/' . $language . '/common.php';
        } else {
            $base_path = $root_dir . '/modules/' . $mod_name . '/locales/';

            $files[] = $base_path . $language . '.php';
            $files[] = $base_path . $language . '.overload.php';
        }

        $locales = [];
        foreach ($files as $_file_path) {
            if (file_exists($_file_path)) {
                include $_file_path;
            }
        }

        return $locales;
    }

    private function sanitize(array $locales): array
    {
        foreach ($locales as $_key => &$_value) {
            $_value = trim(nl2br($_value));
        }

        return $locales;
    }
}
