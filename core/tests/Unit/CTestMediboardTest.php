<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Error;
use Ox\Core\CAppUI;
use Ox\Core\Module\CModule;
use Ox\Core\Tests\Unit\Models\CUnitTest;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CTestMediboardTest extends UnitTestMediboard {

  /**
   * RandomObjects
   *
   * @throws TestsException
   */
  public function testRandomObjects() {
    $user = $this->getRandomObjects(CMediusers::class, 1);
    $this->assertNotNull($user->_id);

    $users = $this->getRandomObjects(CMediusers::class, 2);
    $this->assertCount(2, $users);

    $users = $this->getRandomObjects(CMediusers::class, PHP_INT_MAX);
    $this->assertCount(100, $users);
  }

  /**
   * @param CModule $module
   *
   * @throws \Exception
   */
  public function testToogleModule() {
    $module           = new CModule();
    $module->mod_name = "Appfine";
    $module->loadMatchingObject();

    $is_active = $module->mod_active;

    $msg = static::toogleAcitveModule($module);
    $this->assertNull($msg);
    $this->asserttrue($is_active !== $module->mod_active);

    static::toogleAcitveModule($module);
  }

  /**
   *
   */
  public function testCurrentUser(): void {
    $user = CUser::get();
    $this->assertEquals('Phpunit', $user->user_first_name);
  }

  /**
   * SetConfig
   *
   * @config ref_pays 2
   */
  public function testSetConfig() {
    $this->assertEquals(CAppUI::conf("ref_pays"), 2);
  }

  /**
   * GetErrorCount
   */
  public function testGetErrorCount() {
    $this->assertIsNumeric($this->getErrorCount());
  }

  /**
   * Import
   */
  public function testImportObject() {
    // test if object alredy in bdd (error during removed object)
    $user                  = new CUser();
    $user->user_username   = 'BATMAN';
    $user->user_first_name = 'Bruce';
    $user->user_last_name  = 'WAYNE';
    $user->loadMatchingObject();

    if ($user->_id) {
      $user->delete();
    }

    $this->importObject("core/tests/data/mediuser.xml");
    $user    = new CUser();
    $where   = array();
    $where[] = "`user_username` LIKE 'BATMAN' AND `user_first_name` LIKE 'Bruce' AND `user_last_name` LIKE 'WAYNE'";
    $users   = $user->loadList($where);
    $this->assertCount(1, $users);
    $user = reset($users);
    $this->assertInstanceOf(CUser::class, $user);
    $profil = $user->loadRefProfiled();
    $this->assertEquals($profil->user_username, "superhero");
  }

  /**
   * @return void
   * @throws TestsException
   */
  public function testInvokePrivateStaticMethod(): void {
    $method = 'privateStaticMethod';
    $this->assertTrue($this->invokePrivateMethod(new CUnitTest, $method));
    $this->assertTrue($this->invokePrivateMethod(CUnitTest::class, $method));
  }

  /**
   * @return void
   * @throws TestsException
   */
  public function testInvokePrivateMethod(): void {
    $default_return = 'default';
    $method_name    = 'privateMethod';
    $args           = ['lorem', 'ipsum'];
    $other_args     = 'other_args';
    $obj            = new CUnitTest();
    $class_name     = CUnitTest::class;

    // whitout params
    $return = $this->invokePrivateMethod($obj, $method_name);
    $this->assertEquals($return, $default_return);

    $return = $this->invokePrivateMethod($class_name, $method_name);
    $this->assertEquals($return, $default_return);

    // with args
    $return = $this->invokePrivateMethod($obj, $method_name, $args);
    $this->assertEquals($return, $args);

    $return = $this->invokePrivateMethod($class_name, $method_name, $args);
    $this->assertEquals($return, $args);

    // with other args
    $return = $this->invokePrivateMethod($obj, $method_name, $args, $other_args);
    $this->assertEquals($return, $other_args);

    $return = $this->invokePrivateMethod($class_name, $method_name, $args, $other_args);
    $this->assertEquals($return, $other_args);
  }

  /**
   * @return void
   */
  public function testInvokePrivateMethodFaild(): void {
    $method_name = 'privateMethod';
    $obj         = new CUnitTest();

    // this is private
    $this->expectException(Error::class);
    $obj->$method_name();
  }

  /**
   * @return void
   * @throws TestsException
   */
  public function testGetPrivateConst(): void {
    $const_name = 'PRIVATE_CONST';
    $expected   = 'PRIVATE';
    $obj        = new CUnitTest();

    $this->assertEquals($expected, $this->getPrivateConst($obj, $const_name));
    $this->assertEquals($expected, $this->getPrivateConst(CUnitTest::class, $const_name));
  }

  /**
   * @param string|object $obj
   * @param string        $const_name
   *
   * @dataProvider getPrivateConstFailedProvider
   *
   * @return void
   * @throws TestsException
   */
  public function testGetPrivateConstFailed($obj, $const_name): void {
    $this->expectException(TestsException::class);
    $this->getPrivateConst($obj, $const_name);
  }

  /**
   * @return array
   */
  public function getPrivateConstFailedProvider() {
    return [
      'const_already_public'     => [new CUnitTest(), 'PUBLIC_CONST'],
      'class_does_not_exists'    => ['Not a class', 'PRIVATE_CONST'],
      'constante_does_no_exists' => [new CUnitTest(), 'NON_EXISTING_CONST'],
    ];
  }
}
