<?php
/**
 * @package Mediboard\Admin\Tests
 * @author  SARL OpenXtrem <dev@openxtrem.com>
 * @license GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 */

namespace Ox\Mediboard\Admin\Rgpd\Tests\Unit;

use Ox\Mediboard\Admin\Rgpd\CRGPDException;
use Ox\Mediboard\Admin\Rgpd\CRGPDManager;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

/**
 * Class CRGPDManagerTest
 */
class CRGPDManagerTest extends UnitTestMediboard {

  /**
   *
   * @throws CRGPDException
   * @throws TestsException
   */
  public function testConstruct() {
    $group   = $this->getRandomObjects(CGroups::class, 1);
    $manager = new CRGPDManager($group->_id);
    $this->assertInstanceOf(CRGPDManager::class, $manager);
  }

  /**
   *
   * @throws CRGPDException
   */
  public function testConstructFailed() {
    $this->expectException(CRGPDException::class);
    new CRGPDManager(null);
  }
}