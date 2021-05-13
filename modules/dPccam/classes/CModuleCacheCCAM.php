<?php
/**
 * @package Mediboard\dPcim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Module\CAbstractModuleCache;

/**
 * Description
 */
class CModuleCacheCCAM extends CAbstractModuleCache {
  public $module = 'dPccam';

  protected $shm_patterns = array(
    CCodeCCAM::class,
    COldCodeCCAM::class,
    CDatedCodeCCAM::class,
    CDentCCAM::class,
    CCodeNGAP::class,
    CCCAM::class,
    CActiviteModificateurCCAM::class,
    CActiviteCCAM::class,
    CActiviteClassifCCAM::class,
    CInfoTarifCCAM::class,
  );
}
