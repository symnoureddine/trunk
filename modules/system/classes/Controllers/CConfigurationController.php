<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers;

use Exception;
use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CAppUI;
use Ox\Core\CController;
use Ox\Core\CMbArray;
use Ox\Core\CStoredObject;
use Ox\Core\Kernel\Exception\CAccessDeniedException;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\System\Api\FilterableTrait;
use Ox\Mediboard\System\CConfiguration;
use Ox\Mediboard\System\CConfigurationModelManager;
use Ox\Mediboard\System\ConfigurationManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Description
 */
class CConfigurationController extends CController
{
    use FilterableTrait;

    /** @var string */
    private $mod_name;

    /** @var array */
    private $model = [];

    /** @var array */
    private $available_contexts = [];

    /**
     * @api
     */
    public function listConfigurations(string $mod_name, CRequestApi $request_api): JsonResponse
    {
        $this->mod_name = $this->getActiveModule($mod_name);

        $module = CModule::getActive($this->mod_name);
        if (!$this->checkPermRead($module)) {
            throw new CAccessDeniedException("Cannot access module '{$mod_name}'");
        }

        $configurations = [
            'instance' => $this->getInstanceConfigurations($request_api),
            'static'   => $this->getStaticConfigurations($request_api),
            'context'  => $this->getContextualConfigurations($request_api),
        ];

        $ressource = new CItem($configurations);
        $ressource->setName('configurations');
        if (!$this->isFilterEnabled()) {
            // TODO Vider les etags lors du changement de configs
            // TODO Il faut au moins un type d'etag par module ?
            $ressource->setEtag(CEtag::TYPE_CONFIGURATIONS);
        }

        return $this->renderApiResponse($ressource);
    }

    private function getInstanceConfigurations(CRequestApi $request_api): array
    {
        try {
            // TODO Handle core configs ?
            // TODO Ajouter les configs des DS ? Danger et pas de découpage simple par module
            // CAppUI::conf already has it's own cache
            $configs = CAppUI::conf($this->mod_name);
        } catch (Throwable $e) {
            $configs = [];
        }

        $flattened_configs = CMbArray::flattenArrayKeys($configs, $this->mod_name);

        return $this->applyFilter($request_api, $flattened_configs, false);
    }

    private function getStaticConfigurations(CRequestApi $request_api): array
    {
        try {
            $configs = (ConfigurationManager::get())->getValuesForModule($this->mod_name);
        } catch (Throwable $e) {
            $configs = [];
        }

        $flattened_configs = CMbArray::flattenArrayKeys($configs, $this->mod_name);

        return $this->applyFilter($request_api, $flattened_configs, false);
    }

    private function getContextualConfigurations(CRequestApi $request_api): array
    {
        $configurations = [];

        $this->model              = CConfiguration::getModel($this->mod_name);
        $this->available_contexts = array_keys($this->model);

        $configurations = $this->buildGroupsConfigurations($request_api, $configurations);

        return $this->buildOtherContextualConfigurations($request_api, $configurations);
    }

    private function getContextualConfigurationForContext(
        CRequestApi $request_api,
        CStoredObject $context,
        string $context_class = null
    ): array {
        $configs         = [];
        $context_configs = CConfigurationModelManager::getValues($this->mod_name, $context->_class, $context->_id);

        if (!$context_class) {
            $context_class = $context->_class;
        }

        foreach (CMbArray::flattenArrayKeys($context_configs) as $_config => $_value) {
            $_key = $this->mod_name . ' ' . trim($_config);
            if (isset($this->model[$context_class][$_key])) {
                $configs[$_key] = $_value;
            }
        }

        return $this->applyFilter($request_api, $configs, false);
    }

    private function isSubContext(string $context): bool
    {
        return str_contains($context, ' ');
    }

    private function getQueryContexts(CRequestApi $request_api): array
    {
        $contexts        = [];
        $asked_contextes = $request_api->getRequest()->query->get('context_guid');
        if ($asked_contextes) {
            $split_context = explode('|', $asked_contextes);

            $context_objects = CStoredObject::loadFromGuids($split_context);

            /** @var CStoredObject $_context */
            foreach ($context_objects as $_class => $_contexts) {
                if ($_class === 'CGroups') {
                    continue;
                }

                $contexts[$_class] = [];
                foreach ($_contexts as $_ctx) {
                    if ($_ctx->getPerm(PERM_READ)) {
                        $contexts[$_class][] = $_ctx;
                    }
                }
            }
        }

        return $contexts;
    }

    private function buildGroupsConfigurations(
        CRequestApi $request_api,
        array $configurations = []
    ): array {
        $group = $request_api->getGroup();

        if (in_array('CGroups', $this->available_contexts, true)) {
            $configurations[$group->_class] = [
                $group->_id => $this->getContextualConfigurationForContext($request_api, $group),
            ];

            // For each subcontext load objects from current group
            foreach ($this->available_contexts as $_ctx) {
                // TODO Handle constantes configurations
                if (!$this->isSubContext($_ctx) || strpos($_ctx, 'constantes') === 0) {
                    continue;
                }

                $configurations = $this->addContextualConfigurationsForSubContexts(
                    $request_api,
                    $group,
                    $_ctx,
                    $configurations
                );
            }
        }

        return $configurations;
    }

    private function buildOtherContextualConfigurations(CRequestApi $request_api, array $configurations = []): array
    {
        //$query_contexts = $this->getQueryContexts($request_api);
        foreach ($this->available_contexts as $_context_class) {
            if ($_context_class === 'CGroups' || $this->isSubContext($_context_class)) {
                continue;
            }

            /** @var CStoredObject $ctx_instance */
            $ctx_instance  = new $_context_class();
            $all_instances = $ctx_instance->loadMatchingListEsc();

            foreach ($all_instances as $_context) {
                if (!$_context->getPerm(PERM_READ)) {
                    continue;
                }

                if (!isset($configurations[$_context_class])) {
                    $configurations[$_context_class] = [];
                }

                $configurations[$_context_class][$_context->_id]
                    = $this->getContextualConfigurationForContext($request_api, $_context);
            }
        }

        return $configurations;
    }

    private function addContextualConfigurationsForSubContexts(
        CRequestApi $request_api,
        CGroups $group,
        string $ctx,
        array $configurations
    ): array {
        $ctx_objects = $this->loadSubCtxObjects($group, $ctx);

        /** @var CStoredObject $_object */
        foreach ($ctx_objects as $_object) {
            if (!$_object->getPerm(PERM_READ)) {
                continue;
            }

            if (!isset($configurations[$_object->_class])) {
                $configurations[$_object->_class] = [];
            }

            $configurations[$_object->_class][$_object->_id]
                = $this->getContextualConfigurationForContext($request_api, $_object, $ctx);
        }

        return $configurations;
    }

    /**
     * Only handle sub ctx from CGroups
     */
    private function loadSubCtxObjects(CGroups $group, string $ctx_string): array
    {
        [$ctx_class,] = explode(' ', $ctx_string);

        /** @var CStoredObject $obj */
        $obj           = new $ctx_class();
        $obj->group_id = $group->_id;

        return $obj->loadMatchingListEsc();
    }
}
