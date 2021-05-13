<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Etablissement;

use Ox\Core\Module\CAbstractModuleCache;

/**
 * Description
 */
class CModuleCacheEtablissement extends CAbstractModuleCache {
  public $module = 'dPetablissement';

  protected $shm_patterns = array(
    CGroups::class,
  );
}
