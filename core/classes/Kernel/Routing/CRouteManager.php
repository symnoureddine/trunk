<?php

/**
 * @package Mediboard\Core\Kernel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Routing;

use Exception;
use Ox\Core\Auth\CAuthentication;
use Ox\Core\CClassMap;
use Ox\Core\CController;
use Ox\Core\CMbException;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Core\Kernel\Exception\CRouteException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CRouteManager
 */
class CRouteManager
{
    /** @var array */
    public const ALLOWED_METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'HEAD',
    ];

    /** @var array */
    public const ALLOWED_OPTIONS = [
        'openapi',
        'accept',
        'description',
        'parameters',
        'responses',
        'body',
        'dirname',
    ];

    /** @var array */
    public const ALLOWED_DEFAULTS = [
        'security',
        'permission',
        '_controller',
        '_route',
        'object_class',
    ];


    /** @var array */
    public const ALLOWED_OPTIONS_BODY = [
        'required',
        'content-type',
    ];

    /** @var array */
    public const ALLOWED_PERMISSIONS = [
        'read',
        'edit',
        'admin',
        'none',
    ];

    public const ALLOWED_PATH_PREFIX = [
        'api/',
        'gui/',
    ];

    /** @var RouteCollection */
    private $route_collection;

    /** @var array */
    private $routes_name = [];

    /** @var array */
    private $routes_prefix = [];

    /** @var array */
    private $routes_path_methods = [];

    /** @var array */
    private $modules_controller_unicity = [];

    /** @var string $all_routes_path */
    private $all_routes_path;

    /** @var string $root */
    private $root;

    /**
     * CRouteManager constructor.
     */
    public function __construct()
    {
        $this->root             = dirname(__DIR__, 4);
        $this->all_routes_path  = $this->root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'all_routes.yml';
        $this->route_collection = new RouteCollection();
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return array|RouteCollection|null
     */
    public function getRouteCollection()
    {
        return $this->route_collection;
    }

    /**
     * @return string path to all_routes.yml file
     */
    public function getAllRoutesPath()
    {
        return $this->all_routes_path;
    }

    /**
     * @return $this
     * @throws CRouteException
     * @throws Exception
     */
    public function loadAllRoutes($glob = true): CRouteManager
    {
        if ($glob) {
            foreach ($this->getRessources() as $_file) {
                $pathinfo    = pathinfo($_file);
                $fileLocator = new FileLocator($pathinfo['dirname']);
                $loader      = new YamlFileLoader($fileLocator);
                try {
                    $new_collection = $loader->load($pathinfo['basename']);
                    $new_collection->addOptions(['dirname' => $pathinfo['dirname']]);
                    $this->route_collection->addCollection($new_collection);
                } catch (Exception $e) {
                    throw new CRouteException($e->getMessage() . ' in ' . $_file);
                }
            }
        } else {
            if (!file_exists($this->getAllRoutesPath())) {
                throw new Exception('File ' . $this->getAllRoutesPath() . ' is not exists.');
            }

            $pathinfo    = pathinfo($this->getAllRoutesPath());
            $fileLocator = new FileLocator($pathinfo['dirname']);
            $loader      = new YamlFileLoader($fileLocator);
            $collection  = $loader->load($pathinfo['basename']);
            $this->route_collection->addCollection($collection);
        }

        return $this;
    }


    /**
     * @param string $prefix
     *
     * @return RouteCollection
     */
    public function filterRoutesCollectionByPrefix(string $prefix, RouteCollection $collection = null): RouteCollection
    {
        $collection_filtered = new RouteCollection();
        $collection          = $collection ?? $this->route_collection;
        $prefix              = $prefix[0] === '/' ? $prefix : '/' . $prefix;

        /**@var Route $_route */
        foreach ($collection as $_name => $_route) {
            if (strpos($_route->getPath(), $prefix) !== 0) {
                continue;
            }
            $collection_filtered->add($_name, $_route);
        }

        return $collection_filtered;
    }


    /**
     * @param string $route_name
     *
     * @return Route
     * @throws CRouteException
     */
    public function getRouteByName($route_name): Route
    {
        $route = $this->route_collection->get($route_name);
        if ($route === null) {
            throw new CRouteException('[%s] Invalid route name', $route_name);
        }

        return $route;
    }

    /**
     * @param string $route_path
     *
     * @return array|RouteCollection
     */
    public function getRoutesByPath($route_path)
    {
        /**@var Route $_route */
        $routes = [];
        foreach ($this->route_collection as $_name => $_route) {
            if ($_route->getPath() === $route_path) {
                $routes[$_name] = $_route;
            }
        }

        return $routes;
    }

    /**
     * @return string
     * @throws CRouteException
     */
    public function buildAllRoutes(): string
    {
        $time_start = microtime(true);

        $file = $this->all_routes_path;
        if (file_exists($file) && is_file($file)) {
            unlink($file);
        }

        // Init
        $content             = null;
        $this->routes_name   = [];
        $this->routes_prefix = [];


        // Check route collection validity
        foreach ($this->route_collection as $route_name => &$route) {
            $this->checkRoute($route_name, $route);
        }

        // Create all_routes.yml
        $yml     = new Yaml();
        $content = [];
        foreach ($this->route_collection as $route_name => $route) {
            $content[] = $yml->dump($this->convertRouteToArray($route_name, $route));
        }

        // Store
        file_put_contents($file, implode(PHP_EOL, $content));

        $time         = round(microtime(true) - $time_start, 3);
        $count_routes = $this->route_collection->count();

        return "Generated routing file in {$file} containing {$count_routes} routes during {$time} sec";
    }

    /**
     * @return array|string routes yml files
     */
    public function getRessources()
    {
        return glob($this->root . '/modules/*/routes/*.yml', GLOB_BRACE) ?: [];
    }


    /**
     * @param Route $route
     * @param bool  $check_controller
     *
     * @return bool
     * @throws CRouteException
     * @throws CControllerException
     */
    public function checkRoute(string $route_name, Route $route, bool $check_controller = true): bool
    {
        //// init
        $route_dir = $route->getOption('dirname');
        $route_dir = str_replace('routes', '', $route_dir);

        if (strpos(PHP_OS, 'WIN') !== false) {
            // window compat
            $route_dir = str_replace('/', '\\', $route_dir);
        }

        //// Name
        if (in_array($route_name, $this->routes_name, true)) {
            throw new CRouteException('[%s] Duplicate route name', $route_name);
        }
        $this->routes_name[] = $route_name;

        //// Prefix
        $routes_segments = explode('_', $route_name);
        if (count($routes_segments) <= 1) {
            throw new CRouteException('[%s] Invalid route name, missing prefix', $route_name, 'toto');
        }
        $_prefix = $routes_segments[0];

        if (!isset($this->routes_prefix[$route_dir])) {
            // first route in collection/file
            if (in_array($_prefix, array_values($this->routes_prefix), true)) {
                throw new CRouteException('[%s] Duplicate route prefix %s', $route_name, $_prefix);
            }
            $this->routes_prefix[$route_dir] = $_prefix;
        } else {
            $previous_prefix = $this->routes_prefix[$route_dir];
            if ($previous_prefix !== $_prefix) {
                throw new CRouteException(
                    '[%s] Invalid prefix name %s not equals %s',
                    $route_name,
                    $previous_prefix,
                    $_prefix
                );
            }
        }

        //// Methods
        $http_methods = $route->getMethods();
        if (empty($http_methods)) {
            throw new CRouteException('[%s] Empty methods', $route_name);
        }
        foreach ($http_methods as $http_method) {
            if (!in_array($http_method, self::ALLOWED_METHODS, true)) {
                $allowed_methods = implode(', ', self::ALLOWED_METHODS);
                throw new CRouteException(
                    '[%s] Invalid method %s, expected one of: %s',
                    $route_name,
                    $http_method,
                    $allowed_methods
                );
            }

            // duplicity route path + http method
            $path_http_method = $route->getPath() . '_' . $http_method;
            if (in_array($path_http_method, $this->routes_path_methods, true)) {
                throw new CRouteException(
                    '[%s] Duplicate route method %s for path %s ',
                    $route_name,
                    $http_method,
                    $route->getPath()
                );
            }
            $this->routes_path_methods[] = $path_http_method;
        }

        if ($check_controller) {
            //// Controller
            $default_controller = $route->getDefault('_controller');
            if ($default_controller === null || strpos($default_controller, '::') === false) {
                throw new CRouteException('[%s] Invalid default controller %s', $route_name, $default_controller);
            }
            [$controller, $method] = explode('::', $route->getDefault('_controller'));
            if (!class_exists($controller)) {
                throw new CRouteException('[%s] Invalid controller class %s', $route_name, $controller);
            }

            try {
                /** @var CController $instance */
                $instance = new $controller;
            } catch (Exception $e) {
                throw new CRouteException('[%s] Invalid controller instance %s', $route_name, $controller);
            }

            if (!is_subclass_of($instance, CController::class)) {
                throw new CRouteException('[%s] Invalid controller subclass %s', $route_name, $controller);
            }

            if (!$route || strpos($instance->getReflectionClass()->getFileName(), $route_dir) !== 0) {
                throw new CRouteException(
                    '[%s] Controller path does not match route directory %s',
                    $route_name,
                    $controller
                );
            }

            if (!method_exists($instance, $method)) {
                throw new CRouteException('[%s] Invalid controller method %s::%s', $route_name, $controller, $method);
            }

            // Module controller unicity (access_log)
            $key_unicity = implode(
                '-',
                [
                    $instance->getModuleName(),
                    CClassMap::getSN($controller),
                    $method,
                ]
            );
            if (array_key_exists($key_unicity, $this->modules_controller_unicity)
                && $this->modules_controller_unicity[$key_unicity] !== $controller) {
                throw new CRouteException(
                    '[%s] Invalid controller unicity constraint %s',
                    $route_name,
                    $key_unicity
                );
            }
            $this->modules_controller_unicity[$key_unicity] = $controller;
            unset($key_unicity);

            // Api security
            if (strpos($route->getPath(), 'api') === 1) {
                $reflection_method = $instance->getReflectionMethod($method);
                $doc_comment       = $reflection_method->getDocComment();
                $pattern           = $route->getDefault('security') === [] ? '/@api public/i' : '/@api\s(?!public)/i';
                if ($doc_comment === false || preg_match($pattern, $doc_comment) !== 1) {
                    $doc_expected = $route->getDefault('security') === [] ? '@api public' : '@api';
                    throw new CRouteException(
                        '[%s] Invalid controller doc comments %s::%s, does not match %s',
                        $route_name,
                        $controller,
                        $method,
                        $doc_expected
                    );
                }
            }
        }

        //// Path
        $path       = $route->getPath();
        $valid_path = false;
        foreach (self::ALLOWED_PATH_PREFIX as $prefix) {
            if (strpos($path, $prefix) === 1) {
                $valid_path = true;
                break;
            }
        }
        if (!$valid_path) {
            throw new CRouteException(
                '[%s] Path must start by %s',
                $route_name,
                implode(',', self::ALLOWED_PATH_PREFIX)
            );
        }


        //// Defaults
        $defaults = $route->getDefaults();
        if (!array_key_exists('permission', $defaults)) {
            throw new CRouteException('[%s] Missing mandatory defaults key %s', $route_name, 'permission');
        }

        foreach ($defaults as $default_name => $default_value) {
            // available default
            if (!in_array($default_name, self::ALLOWED_DEFAULTS, true)) {
                $allowed = implode(', ', self::ALLOWED_DEFAULTS);
                throw new CRouteException(
                    '[%s] Invalid default %s, expected one of: %s',
                    $route_name,
                    $default_name,
                    $allowed
                );
            }

            // security (= authentication)
            if ($default_name === 'security') {
                if (!is_array($default_value)) {
                    throw new CRouteException(
                        '[%s] Invalid defaults security %s, expected array',
                        $route_name,
                        gettype($default_value)
                    );
                }
                foreach ($default_value as $security_name) {
                    $allowed_authentication = array_keys(CAuthentication::SERVICES);
                    if (!in_array($security_name, $allowed_authentication, true)) {
                        throw new CRouteException(
                            '[%s] Invalid allowed option security %s, expected one of: %s',
                            $route_name,
                            $security_name,
                            implode(', ', $allowed_authentication)
                        );
                    }
                }
            }

            // permission
            if ($default_name === 'permission') {
                if (!is_string($default_value)) {
                    throw new CRouteException(
                        '[%s] Invalid defaults permission %s, expected string',
                        $route_name,
                        gettype($default_value)
                    );
                }
                $allowed_permission = static::ALLOWED_PERMISSIONS;
                if (!in_array($default_value, $allowed_permission, true)) {
                    throw new CRouteException(
                        '[%s] Invalid allowed option permission %s, expected one of: %s',
                        $route_name,
                        $default_value,
                        implode(', ', $allowed_permission)
                    );
                }
            }
        }

        //// Options
        foreach ($route->getOptions() as $option_name => $option_value) {
            // ignore compiler_class
            if ($option_name === 'compiler_class') {
                continue;
            }

            // available options
            if (!in_array($option_name, self::ALLOWED_OPTIONS, true)) {
                $allowed_options = implode(', ', self::ALLOWED_OPTIONS);
                throw new CRouteException(
                    '[%s] Invalid option %s, expected one of: %s',
                    $route_name,
                    $option_name,
                    $allowed_options
                );
            }

            // description
            if (($option_name === 'description') && !is_string($option_value)) {
                throw new CRouteException(
                    '[%s] Invalid option desciption %s, expected string',
                    $route_name,
                    gettype($option_value)
                );
            }

            // openapi
            if (($option_name === 'openapi') && !is_bool($option_value)) {
                throw new CRouteException(
                    '[%s] Invalid option openapi %s, expected bool',
                    $route_name,
                    gettype($option_value)
                );
            }

            // content negociation
            if (($option_name === 'accept') && !is_array($option_value)) {
                throw new CRouteException(
                    '[%s] Invalid option accept %s, expected array',
                    $route_name,
                    gettype($option_value)
                );
            }

            // body
            if ($option_name === 'body') {
                if (!is_array($option_value)) {
                    throw new CRouteException(
                        '[%s] Invalid option body %s, expected array',
                        $route_name,
                        gettype($option_value)
                    );
                }
                foreach ($option_value as $body_option => $body_option_value) {
                    if (!in_array($body_option, self::ALLOWED_OPTIONS_BODY, true)) {
                        $allowed_body_option = implode(', ', self::ALLOWED_OPTIONS_BODY);
                        throw new CRouteException(
                            '[%s] Invalid allowed option body %s, expected one of: %s',
                            $route_name,
                            $body_option,
                            $allowed_body_option
                        );
                    }

                    if ($body_option === 'required' && !is_bool($body_option_value)) {
                        throw new CRouteException(
                            '[%s] Invalid option body required %s, expected bool',
                            $route_name,
                            gettype($body_option_value)
                        );
                    }

                    // defalut application/json
                    if ($body_option === 'content-type' && !is_array($body_option_value)) {
                        throw new CRouteException(
                            '[%s] Invalid body option content-type %s, expected array',
                            $route_name,
                            gettype($body_option_value)
                        );
                    }
                }
            }

            // responses
            if ($option_name === 'responses') {
                if (!is_array($option_value)) {
                    throw new CRouteException(
                        '[%s] Invalid responses option %s, expected array',
                        $route_name,
                        gettype($option_value)
                    );
                }
                foreach ($option_value as $_response_code => $_response_description) {
                    if (!is_int($_response_code)) {
                        throw new CRouteException(
                            '[%s] Invalid response code option %s, expected int',
                            $route_name,
                            gettype($_response_code)
                        );
                    }
                    if (!is_string($_response_description)) {
                        throw new CRouteException(
                            '[%s] Invalid response description %s, expected string',
                            $route_name,
                            gettype($_response_description)
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array $arguments
     *
     * @return Route
     * @throws CMbException
     */
    public function createRouteFromRequest($arguments): Route
    {
        $path         = rtrim($arguments['path'], '/');
        $requirements = [];
        foreach ($arguments['req_names'] as $_key => $_name) {
            if ($_name) {
                $path .= "/{{$_name}}";

                if (!isset($arguments['req_types'][$_key]) || !$arguments['req_types'][$_key]) {
                    throw new CMbException('Type is mandatory for requirement %s', $_name);
                }

                $requirements[$_name] = stripslashes($arguments['req_types'][$_key]);
            }
        }

        $route        = new Route($path);
        $route->_name = $arguments['route_name'];
        $route->setDefault('_controller', stripslashes($arguments['controller']));
        $route->setMethods(array_keys($arguments['methods']));
        $route->setRequirements($requirements);

        $route->setOption('openapi', (bool)$arguments['openapi']);

        if ($arguments['accept']) {
            $route->setOption('accept', array_keys($arguments['accept']));
        }

        if ($arguments['description']) {
            $route->setOption('description', $arguments['description']);
        }

        if ($arguments['param_names']) {
            $params = [];
            foreach ($arguments['param_names'] as $_key => $_name) {
                if ($_name) {
                    $params[$_name] = (isset($arguments['param_types'][$_key]) && $arguments['param_types'][$_key])
                        ? stripslashes($arguments['param_types'][$_key]) : '';
                }
            }

            if ($params) {
                $route->setOption('parameters', $params);
            }
        }

        if ($arguments['security'] && count($arguments['security']) < count(CAuthentication::SERVICES)) {
            $securities = [];
            foreach ($arguments['security'] as $_secu) {
                $securities[$_secu] = [];
            }

            $route->setDefault('security', $securities);
        }

        if ($arguments['response_names']) {
            $responses = [];
            foreach ($arguments['response_names'] as $_key => $_name) {
                if ($_name) {
                    $responses[$_name]
                        = (isset($arguments['response_descs'][$_key]) && $arguments['response_descs'][$_key])
                        ? $arguments['response_descs'][$_key] : null;
                }
            }

            if ($responses) {
                $route->setOption('responses', $responses);
            }
        }

        $body = [
            'required' => (bool)$arguments['body_required'],
        ];

        if ($arguments['content_type']) {
            $body['content-type'] = array_keys($arguments['content_type']);
        }

        $route->setOption('body', $body);
        $route->setDefault('permission', $arguments['permission']);

        return $route;
    }

    /**
     * @param Route $route
     *
     * @return array[]
     */
    public function convertRouteToArray(string $route_name, Route $route): array
    {
        $data = [
            'path'     => $route->getPath(),
            'methods'  => $route->getMethods(),
            'defaults' => $route->getDefaults(), // (contains _controller)
        ];

        $requirements = $route->getRequirements();
        if (!empty($requirements)) {
            $data['requirements'] = $requirements;
        }

        $condition = $route->getCondition();
        if ($condition) {
            $data['condition'] = $condition;
        }

        return [$route_name => $data];
    }
}
