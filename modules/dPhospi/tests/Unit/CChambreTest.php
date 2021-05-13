<?php

namespace Ox\Mediboard\Hospi\Tests\Unit;

use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CEmplacement;
use Ox\Mediboard\Hospi\CLit;
use Ox\Mediboard\Hospi\CService;
use Ox\Tests\UnitTestMediboard;

class CChambreTest extends UnitTestMediboard {
  public function test__construct() {
    $chambre = new CChambre();
    $this->assertInstanceOf(CChambre::class, $chambre);
  }

  public function testLoadRefService() {
    $chambre = new CChambre();
    $this->assertInstanceOf(CService::class, $chambre->loadRefService());
  }

  public function testLoadRefsLits() {
    /** @var CChambre $chambre */
    $chambre = $this->getRandomObjects(CChambre::class);

    $this->assertContainsOnlyInstancesOf(CLit::class, $chambre->loadRefsLits());
  }

  public function testLoadRefEmplacement() {
    $chambre = new CChambre();
    $this->assertInstanceOf(CEmplacement::class, $chambre->loadRefEmplacement());
  }

  public function testCheckChambre() {
    /** @var CChambre $chambre */
    $chambre = $this->getRandomObjects(CChambre::class);

    $chambre->loadRefsLits();

    foreach ($chambre->_ref_lits as $_lit) {
      $_lit->_ref_affectations = array();
    }

    $chambre->checkChambre();

    $this->assertTrue($chambre->_nb_lits_dispo <= count($chambre->_ref_lits));
  }
}