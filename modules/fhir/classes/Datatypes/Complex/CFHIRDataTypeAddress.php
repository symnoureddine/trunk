<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;

use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;

/**
 * FHIR data type
 */
class CFHIRDataTypeAddress extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeCode */
  public $use;

  /** @var CFHIRDataTypeCode */
  public $type;

  /** @var CFHIRDataTypeString */
  public $text;

  /** @var CFHIRDataTypeString[] */
  public $line;

  /** @var CFHIRDataTypeString */
  public $city;

  /** @var CFHIRDataTypeString */
  public $district;

  /** @var CFHIRDataTypeString */
  public $state;

  /** @var CFHIRDataTypeString */
  public $postalCode;

  /** @var CFHIRDataTypeString */
  public $country;

  /** @var CFHIRDataTypePeriod */
  public $period;
}
