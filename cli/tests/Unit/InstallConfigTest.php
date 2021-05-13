<?php
/**
 * @package Mediboard\\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Cli\Console\InstallConfig;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;


/**
 * Class InstallConfigTest
 */
class InstallConfigTest extends UnitTestMediboard {

  /**
   * @group schedules
   * @throws TestsException
   */
  public function testAddDefaultConfigs() {
    $installConfig = new InstallConfig();
    $this->assertEmpty($installConfig->getConfigs());
    $this->assertInstanceOf(InstallConfig::class, $this->invokePrivateMethod($installConfig, 'addDefaultConfigs'));
    $this->assertNotEmpty($installConfig->getConfigs());

    return $installConfig;
  }

  /**
   * @group schedules
   * @depends testAddDefaultConfigs
   */
  public function testConvertConfigs(InstallConfig $installConfig) {
    $reflection = new ReflectionClass($installConfig);
    $property   = $reflection->getProperty('configs');
    $property->setAccessible(true);
    $inputs = $this->getInputs(true);

    $property->setValue($installConfig, array_merge($installConfig->getConfigs(), $inputs));
    $this->invokePrivateMethod($installConfig, 'ConvertConfigs');
    $configs_convert = $installConfig->getConfigs();

    $this->assertArrayNotHasKey('database_host', $configs_convert);
    $this->assertEquals($configs_convert['db']['std']['dbhost'], $inputs['database_host']);

    $this->assertArrayNotHasKey('mutex_driver_files', $configs_convert);
    $this->assertEquals($configs_convert['mutex_drivers']['CMbFileMutex'], $inputs['mutex_driver_files']);
  }

  /**
   * @return Command
   */
  private function makeCommand(): Command {
    $application = new Application();
    $application->add(new InstallConfig);

    return $application->find('install:config');
  }

  /**
   * @group schedules
   */
//  public function testExecuteLogicException() {
//    $this->markTestSkipped('Need ref config');
//    $command        = $this->makeCommand();
//    $command_tester = new CommandTester($command);
//
//    $this->expectExceptionMessageRegExp('/The configuration file (.*) already exists/');
//    $command_tester->execute([]);
//  }

  /**
   * @group schedules
   */
//  public function testExecuteInvalidArgumentException() {
//    $this->markTestSkipped('Need ref config');
//    $command        = $this->makeCommand();
//    $command_tester = new CommandTester($command);
//
//    $this->expectExceptionMessage('is not a valid directory');
//    $command_tester->execute([
//      '--path' => 'toto/tata/titi'
//    ]);
//  }

  /**
   * @param bool $sanitize_value
   *
   * @return array
   */
  private function getInputs($sanitize_value = false): array {
    $inputs = [
      'product_name'             => 'Mediboard_test',
      'company_name'             => 'ox',
      'page_title'               => 'MediTest',
      'root_dir'                 => 'var/www/html',
      'base_url'                 => 'http://localhost/mediboard_test/',
      'external_url'             => 'http://localhost/mediboard_test/',
      'instance_role'            => 'qualif',
      'database_host'            => '127.0.0.1',
      'database_name'            => 'mediboard_test',
      'database_user'            => 'root',
      'database_pass'            => '',
      'shared_memory'            => 'disk',
      'shared_memory_distribued' => 'disk',
      'session_handler'          => 'files',
      'mutex_driver_files'       => 'yes',
      'mutex_driver_apc'         => 'no',
      'mutex_driver_redis'       => 'no'
    ];

    if ($sanitize_value) {
      foreach ($inputs as $key => &$value) {
        // sanitize
        $value = $value === 'yes' ? 1 : $value;
        $value = $value === 'no' ? '' : $value;
        $value = str_replace('/', '\/', $value);
      }
    }

    return $inputs;
  }

  /**
   * @group schedules
   */
//  public function testExecuteSuccess() {
//    $this->markTestSkipped('Need ref config');
//    $command        = $this->makeCommand();
//    $command_tester = new CommandTester($command);
//
//    $inputs_value   = array_values($this->getInputs());
//    $inputs_value[] = 'no';  // save changes
//
//    $command_tester->setInputs($inputs_value);
//
//    $command_tester->execute([
//      '--path' => dirname(__DIR__, 3) . '/tmp'
//    ]);
//
//    $output = $command_tester->getDisplay();
//
//    // check resume
//    foreach ($this->getInputs(true) as $key => $value) {
//      $pattern = '/' . $key . '(.*)' . $value . '/';
//      $this->assertRegExp($pattern, $output);
//    }
//
//    // not configurations successfully saved
//    $this->assertStringEndsWith('Do you confirm this settings [Y/n] ?', $output);
//  }
}
