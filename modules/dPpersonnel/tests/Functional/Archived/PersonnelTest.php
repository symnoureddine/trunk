<?php
/**
 * @package Mediboard\Personnel\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * PersonnelTest
 *
 * @description Test create and edit a personnel
 * @screen      PersonnelPage
 */
class PersonnelTest extends SeleniumTestMediboard {

  public $personnelName = "SELENIUM";
  public $personnelType = "instrumentiste";
  public $personnelOtherType = "manipulateur";

  /**
   * Création d'un personnel
   */
  public function testCreatePersonnelOk() {
    $page = new PersonnelPage($this);

    $page->createPersonnel($this->personnelName, $this->personnelType);
    $this->assertEquals("Personnel créé", $page->getSystemMessage());

    $page->searchPersonnel($this->personnelName, $this->personnelType);
    $this->assertContains($this->personnelName, $page->getPersonnelName());
    $this->assertContains(ucfirst($this->personnelType), $page->getPersonnelType());
  }

  /**
   * Modification d'un personnel
   */
  public function testEditPersonnelOk() {
    $page = new PersonnelPage($this);

    $page->createPersonnel($this->personnelName, $this->personnelType);
    $this->assertEquals("Personnel créé", $page->getSystemMessage());

    $page->searchPersonnel($this->personnelName, $this->personnelType);

    $page->editPersonnel($this->personnelName, $this->personnelOtherType);
    $this->assertEquals("Personnel modifié", $page->getSystemMessage());

    $page->searchPersonnel($this->personnelName, $this->personnelOtherType);
    $this->assertContains($this->personnelName, $page->getPersonnelName());
    $this->assertContains(ucfirst($this->personnelOtherType), $page->getPersonnelType());
  }
}