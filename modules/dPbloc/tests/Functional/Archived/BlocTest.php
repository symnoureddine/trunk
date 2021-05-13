<?php
/**
 * @package Mediboard\Bloc\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * BlocTest
 *
 * @description Test creation of various object in "Bloc" module
 * @screen      BlocPage
 */
class BlocTest extends SeleniumTestMediboard {

  public $nom_bloc  = "NomBloc";
  public $nom_salle = "NomSalle";

  // Bloc disabled and rooms disabled
  public $bloc2 = "NomBloc 2";
  public $bloc4 = "NomBloc 4";

  /**
   * Cr�ation d'un bloc appel� NomBloc
   */
  public function testCreateBlocOk() {
    $page = new BlocPage($this);
    $page->createBloc($this->nom_bloc);
    $page->getSystemMessageElement();
    $this->assertEquals("Bloc op�ratoire cr��", $page->getSystemMessage());
  }

  /**
   * Cr�ation d'une salle
   */
  public function testCreateSalleOk() {
    $page = new BlocPage($this);
    $this->importObject('dPbloc/tests/Functional/data/bloc.xml');

    //Click sur le volet Salle de bloc
    $page->accessControlTab("salles");
    $page->createSalle($this->nom_bloc, $this->nom_salle);
    $page->getSystemMessageElement();
    $this->assertEquals("Salle cr��e", $page->getSystemMessage());
  }

  /**
   * Cr�ation d'une plage op�ratoire
   */
  public function testCreateVacation() {
    $page = new BlocPage($this);
    $this->importObject('dPbloc/tests/Functional/data/bloc_salle.xml');

    $page->createVacation($this->nom_bloc, $this->nom_salle);
    $this->assertContains("CHIR Test", $page->getPlageCell());
  }

  /**
   * Cr�ation de plusieurs blocs et de plusieurs salles en d�sactivant certains bloc et salles avec
   * v�rification de l'affichage des blocs et salles actif
   */
  public function testCreateSomeBlocsAndSomeRoomsWithCheckDisplayOk() {
    $page = new BlocPage($this);
    $page->createSomeBlocs($this->nom_bloc);
    $page->createSomeOperatingRooms($this->nom_bloc, $this->nom_salle);
    $this->assertEquals(3, $page->checkBlocsDisabled($this->nom_bloc));
    $this->assertTrue($page->checkRoomsDisabled($this->bloc2, $this->bloc4, $this->nom_salle));
  }
}