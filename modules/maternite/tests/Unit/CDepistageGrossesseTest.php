<?php

namespace Ox\Mediboard\Maternite;

use Ox\Mediboard\Addictologie\CDossierAddictologie;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;
use PHPUnit\Framework\TestCase;

class CDepistageGrossesseTest extends UnitTestMediboard {

  /**
   * Create depistage grossesse
   *
   * @return CDepistageGrossesse
   * @throws TestsException
   */
  public function testCreateDepistage() {
    $grossesse = $this->getRandomObjects("CGrossesse", 1);
    $depistage = new CDepistageGrossesse();
    $depistage->grossesse_id = $grossesse->_id;
    $depistage->date = "now";
    $depistage->_libelle_customs = array(0 => "Test", 1 => "Test2");
    $depistage->_valeur_customs = array(0 => 15, 1 => 20);
    $depistage->store();

    $this->assertNotNull($depistage->_id);
    return $depistage;
  }

  /**
   * Test du calcul de la date en semaines d'aménorrhée
   *
   * @param CDepistageGrossesse $depistage Dépistage
   *
   * @depends testCreateDepistage
   * @throws TestsException
   */
  public function testCalculSemaineAmenorrheeSA(CDepistageGrossesse $depistage) {
    $sa = $depistage->getSA();
    $this->assertIsNumeric($sa);
  }

  /**
   * Test de la construction du tableau des dépistages additionnels
   *
   * @param CDepistageGrossesse $depistage Dépistage
   *
   * @depends testCreateDepistage
   * @throws TestsException
   */
  public function testConstructionAdditionnelDepistage(CDepistageGrossesse $depistage) {
    $depistage->updateFormFields();
    $this->assertCount(3, $depistage->_depistage_custom_ids);
  }
}
