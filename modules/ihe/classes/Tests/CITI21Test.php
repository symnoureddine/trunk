<?php
/**
 * PDQ - ITI-21 - Tests
 *
 * @category IHE
 * @package  Mediboard
 * @author   SARL OpenXtrem <dev@openxtrem.com>
 * @license  GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @version  SVN: $Id:$
 * @link     http://www.mediboard.org
 */

namespace Ox\Interop\Ihe\Tests;

use Ox\Core\CMbException;
use Ox\Interop\Connectathon\CCnStep;

/**
 * Class CITI21Test
 * PDQ - ITI-21 - Tests
 */
class CITI21Test extends CIHETestCase {
  /**
   * Test Q22 - Find Candidates
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testQ22(CCnStep $step) {
    $profil      = "PDQ";
    $transaction = "ITI21";
    $message     = "QBP";
    $code        = "Q22";

    mbTrace($step);

    // PDQ_Multiple_Query
    switch ($step->number) {
      case '20':
        // PID.5.1.1 = MOO*
        break;


      default:
    }
  }
}