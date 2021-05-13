<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;

/**
 * FHIR data type
 */
class CFHIRDataTypeReference extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeString */
  public $reference;

  /** @var CFHIRDataTypeIdentifier */
  public $identifier;

  /** @var CFHIRDataTypeString */
  public $display;
}
