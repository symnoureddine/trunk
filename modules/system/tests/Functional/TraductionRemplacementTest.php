<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional;

use Ox\Mediboard\System\Tests\Functional\Pages\CachePage;
use Ox\Mediboard\System\Tests\Functional\Pages\TraductionRemplacementPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * TraductionRemplacementTest
 *
 * @description Test paramétrage Traduction de remplacement
 * @screen      TraductionRemplacementPage
 */
class TraductionRemplacementTest extends SeleniumTestMediboard {
  public $old_translation = "Traductions de remplacement";
  public $new_translation = "Test de remplacement";

  /**
   * Création d'une traduction de remplacement
   */
  public function testCreateTraduction() {
    $this->markTestSkipped('Not tested on gitlab-ci');
    $systemPage = new CachePage($this);
    $systemPage->clearCache();

    $page = new TraductionRemplacementPage($this);
    $page->createTraduction($this->old_translation, $this->new_translation);

    $systemPage = new CachePage($this);
    $systemPage->clearCache();

    $page = new TraductionRemplacementPage($this);
    $name_tab = $page->getNameTabTraduction();
    $this->assertContains($this->new_translation, $name_tab);
  }

}