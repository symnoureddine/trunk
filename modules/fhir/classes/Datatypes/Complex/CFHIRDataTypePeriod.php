<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDateTime;

/**
 * FHIR data type
 */
class CFHIRDataTypePeriod extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeDateTime */
  public $start;

  /** @var CFHIRDataTypeDateTime */
  public $end;
}
