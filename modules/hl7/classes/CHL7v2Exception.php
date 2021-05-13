<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use Ox\Core\CAppUI;
use Ox\Core\CMbException;

class CHL7v2Exception extends CMbException {
  const EMPTY_MESSAGE              = 1;
  const WRONG_MESSAGE_TYPE         = 2;
  const INVALID_SEPARATOR          = 3;
  const SEGMENT_INVALID_SYNTAX     = 4;
  const UNKOWN_SEGMENT_TYPE        = 5;
  const UNEXPECTED_SEGMENT         = 6;
  const TOO_MANY_FIELDS            = 7;
  const SPECS_FILE_MISSING         = 8;
  const VERSION_UNKNOWN            = 10;
  const INVALID_DATA_FORMAT        = 11;
  const FIELD_EMPTY                = 12;
  const TOO_MANY_FIELD_ITEMS       = 13;
  const SEGMENT_MISSING            = 14;
  const MSG_CODE_MISSING           = 15;
  const UNKNOWN_AUTHORITY          = 16;
  const UNEXPECTED_DATA_TYPE       = 17;
  const DATA_TOO_LONG              = 18;
  const UNKNOWN_TABLE_ENTRY        = 19;
  const EVENT_UNKNOWN              = 20;
  const FIELD_FORBIDDEN            = 21;
  const UNKNOWN_MSG_CODE           = 22;
  const UNKNOWN_DOMAINS_RETURNED   = 23;
  const INVALID_DATA_SOURCE         = 24;

  public $extraData;
  
  // argument 2 must be named "code" ...
  public function __construct($id, $code = 0) {
    $args = func_get_args();
    $args[0] = "CHL7v2Exception-$id";

    $this->extraData = $code;
    $message = call_user_func_array(array(CAppUI::class, "tr"), $args);

    parent::__construct($message, $id); 
  }
}
