<?php

namespace Ox\Mediboard\Patients\Tests\Unit;

use Ox\Mediboard\Patients\CPaysInsee;
use Ox\Tests\UnitTestMediboard;

class CPayInseeTest extends UnitTestMediboard {
  static $code_num = 004;
  static $code_alpha2 = "AF";
  static $code_alpha3 = "AFG";
  static $nom_fr = "Afghanistan";

  public function test__construct() {
    $pays_insee = new CPaysInsee();
    $this->assertInstanceOf(CPaysInsee::class, $pays_insee);
  }

  public function testGetAlpha2() {
    $nom_pays = CPaysInsee::getAlpha2(self::$code_num);
    $this->assertEquals(self::$code_alpha2, $nom_pays);
  }

  public function testGetAlpha3() {
    $nom_pays = CPaysInsee::getAlpha3(self::$code_num);
    $this->assertEquals(self::$code_alpha3, $nom_pays);
  }

  public function testGetPaysByNumerique() {
    $pays = CPaysInsee::getPaysByNumerique(self::$code_num);
    $this->assertEquals(self::$code_alpha3, $pays->alpha_3);
  }

  public function testGetPaysByAlpha() {
    $pays = CPaysInsee::getPaysByAlpha(self::$code_alpha3);
    $this->assertEquals($pays->numerique, self::$code_num);
  }

  public function testGetNomFR() {
    $nom_fr = CPaysInsee::getNomFR(self::$code_num);
    $this->assertEquals(self::$nom_fr, $nom_fr);
  }
}