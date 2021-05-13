<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * AffectationPersonnelTest
 *
 * @description Test ajout d'un horaire pour l'affectation de personnel
 * @screen      HorairesAffectationPage
 */
class AffectationPersonnelTest extends SeleniumTestMediboard {
  /** @var HorairesAffectationPage $page */
  public $page = null;
  public $nom = "H1";
  public $debut = "10";
  public $fin = "14";
  public $personnel = "CHIR Test";

  /**
   * Ajout d'un horaire pour l'affectation de personnel
   */
  public function testAddHoraireOk() {
    $this->page = new HorairesAffectationPage($this);
    $nb_horaire_sibling = $this->page->testAddHoraire($this->nom, $this->debut, $this->fin);
    $this->assertEquals("Horaire créé", $this->page->getSystemMessage());
    $this->assertEquals($nb_horaire_sibling, 1);
  }

  /**
   * Ajout d'une affectation de personnel pour un horaire donné
   *
   * @config [CConfiguration] soins UserSejour see_global_users 1
   * @config [CConfiguration] soins UserSejour type_affectation segment
   */
  public function testAddHorairePersonnelOk() {
    $this->page = new SejourPage($this);
    $this->importObject("soins/tests/Functional/data/time_user_sejour.xml");
    $this->importObject("soins/tests/Functional/data/sejour_place.xml");
    $this->page->testAddHorairePersonnel($this->nom, $this->personnel);

    $this->assertEquals("Personnel affecté", $this->page->getSystemMessage());
  }

  /**
   * Ajout d'ajout d'un responsable du personnel pour le jour courant dans un service
   *
   * @config [CConfiguration] soins UserSejour can_edit_user_sejour 1
   */
  public function testAddResponsableJourOk() {
    $this->page = new SejourPage($this);
    $this->page->testAddResponsableJour($this->personnel);
    $this->assertContains("Affectation d'utilisateur pour le service créée", $this->page->getSystemMessage());
  }
}