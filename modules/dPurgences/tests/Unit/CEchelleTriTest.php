<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Urgences\Tests\Unit;

use Ox\Mediboard\Urgences\CEchelleTri;
use Ox\Tests\UnitTestMediboard;

class CEchelleTriTest extends UnitTestMediboard {

  public function test__construct() {
    $echelle = new CEchelleTri();
    $this->assertInstanceOf(CEchelleTri::class, $echelle);
  }

  public function testCalculGlasgow() {
    $echelle_tri = new CEchelleTri();

    $glasgow = $echelle_tri->calculGlasgow();
    $this->assertEquals(0, $glasgow);

    $echelle_tri->ouverture_yeux = "spontane";
    $glasgow = $echelle_tri->calculGlasgow();
    $this->assertEquals(4, $glasgow);

    $echelle_tri->rep_motrice = "decortication";
    $glasgow = $echelle_tri->calculGlasgow();
    $this->assertEquals(7, $glasgow);

    $echelle_tri->rep_verbale = "incomprehensible";
    $glasgow = $echelle_tri->calculGlasgow();
    $this->assertEquals(9, $glasgow);
  }
}
