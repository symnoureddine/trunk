<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateInfrastructureTest
 *
 * @description Test de création de l'infrastructure
 * @screen      InfrastructurePage
 */
class CreateInfrastructureTest extends SeleniumTestMediboard {

  public $name_secteur = "secteur1";
  public $name_service = "service1";
  public $name_chambre = "chambre1";
  public $name_lit     = "lit1";
  public $name_uf      = "uf_soins1";
  public $type_uf      = "soins";
  public $code_uf      = "32";

  /** @var InfrastructurePage $page */
  public $page;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new InfrastructurePage($this);
//  }

  /**
   * Essai de création de secteur
   */
  public function testCreateSecteur() {
    $msg = $this->page->createSecteur($this->name_secteur);
    $this->assertEquals("Secteur créé", $msg);
  }

  /**
   * Essai de création de service
   */
  public function testCreateService() {
    $msg = $this->page->createService($this->name_service);
    $this->assertEquals($this->name_service, $msg);
  }

  /**
   * Essai de création de chambre
   */
  public function testCreateChambre() {
    $this->page->createService($this->name_service);
    $msg = $this->page->createChambre($this->name_service, $this->name_chambre);
    $this->assertEquals("Chambre créée", $msg);
  }

  /**
   * Essai de création de lit
   */
  public function testCreateLit() {
    $this->page->createService($this->name_service);
    $this->page->createChambre($this->name_service, $this->name_chambre);
    $msg = $this->page->createLit($this->name_lit);
    $this->assertEquals("Lit créé", $msg);
  }

  /**
   * Essai d'affectation d'un service à un secteur
   */
  public function testAddServiceSecteur() {
    $this->page->createSecteur($this->name_secteur);
    $this->page->createService($this->name_service);
    $msg = $this->page->addServiceToSecteur($this->name_secteur, $this->name_service);
    $this->assertEquals($this->name_service, $msg);
  }

  /**
   * Essai de création d'UF
   */
  public function testCreateUF() {
    $msg = $this->page->createUF($this->name_uf, $this->type_uf, $this->code_uf);
    $this->assertEquals("Unité fonctionnelle créée", $msg);
  }

}
