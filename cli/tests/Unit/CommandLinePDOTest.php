<?php

use Ox\Cli\CommandLinePDO;
use Ox\Core\CAppUI;
use Ox\Tests\UnitTestMediboard;

/**
 * @package Mediboard\\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
class CommandLinePDOTest extends UnitTestMediboard {

  /**
   * @return string
   */
  private function getRandomDatabaseName(): string {
    return uniqid('mediboard_');
  }

  /**
   * @group schedules
   * @return CommandLinePDO
   * @throws Exception
   */
  public function test__construct(): CommandLinePDO {
    $host   = CAppUI::conf('db std dbhost');
    $user   = CAppUI::conf('db std dbuser');
    $pass   = CAppUI::conf('db std dbpass');

    $pdo = new CommandLinePDO($host, $user, $pass);
    $this->assertInstanceOf(CommandLinePDO::class, $pdo);

    return $pdo;
  }

  /**
   * @depends test__construct
   *
   * @param CommandLinePDO $pdo
   *
   * @group   schedules
   * @throws Exception
   */
  public function testIsDatabaseExistsOk(CommandLinePDO $pdo) {
    $database = CAppUI::conf('db std dbname');
    $this->assertTrue($pdo->isDatabaseExists($database));
  }

  /**
   * @depends test__construct
   * @group   schedules
   *
   * @param CommandLinePDO $pdo
   */
  public function testIsDatabaseExistsKo(CommandLinePDO $pdo) {
    $this->assertFalse($pdo->isDatabaseExists($this->getRandomDatabaseName()));
  }

  /**
   * @depends test__construct
   *
   * @param CommandLinePDO $pdo
   *
   * @group   schedules
   * @throws \Ox\Tests\TestsException
   */
  public function testQueryDump(CommandLinePDO $pdo) {
    $path    = dirname(__FILE__, 3) . '/sql/mediboard.sql';
    $queries = $this->invokePrivateMethod($pdo, 'queryDump', $path);
    $this->assertIsArray($queries);
    $this->assertNotEmpty($queries);
  }

  /**
   * @group   schedules
   * @depends test__construct
   */
  public function testCreateAndDeleteDatabase(CommandLinePDO $pdo) {
    $database = $this->getRandomDatabaseName();
    $this->assertTrue($pdo->createDatabase($database));
    $this->assertTrue($pdo->dropDatabase($database));
  }
}

