<?php
/**
 * @package Mediboard\Core\Api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\OpenApi;

use cebe\openapi\Reader;
use Ox\Core\CAppUI;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * COpenApiManager
 */
class COpenApiManager
{

    public const FILE_PATH = '/includes/documentation.yml';

    public const BIN_VALIDATOR = '/vendor/bin/php-openapi';

    /** @var string|null $root of project */
    private $root;

    /** @var array $documentation */
    private $documentation;

    /**
     * COpenApiManager constructor.
     */
    public function __construct()
    {
        $this->root = dirname(__DIR__, 3);
    }


    /**
     * @return array default OpenAPiSpecifications
     * @throws COpenApiException
     */
    private function getDefaultOAS(): array
    {
        return [
            'openapi'    => '3.0.1',
            'info'       => [
                'title'       => 'OX APIs documentation',
                'description' => 'Visualize and interact with our APIs resources.<br>Making it easy for back end implementation and client side consumption<br>Generated
 with <b>Mediboard</b> OpenApi Specifications and <b>Swagger UI</b> open source project.',
                'version'     => '1.0.0',
                'contact'     => [
                    'name'  => 'Support',
                    'email' => 'dev@openxtrem.com',
                ],
                'license'     => [
                    'name' => 'License GPL',
                    'url'  => 'https://openxtrem.com/licenses/gpl.html',
                ],
            ],
            'servers'    => $this->getServersInfo(),
            'paths'      => [],
            'tags'       => [
                [
                    'name'        => 'fhir',
                    'description' => 'FHIR is a standard for health care data exchange',
                ],
                [
                    'name'        => 'oauth',
                    'description' => 'Mediboard OAuth server implementation',
                ],
                [
                    'name'        => 'scim',
                    'description' => 'System for Cross-domain Identity Management',
                ],
                [
                    'name'        => 'appFine',
                    'description' => 'Patient portal resources',
                ],
                [
                    'name'        => 'system',
                    'description' => 'Administration system',
                ],
                [
                    'name'        => 'admin',
                    'description' => 'Permissions',
                ],
                [
                    'name'        => 'planning',
                    'description' => 'Appointment planification',
                ],
            ],
            'security'   => [
                ['Basic' => []],
                ['Token' => []],
                ['Login' => []],
                ['Session' => []],
                ['OAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'Basic'   => [
                        'type'   => 'http',
                        'scheme' => 'basic',
                    ],
                    'Token'   => [
                        'type' => 'apiKey',
                        'in'   => 'header',
                        'name' => 'X-OXAPI-KEY',
                    ],
                    'Login'   => [
                        'type' => 'apiKey',
                        'in'   => 'query',
                        'name' => 'login',
                    ],
                    'Session' => [
                        'type' => 'apiKey',
                        'in'   => 'cookie',
                        'name' => CAppUI::forgeSessionName(basename($this->root)),
                    ],
                    'OAuth'   => [
                        'type'  => 'oauth2',
                        'flows' => [
                            'clientCredentials' => [
                                'tokenUrl' => '/mediboard/api/oauth2/token',
                                'scopes'   => [
                                    'read' => 'Read scope',
                                ],
                            ],
                        ],
                    ],
                ],
                'responses'       => [
                    'succes'       => [
                        'description' => 'Successful operation',
                    ],
                    'partial'      => [
                        'description' => 'Partial response',
                    ],
                    'unauthorized' => [
                        'description' => 'Unauthorized resource',
                    ],
                    'forbidden'    => [
                        'description' => 'Forbidden resource',
                    ],
                    'not_found'    => [
                        'description' => 'Resource not found',
                    ],
                    'failed'       => [
                        'description' => 'Internal Server Error',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getServersInfo(): array
    {
        $config_file = $this->root . '/includes/config.php';
        $servers     = [];
        if (file_exists($config_file)) {
            global $dPconfig;
            require $config_file;
            $base_url    = $dPconfig['external_url'] ?? 'http://localhost/mediboard/';
            $description = $dPconfig['instance_role'] ?? 'Qualif';
            $servers[]   = [
                'url'         => $base_url,
                'description' => $description,
            ];
        }

        return $servers;
    }

    /**
     * @return bool
     */
    public function documentationExists(): bool
    {
        return file_exists($this->root . self::FILE_PATH);
    }

    /**
     * Use by composer script
     *
     * @param RouteCollection $route_collection
     *
     * @return string
     * @throws COpenApiException
     */
    public function build(RouteCollection $route_collection): string
    {
        $time_start = microtime(true);

        // Delete tmp file
        $file = $this->root . self::FILE_PATH;
        if (file_exists($file) && is_file($file)) {
            unlink($file);
        }

        // Default doc
        $this->documentation = $this->getDefaultOAS();

        // Generate routes oas
        foreach ($route_collection as $_route_name => $_route) {
            $_route->_name = $_route_name;
            $this->generateOAS($_route);
        }

        // Store
        file_put_contents($file, Yaml::dump($this->documentation));

        // Validate global OAS
        $this->validateOAS($file);

        $count_paths = count($this->documentation['paths']);
        $time        = round(microtime(true) - $time_start, 3);

        return "Generated openapi documentation file in {$file} containing {$count_paths} paths during {$time} sec";
    }


    /**
     * @param Route $route
     *
     * @return void
     */
    private function generateOAS(Route $route): void
    {
        // no doc ?
        if ($route->getOption('openapi') === false) {
            return;
        }

        // auto-generate !
        $oas = [];

        // description
        $oas['summary'] = $route->getOption('description') ?? 'Undefined';

        // tag (route prefix)
        $oas['tags'] = (array)explode('_', $route->_name)[0];

        // parameters
        $oas['parameters'] = [];

        // query params
        if ($parameters = $route->getOption('parameters')) {
            foreach ($parameters as $param_name => $type_route) {
                $type_oas = $this->getParametersTypeOAS($type_route);
                $param    = [
                    'in'       => 'query',
                    'name'     => $param_name,
                    'schema'   => [
                        'type' => $type_oas ?? 'string',
                    ],
                    'required' => false,
                ];

                if ($type_oas === null) {
                    $param['schema']['enum'] = explode('|', $type_route);
                }

                $oas['parameters'][] = $param;
            }
        }

        // path params
        if ($requirements = $route->getRequirements()) {
            foreach ($requirements as $param_name => $type_route) {
                $type_oas = $this->getParametersTypeOAS($type_route);

                $param = [
                    'in'       => 'path',
                    'name'     => $param_name,
                    'schema'   => [
                        'type' => $type_oas ?? 'string',
                    ],
                    'required' => true,
                ];

                if ($type_oas === null) {
                    $param['schema']['enum'] = explode('|', $type_route);
                }

                $oas['parameters'][] = $param;
            }
        }

        // header params
        //    $oas['parameters'][] = [
        //      'in'          => 'header',
        //      'name'        => 'X-GROUP-ID',
        //      'description' => 'Institutions group identifiant',
        //      'schema'      => [
        //        'type' => 'integer'
        //      ],
        //      'required'    => false
        //    ];

        // security
        $security = $route->getDefault('security');
        if (is_array($security)) {
            $oas['security'] = [];
            foreach ($security as $sercurity_name) {
                $oas['security'][] = [ucfirst($sercurity_name) => []];
            }
        }

        // request body
        if ($request_body = $route->getOption('body')) {
            $oas['requestBody'] = [
                'required' => $request_body['required'] ?? false,
            ];
            $content_types      = $request_body['content-type'] ?? [];
            foreach ($content_types as $content) {
                $oas['requestBody']['content'][$content] = [];
            }
        }

        // responses
        if ($responses = $route->getOption('responses')) {
            foreach ($responses as $response_code => $response_description) {
                $oas['responses'][$response_code] = [
                    'description' => $response_description,
                ];
            }
        }

        // Default response
        $oas['responses'] = [
            'default' => [
                'description' => 'Default response structure',
            ],
            '200'     => [
                '$ref' => '#/components/responses/succes',
            ],
            '201'     => [
                '$ref' => '#/components/responses/partial',
            ],
            '401'     => [
                '$ref' => '#/components/responses/unauthorized',
            ],
            '403'     => [
                '$ref' => '#/components/responses/forbidden',
            ],
            '404'     => [
                '$ref' => '#/components/responses/not_found',
            ],
            '500'     => [
                '$ref' => '#/components/responses/failed',
            ],
        ];

        // Note: To describe Header parameters named Accept, use the corresponding OpenAPI keywords: responses.<code>.content.<media-type>
        if ($accept = $route->getOption('accept')) {
            foreach ($accept as $media_type) {
                $oas['responses']['default']['content'][$media_type] = [];
            }
        }

        // add ...
        $path = $route->getPath();

        // ... for each methods
        $http_methods = array_map('strtolower', $route->getMethods());
        foreach ($http_methods as $http_method) {
            $this->documentation['paths'][$path][$http_method] = $oas;
        }
    }

    /**
     * @param string $file
     *
     * @return mixed
     * @throws COpenApiException
     */
    private function parseFile($file)
    {
        try {
            return Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new COpenApiException('Parse error : ' . $e->getMessage());
        }
    }

    /**
     * (dev_time php >= 7.1)
     *
     * @param string $file file path
     *
     * @return void
     *
     * @throws COpenApiException
     */
    private function validateOAS($file): void
    {
        if (!class_exists(Reader::class)) {
            return;
        }

        $cmd = $this->root . self::BIN_VALIDATOR . " validate {$file} 2>&1";
        exec($cmd, $output);

        if (count($output) > 1) {
            $msg = null;
            foreach ($output as $_error) {
                $msg .= "\n" . preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $_error);
            }

            throw new COpenApiException($msg);
        }
    }

    /**
     * @param mixed $route_type
     *
     * @return string|null
     */
    private function getParametersTypeOAS($route_type): ?string
    {
        if ($route_type === '\d+') {
            return 'integer';
        }
        if ($route_type === '\w+') {
            return 'string';
        }

        if ($route_type === '0|1' || $route_type === 'true|false') {
            return 'boolean';
        }

        return null;
    }

    /**
     * @param bool $convert_to_json
     *
     * @return mixed
     * @throws COpenApiException
     */
    public function getDocumentation($convert_to_json = false)
    {
        if (!$this->documentationExists()) {
            throw new COpenApiException('Documentation is missing');
        }

        return $this->parseFile($this->root . static::FILE_PATH);
    }
}
