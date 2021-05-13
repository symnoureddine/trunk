<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Developpement\Controllers\Legacy;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CLegacyController;
use Ox\Core\CMbArray;
use Ox\Core\CView;
use Ox\Core\Kernel\Event\ListenersRegister;
use Ox\Mediboard\Developpement\CModuleBuilder;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

class DeveloppementController extends CLegacyController
{
    public function configure()
    {
        $this->checkPermAdmin();
        $this->renderSmarty('configure.tpl');
    }

    public function do_create_module()
    {
        $this->checkPermAdmin();

        $name_canonical     = CView::post("name_canonical", "str notNull pattern|[a-zA-Z0-9_]*");
        $name_short         = CView::post("name_short", "str notNull");
        $name_long          = CView::post("name_long", "str notNull");
        $license            = CView::post("license", "str notNull");
        $namespace_prefix   = CView::post("namespace_prefix", "str notNull");
        $namespace_category = CView::post("namespace_category", "str notNull");
        $namespace          = CView::post("namespace", "str notNull");
        $mod_category       = CView::post("mod_category", "str notNull");
        $mod_package        = CView::post("mod_package", "str notNull");
        $trigramme          = CView::post("trigramme", "str notNull");
        $mod_image          = CView::post("mod_image", "str");

        CView::checkin();

        $namespace = str_replace('\\\\', '\\', "{$namespace_prefix}\\{$namespace_category}\\" . ucfirst($namespace));

        $builder = new CModuleBuilder(
            $name_canonical,
            $namespace,
            $name_short,
            $name_long,
            $license,
            $trigramme,
            $mod_package,
            $mod_category,
            $namespace_category,
            $mod_image
        );
        $builder->build();

        CAppUI::js("getForm('create-module-form').reset()");
        CAppUI::setMsg("Module '$name_canonical' créé", UI_MSG_OK);
        echo CAppUI::getMsg();
        CApp::rip();
    }

    public function css_test(): void
    {
        $this->checkPermRead();

        $files          = [
            "style/mediboard_ext/standard.css",
        ];
        $button_classes = [];

        foreach ($files as $_file) {
            $css_files = file_get_contents($_file);
            $matches   = [];
            preg_match_all('/button\:not\(\[class\^=v\-\]\)\.([^\:]+)\:\:before/', $css_files, $matches);
            $button_classes = array_merge($button_classes, $matches[1]);
        }

        $button_classes = array_unique($button_classes);
        $button_classes = array_filter(
            $button_classes,
            function ($button_class) {
                return strpos($button_class, '.') === false;
            }
        );

        $values_to_remove = [
            "notext",
            "me-notext",
            "me-btn-small",
            "rtl",
            "me-noicon",
            "me-small",
            "me-color-care[style*=forestgreen]",
            "me-color-care[style*=firebrick]",
            "me-dark",
            "me-secondary",
            "delete",
        ];
        foreach ($values_to_remove as $value) {
            CMbArray::removeValue($value, $button_classes);
        }

        // Création du template
        $this->renderSmarty(
            'css_test.tpl',
            [
                "button_classes" => array_values($button_classes),
            ]
        );
    }

    public function vw_kernel()
    {
        $this->checkPermRead();

        $this->renderSmarty(
            'vw_kernel',
            [
                "listeners"        => $this->getDispatcherListeners(),
            ]
        );
    }

    private function getDispatcherListeners(): array
    {
        $dispatcher = new EventDispatcher();
        $request    = Request::create('api/');
        $request->attributes->set('is_api', true);
        ListenersRegister::addSubscribers(
            $request,
            $dispatcher
        );

        $dispatch_listeners = $dispatcher->getListeners();

        foreach ($dispatch_listeners as $_event_name => &$listeners) {
            foreach ($listeners as &$_listener) {
                $_listener[] = $dispatcher->getListenerPriority($_event_name, [$_listener[0], $_listener[1]]);
            }
        }

        $dispatch_listeners = array_merge_recursive($dispatch_listeners, $this->getCustomListeners());

        return $this->sortListeners($dispatch_listeners);
    }

    private function getCustomListeners(): array
    {
        $custom_listeners = [];
        foreach ($this->getInterfaceChildren(EventSubscriberInterface::class) as $listener_name) {
            if (
                in_array($listener_name, ListenersRegister::DEFAULT_LISTENERS, true)
                || in_array($listener_name, ListenersRegister::API_LISTENERS, true)
            ) {
                continue;
            }

            $events = $listener_name::getSubscribedEvents();
            foreach ($events as $_event_name => $_listeners) {
                foreach ($_listeners as [$_action, $_priority]) {
                    $custom_listeners[$_event_name][] = [
                        new $listener_name(),
                        $_action,
                        $_priority
                    ];
                }
            }
        }

        return $custom_listeners;
    }

    private function getInterfaceChildren(string $interface): array
    {
        $classes = [];
        foreach (CClassMap::getInstance()->getClassMap() as $_class_name => $_map) {
            if (in_array($interface, $_map['interfaces'], true)) {
                $classes[] = $_class_name;
            }
        }

        return $classes;
    }

    private function sortListeners(array $listeners): array
    {
        $results = [];

        $reflexion = new ReflectionClass(KernelEvents::class);
        $consts    = $reflexion->getConstants();

        foreach ($consts as $const_name => $event_name) {
            if (isset($listeners[$event_name])) {
                $results[$event_name] = [];

                /** @var EventSubscriberInterface $listener $listener */
                foreach ($listeners[$event_name] as [$listener, $action, $priority]) {
                    $results[$event_name][$priority] = [
                        'priority' => $priority,
                        'callable' => get_class($listener) . '::' . $action,
                        'type'     => $this->getListenerType(get_class($listener)),
                    ];
                }

                ksort($results[$event_name]);
                $results[$event_name] = array_reverse($results[$event_name]);
            }
        }

        return $results;
    }

    private function getListenerType(string $listener_class): ?string
    {
        if (in_array($listener_class, ListenersRegister::DEFAULT_LISTENERS)) {
            return 'default';
        }

        if (in_array($listener_class, ListenersRegister::API_LISTENERS)) {
            return 'api';
        }

        return 'optionnal';
    }
}
