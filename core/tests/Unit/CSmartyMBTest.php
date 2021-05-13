<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use DateTime;
use Ox\Core\CSmartyMB;
use Ox\Tests\UnitTestMediboard;
use PHPUnit\Framework\Error\Warning;

/**
 * Description
 */
class CSmartyMBTest extends UnitTestMediboard {
  public function test_it_does_not_warn_when_namespaced_classes(): void {
    $smarty = $this->getMockBuilder(CSmartyMB::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['_const', '_static', 'static_call'])
      ->getMock();

    $atom_format          = $smarty->_const('\Datetime', 'ATOM');
    $model_object_spec    = $smarty->_static('\Ox\Core\CModelObject', 'spec');
    $datetime_from_format = $smarty->static_call('\Datetime::createFromFormat', 'j-M-Y', '15-Feb-2009');

    $this->assertEquals("Y-m-d\TH:i:sP", $atom_format);
    $this->assertIsArray($model_object_spec);
    $this->assertInstanceOf(DateTime::class, $datetime_from_format);
  }

  public function test_it_warns_about_non_namespaced_classes_when_const(): void {
    $smarty = $this->getMockBuilder(CSmartyMB::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['_const'])
      ->getMock();

    $this->expectWarning();
    $smarty->_const('Datetime', 'ATOM');
  }

  public function test_it_warns_about_non_namespaced_classes_when_static(): void {
    $smarty = $this->getMockBuilder(CSmartyMB::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['_static'])
      ->getMock();

    $this->expectWarning();
    $smarty->_static('CModelObject', 'spec');
  }

  public function test_it_warns_about_non_namespaced_classes_when_static_call(): void {
    $smarty = $this->getMockBuilder(CSmartyMB::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['static_call'])
      ->getMock();

    $this->expectWarning();
    $smarty->static_call('Datetime::createFromFormat', 'j-M-Y', '15-Feb-2009');
  }
}
