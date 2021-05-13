<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypePositiveInt;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;

/**
 * FHIR data type
 */
class CFHIRDataTypeContactPoint extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeCode */
  public $system;

  /** @var CFHIRDataTypeString */
  public $value;

  /** @var CFHIRDataTypeCode */
  public $use;

  /** @var CFHIRDataTypePositiveInt */
  public $rank;
}
