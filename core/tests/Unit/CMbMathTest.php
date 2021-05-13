<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Ox\Core\CMbMath;
use Ox\Tests\UnitTestMediboard;

class CMbMathTest extends UnitTestMediboard {
  public function testFormulaEvaluation() {
    // Todo: Create a functional test in order to compare CMbMath and JSExpressionEval
    $expressions = array(
      '1+2'                              => 3,
      '$a+$b'                            => 3,
      '$a+$b * ($c - $e)'                => -13,
      'floor($a * 10 / 4)'               => 2,
      'cos($a * 10 / 4)'                 => -0.80114361554693,
      'Min(1058087)'                     => 18,
      'J(1502803159000 - 1501593559000)' => 14,
    );

    $variables = array(
      'a' => 1,
      'b' => 2,
      'c' => 3,
      'd' => 4,
      'e' => 10,
    );

    foreach ($expressions as $_expression => $_result) {
      $this->assertEquals($_result, CMbMath::evaluate($_expression, $variables));
    }
  }

  public function testIsValidExponential(){
    $this->assertTrue(CMbMath::isValidExponential('2', 256));
    $this->assertTrue(CMbMath::isValidExponential('3', 59049));
    $this->assertFalse(CMbMath::isValidExponential('2', 123456));
    $this->assertFalse(CMbMath::isValidExponential('3', 654321));
  }
}
