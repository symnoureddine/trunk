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
class CFHIRDataTypeHumanName extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeCode */
  public $use;

  /** @var CFHIRDataTypeString */
  public $text;

  /** @var CFHIRDataTypeString[] */
  public $family;

  /** @var CFHIRDataTypeString[] */
  public $given;

  /** @var CFHIRDataTypeString[] */
  public $prefix;

  /** @var CFHIRDataTypeString[] */
  public $suffix;

  /** @var CFHIRDataTypePeriod */
  public $period;
}
