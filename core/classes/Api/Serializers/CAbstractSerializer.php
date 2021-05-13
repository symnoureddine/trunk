<?php
/**
 * @package Mediboard\core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Serializers;

use Ox\Core\Api\Resources\CAbstractResource;

/**
 * Class CAbstractSerializer
 */
abstract class CAbstractSerializer
{

    /** @var CAbstractResource */
    protected $resource;

    /**
     * CAbstractSerializer constructor.
     *
     * @param CAbstractResource $resource
     */
    public function __construct(CAbstractResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return array
     */
    abstract public function serialize(): array;
}
