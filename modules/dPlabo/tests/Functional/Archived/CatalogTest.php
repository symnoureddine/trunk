<?php
/**
 * @package Mediboard\Labo\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CatalogTest
 *
 * @description Test create a analysis catalog
 * @screen      CatalogPage
 */
class CatalogTest extends SeleniumTestMediboard {

  // catalogue
  public $functionName = "Medecin";
  public $reference    = "C001";
  public $libelle      = "Analyse sanguin";

  // analyses
  public $analyses = array(
    "reference"    => "A001",
    "libelle"      => "Prise de sang",
    "type"         => "float",
    "unite"        => "ml",
    "realisateur"  => "TAMM",
    "age_min"      => 15,
    "age_max"      => 27,
    "materiel"     => "seringue",
    "conservation" => "chambre froide",
    "quantite_pre" => 24.56,
    "unite_pre"    => "ml"
  );

  /**
   * Cr�ation d'un catalogue d'analyse
   */
  public function testCreateAnalysisCatalogOk() {
    $page = new CatalogPage($this);

    $page->createAnalysisCatalog($this->functionName, $this->reference, $this->libelle);
    $this->assertEquals("Catalogue cr��", $page->getSystemMessage());

    $this->assertContains($this->libelle, $page->getCatalogName());
    $this->assertEquals("0", $page->getAnalysisNumber());
  }

  /**
   * Cr�ation d'une analyse m�dicale et v�rification que l'analyse soit bien reli�e au catalogue cr��
   */
  public function testCreateMedicalAnalysisOk() {
    $page = new CatalogPage($this);

    // cr�ation catalog
    $page->createAnalysisCatalog($this->functionName, $this->reference, $this->libelle);
    $this->assertEquals("Catalogue cr��", $page->getSystemMessage());

    // cr�ation analyse m�dicale
    $page->switchTab("vw_edit_examens");
    $page->createMedicalAnalysis($this->libelle, $this->analyses);
    $this->assertEquals("Analyse cr��e", $page->getSystemMessage());
    $this->assertEquals(1, $page->getLineCount());

    $page->switchTab("vw_edit_catalogues");
    $this->assertEquals("1", $page->getAnalysisNumber());
  }
}
