<?php
/**
 * @package Mediboard\Mediusers\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;


/**
 * Test creation of a mediusers
 *
 * @description Test the creation of a mediuser with different profiles
 *
 * @screen MediusersPage
 *
 * Needs a function named Default
 */
class CreateMediusersTest extends SeleniumTestMediboard {

  public $username = "JOHNDOE";
  public $name = "DOE";
  public $function = "Default";

  /**
   * Créé un Mediuser de type *Administrator* avec le profil *SI*
   */
  public function testCreateAdminMediusersWithAdminOk() {
    $page = new MediusersPage($this);
    $type = "Administrator";
    $profile = "SI";
    $page->createMediusers($this->username, null, "Default", $type, $profile, $this->name);
    $values = $page->getMediusersInfos($this->username);
    $this->assertContains($type, $values['type']);
    $this->assertContains($profile, $values['profile']);
  }

  /**
   * Vérifie l'impossibilité de créer un Mediuser de type *Administrator* avec le profil *SI* lorsqu'on est pas admin
   */
  public function testCreateAdminMediusersWithoutAdminKo() {
    $page = new MediusersPage($this);
    $this->importObject("mediusers/tests/Functional/data/mediuser_secretaire.xml");
    $page->changeUser("JOHNSNOW");
    $type = "Administrator";
    $profile = "SI";
    $page->createMediusers($this->username, null, "Default", $type, $profile, $this->name);
    $msg = $this->acceptAlert(1000);
    $this->assertContains("Type", $msg);
  }

}