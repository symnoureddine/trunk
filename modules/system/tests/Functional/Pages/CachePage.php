<?php
/**
 * @package Mediboard\System\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional\Pages;


use Ox\Tests\HomePage;

/**
 * System page representation
 */
class CachePage extends HomePage {

  protected $module_name = "system";
  protected $tab_name    = "view_cache";

  protected $feedback = null;

   /**
   * Empty the cache of the current Mediboard instance
   *
   * @return void
   */
  public function clearCache() {
    $driver = $this->driver;
    $driver->wait(500);
    $driver->byCssSelector('tr#cache-all > td.current-server > button')->click();
    $driver->wait(100);
    $driver->byCssSelector('div#control_window_2 > div:nth-child(2) > button:nth-child(1)')->click();
    $driver->wait(200);
    $this->feedback = $driver->findElementsByCss('#CacheManagerFeedback div');
  }

  /**
   * Returns the content of the clear cache action feedback
   *
   * @return array|null
   */
  public function getFeedBack() {
    return $this->feedback;
  }
}