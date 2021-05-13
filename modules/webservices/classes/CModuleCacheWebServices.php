<?php
/**
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Webservices;

use Ox\Core\Module\CAbstractModuleCache;
use Ox\Core\CacheManager;
use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Interop\Webservices\Wsdl\FileWSDLRepository;
use Ox\Interop\Webservices\Wsdl\WSDLRepository;

/**
 * Class CModuleCacheWebServices
 *
 * @package Ox\Interop\Webservices
 */
class CModuleCacheWebServices extends CAbstractModuleCache {
  public $module = 'webservices';

  /**
   * @inheritdoc
   * @throws CMbException
   */
  public function clearSpecialActions(): void {
    // Remove cache for soap server
    $repo_server  = new WSDLRepository(null, "soap_server");
    $count_delete = $repo_server->flush();
    CacheManager::output("module-webservices-cache removal soap", CAppUI::UI_MSG_OK, $count_delete, "soap_server");

    // Remove cache for soap client
    $repo_client  = new WSDLRepository(new FileWSDLRepository("soap_client"));
    $count_delete = $repo_client->flush();
    CacheManager::output("module-webservices-cache removal soap", CAppUI::UI_MSG_OK, $count_delete, "soap_client");
  }
}
