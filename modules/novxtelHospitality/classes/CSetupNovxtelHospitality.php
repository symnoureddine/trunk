<?php
/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\NovxtelHospitality;

use Ox\Core\CSetup;

/**
 * Novxtel - Hospitality Setup class
 */
class CSetupNovxtelHospitality extends CSetup {
  /**
   * @see parent::__construct()
   */
  function __construct() {
    parent::__construct();
    
    $this->mod_name = "novxtelHospitality";
    $this->makeRevision("0.0");
    
    $this->mod_version = "0.01";    
  }
}
