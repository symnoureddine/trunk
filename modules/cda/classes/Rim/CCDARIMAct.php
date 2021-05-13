<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Rim;

use Ox\Interop\Cda\CCDAClasseCda;
use Ox\Interop\Cda\Datatypes\Base\CCDABL;
use Ox\Interop\Cda\Datatypes\Base\CCDACD;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDACS;
use Ox\Interop\Cda\Datatypes\Base\CCDAED;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAST;

/**
 * CCDARIMAct Class
 */
class CCDARIMAct extends CCDAClasseCda {

  /**
   * @var CCDACS
   */
  public $classCode;

  /**
   * @var CCDACS
   */
  public $moodCode;

  /**
   * @var CCDACD
   */
  public $code;

  /**
   * @var CCDABL
   */
  public $negationInd;

  /**
   * @var CCDAST
   */
  public $derivationExpr;

  /**
   * @var CCDAED
   */
  public $title;

  /**
   * @var CCDAED
   */
  public $text;

  /**
   * @var CCDACS
   */
  public $statusCode;

  /**
   * @var CCDAIVL_TS
   */
  public $effectiveTime;

  /**
   * @var CCDAIVL_TS
   */
  public $activityTime;

  /**
   * @var CCDABL
   */
  public $interruptibleInd;

  /**
   * @var CCDACE
   */
  public $levelCode;

  /**
   * @var CCDABL
   */
  public $independentInd;

  /**
   * @var CCDACE
   */
  public $uncertaintyCode;

  /**
   * @var CCDACE
   */
  public $languageCode;

  public $id                   = array();
  public $priorityCode         = array();
  public $confidentialityCode  = array();
  public $repeatNumber         = array();
  public $reasonCode           = array();

}