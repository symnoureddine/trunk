<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Kernel\Routing;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

/**
 * Class CRouter
 */
class CRouter extends Router
{

    /** @var string */
    public const CACHE_DIR = '/tmp/cache_sf/';

    /** @var string */
    public const RESOURCE = 'includes/all_routes.yml';

    /** @var CRouter */
    private static $instance;

    /**
     * CRouter constructor.
     */
    public function __construct()
    {
        $root_dir = dirname(__DIR__, 4);

        $fileLocator = new FileLocator($root_dir);

        $loader = new YamlFileLoader($fileLocator);

        $options = [
            'cache_dir'             => $root_dir . static::CACHE_DIR
        ];

        $context = new RequestContext();

        parent::__construct($loader, 'includes/all_routes.yml', $options, $context);

        self::$instance = $this;
    }


    /**
     * @return CRouter
     */
    public static function getInstance(): CRouter
    {
        if (is_null(self::$instance)) {
            self::$instance = new CRouter();
        }

        return self::$instance;
    }

    /**
     * @param             $name
     * @param array       $parameters
     * @param int         $referenceType
     * @param null|string $base_url
     *
     * @return string
     */
    public static function generateUrl(
        $name,
        $parameters = [],
        $referenceType = self::ABSOLUTE_PATH,
        $base_url = null
    ): string {
        $url_generator = self::getInstance()->getGenerator();
        if ($base_url) {
            $url_generator->setContext(new RequestContext($base_url));
        }

        return $url_generator->generate($name, $parameters, $referenceType);
    }

}
