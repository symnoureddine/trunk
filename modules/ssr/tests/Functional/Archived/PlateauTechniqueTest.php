<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * PlateauTechniqueTest
 *
 * @description Test param�trage plateau technique SSR
 * @screen      PlateauTechniquePage
 */
class PlateauTechniqueTest extends SeleniumTestMediboard {

  public $namePlateau = "Plateau test";
  public $nameEquipement = "Equipement A";

  /**
   * Cr�ation d'un plateau technique
   */
  public function testCreatePlateauTechnique() {
    $page = new PlateauTechniquePage($this);
    $page->createPlateauTechnique($this->namePlateau);
    $this->assertContains("Plateau technique cr��", $page->getSystemMessage());
  }

  /**
   * Test d'ajout de technicien pour un plateau technique
   */
  public function testAddTechnicienPlateau() {
    $page = new PlateauTechniquePage($this);
    $page->createPlateauTechnique($this->namePlateau);
    $page->addTechnicienPlateau($this->namePlateau);
    $this->assertContains("Technicien cr��", $page->getSystemMessage());
  }

  /**
   * Test d'ajout d'�quipement pour un plateau technique
   */
  public function testAddEquipementPlateau() {
    $page = new PlateauTechniquePage($this);
    $page->createPlateauTechnique($this->namePlateau);
    $page->addEquipementPlateau($this->namePlateau, $this->nameEquipement);
    $this->assertContains("Equipement cr��", $page->getSystemMessage());
  }
}
