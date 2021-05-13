<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use Ox\Mediboard\System\Tests\Functional\Pages\CachePage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CacheTest
 *
 * @description Tests to clear cache from the Mediboard instance UI
 *
 * @screen SystemPage
 */
class CacheTest extends SeleniumTestMediboard {

  /**
   * Test du vidage de cache de l'instance
   */
  public function testClearCache() {
    $cachePage = new CachePage($this);

    $cachePage->clearCache();

    $this->assertTrue(!empty($cachePage->getFeedBack()));
  }
}