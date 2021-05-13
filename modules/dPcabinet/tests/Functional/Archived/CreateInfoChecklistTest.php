<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * InfoChecklist
 *
 * @description Test the creation of a info for consult
 *
 * @screen InfoChecklistPage
 */
class CreateInfoChecklistTest extends SeleniumTestMediboard {

  public $name_info = "NomInfo";

  /**
   * Création d'une info de checklist au niveu de l'établissement
   */
  public function testCreateInfoChecklistOk() {
    $page = new InfoChecklistPage($this);
    $page->testCreateInfoChecklistOk($this->name_info);
    $this->assertEquals("Information créée", $page->getSystemMessage());
  }
}