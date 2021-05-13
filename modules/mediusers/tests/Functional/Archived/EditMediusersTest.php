<?php
/**
 * @package Mediboard\Mediusers\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Mediboard\Mediusers\Tests\Functional\Pages\MediusersPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Test mediuser edition
 *
 * @description Test the mediusers' edition
 *
 * @screen MediusersPage
 */
class EditMediusersTest extends SeleniumTestMediboard {

  /** @var MediusersPage $page */
  public $page = null;

  /**
   * @inheritdoc
   */
  public function setUpPage() {
    parent::setUpPage();
    $this->page = new MediusersPage($this);
  }

  /**
   * Change le profil d'un utilisateur avec un utilisateur Administrateur
   */
  public function testChangeUserProfileWithAdminOk() {
    $this->importObject("mediusers/tests/Functional/data/mediuser.xml");
    $type = "Administrator";
    $profile = "SI";
    $this->page->searchMediusers("WAYNE");
    $this->page->editMediuserByName("WAYNE", $type, $profile);
    $values = $this->page->getMediusersInfos("BATMAN");

    $this->assertContains($type, $values['type']);
    $this->assertContains($profile, $values['profile']);
  }

  /**
   * Vérifie l'impossibilité de changer le profil et le type d'un utilisateur lorsque l'on n'est pas admin
   */
  public function testChangeUserProfileWithoutAdminKo() {
    $this->importObject("mediusers/tests/Functional/data/mediuser_secretaire.xml");
    $this->importObject("mediusers/tests/Functional/data/mediuser.xml");

    $this->page->changeUser("JOHNSNOW");
    sleep(2);
    $this->page->searchMediusers("WAYNE");
    $this->page->editMediuserByName("WAYNE");
    $this->assertFalse($this->page->canEditProfile());
    $this->assertFalse($this->page->canSetAdmin());
  }

  /**
   * @inheritdoc
   */
  public function tearDown() {
    $this->page->changeToDefaultTestUser();
    parent::tearDown();
  }

  /**
   * Test l'ajout d'un compte de facturation par défaut
   *
   * @config ref_pays 2
   */
  public function testAddDefaultCompteFacturationOk() {
    $this->importObject("mediusers/tests/Functional/data/mediuser.xml");
    $this->page->searchMediusers("WAYNE");
    $this->page->editMediuserByName("WAYNE");
    $compte_facturation = $this->page->editCompteFactutation();
    $this->assertEquals(1, $compte_facturation);
  }
}