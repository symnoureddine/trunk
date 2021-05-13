<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use Ox\Interop\Fhir\Datatypes\CFHIRDataType;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;

/**
 * FHIR data type
 */
class CFHIRDataTypeExtension extends CFHIRDataTypeComplex {
  /** @var CFHIRDataTypeUri */
  public $url;

  /** @var CFHIRDataType */
  public $value;
}
