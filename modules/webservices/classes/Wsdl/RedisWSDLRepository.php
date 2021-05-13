<?php
/**
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Webservices\Wsdl;

use Countable;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CWSDL;
use Ox\Core\DSHM;
use Ox\Core\Shm\RedisSharedMemory;

/**
 * Redis implementation of WSDLRepositoryInterface
 */
class RedisWSDLRepository implements WSDLRepositoryInterface, IShortNameAutoloadable, Countable {
  use WSDLNameGeneratorTrait;

  /** @var string */
  private $prefix;

  /**
   * RedisWSDLRepository constructor.
   *
   * @param string|null $prefix
   *
   * @throws CMbException
   */
  public function __construct(string $prefix) {
    $engine = DSHM::getEngine();

    if (!$engine instanceof RedisSharedMemory) {
      throw new CMbException("RedisWSDLRepository-error-'%s' is not a Redis engine", get_class($engine));
    }

    $this->prefix = $prefix;
  }

  /**
   * Generate a WSDL Redis key
   *
   * @param string $wsdl_name WSDL name
   *
   * @return string
   */
  private function generateWSDLKey(string $wsdl_name): string {
    return "{$this->prefix}_{$wsdl_name}";
  }

  /**
   * @inheritDoc
   */
  public function find(?string $login, ?string $token, string $module, string $tab, string $classname, string $wsdl_mode): ?CWSDL {
    $wsdl_name = static::generateWSDLName($login, $token, $module, $tab, $classname);
    $wsdl_key  = $this->generateWSDLKey($wsdl_name);

    $wsdl_content = DSHM::get($wsdl_key);

    if (!$wsdl_content) {
      return null;
    }

    $wsdl = WSDLFactory::createFromString($wsdl_mode, $classname, $wsdl_name, $wsdl_content);

    if ($wsdl->loadXML($wsdl_content) === false) {
      throw new CMbException('FileWSDLRepository-error-Unable to load WSDL XML content');
    }

    return $wsdl;
  }

  /**
   * @inheritDoc
   */
  public function save(CWSDL $wsdl) {
    $wsdl_key = $this->generateWSDLKey($wsdl->getName());

    return (DSHM::put($wsdl_key, $wsdl->saveXML(), 600) === true);
  }

  /**
   * @inheritDoc
   */
  public function delete(CWSDL $wsdl) {
    $wsdl_key = $this->generateWSDLKey($wsdl->getName());

    return (bool)DSHM::rem($wsdl_key);
  }

  /**
   * @inheritDoc
   */
  public function flush() {
    return DSHM::remKeys("{$this->prefix}*");
  }

  /**
   * @inheritDoc
   */
  public function count() {
    $infos = DSHM::getInfo();

    return CMbArray::getRecursive($infos, "entries_by_prefix $this->prefix count", 0);
  }
}
