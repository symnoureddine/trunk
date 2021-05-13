<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Exception;
/**
 * FHIR Exception
 */
class CFHIRExceptionNotFound extends CFHIRException {
  /**
   * CFHIRException constructor.
   *
   * @param string $message  Message to display
   * @param int    $code     HTTP code
   * @param null   $previous Previous exception
   */
  public function __construct($message = "Not found", $code = 404, $previous = null) {
    parent::__construct($message, $code, $previous);
  }
}
