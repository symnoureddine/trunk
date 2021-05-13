<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\DEC;

use Ox\Interop\Hl7\Events\CHL7v2Event;
use Ox\Interop\Ihe\CIHE;

/**
 * Classe CHL7v2EventDEC 
 * Device Enterprise Communication
 */
class CHL7v2EventDEC extends CHL7v2Event implements CHL7EventDEC {
  public $event_type = "ORU";

  /**
   * Construct
   *
   * @return CHL7v2EventDEC
   */
  function __construct() {
    parent::__construct();
    
    $this->profil      = "DEC";
    $this->msg_codes   = array ( 
      array(
        $this->event_type, $this->code
      )
    );

    $this->transaction = CIHE::getDECTransaction($this->code);
  }
}