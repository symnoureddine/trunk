<?php
/**
 * @package Mediboard\dPcim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cim10;

use Ox\Core\Module\CAbstractModuleCache;
use Ox\Mediboard\Cim10\Atih\CCIM10CategoryATIH;
use Ox\Mediboard\Cim10\Atih\CCodeCIM10ATIH;
use Ox\Mediboard\Cim10\Cisp\CCISP;
use Ox\Mediboard\Cim10\Drc\CDRCConsultationResult;
use Ox\Mediboard\Cim10\Gm\CCategoryCIM10GM;
use Ox\Mediboard\Cim10\Gm\CCodeCIM10GM;
use Ox\Mediboard\Cim10\Oms\CCodeCIM10OMS;

/**
 * Description
 */
class CModuleCacheCIM10 extends CAbstractModuleCache {
  public $module = 'dPcim10';

  protected $shm_patterns = array(
    CCodeCIM10OMS::class,
    CCodeCIM10ATIH::class,
    CDRCConsultationResult::class,
    CCIM10CategoryATIH::class,
    CCategoryCIM10GM::class,
    CCodeCIM10GM::class,
    CCISP::class,
  );
}
