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
   * Création d'un catalogue d'analyse
   */
  public function testCreateAnalysisCatalogOk() {
    $page = new CatalogPage($this);

    $page->createAnalysisCatalog($this->functionName, $this->reference, $this->libelle);
    $this->assertEquals("Catalogue créé", $page->getSystemMessage());

    $this->assertContains($this->libelle, $page->getCatalogName());
    $this->assertEquals("0", $page->getAnalysisNumber());
  }

  /**
   * Création d'une analyse médicale et vérification que l'analyse soit bien reliée au catalogue créé
   */
  public function testCreateMedicalAnalysisOk() {
    $page = new CatalogPage($this);

    // création catalog
    $page->createAnalysisCatalog($this->functionName, $this->reference, $this->libelle);
    $this->assertEquals("Catalogue créé", $page->getSystemMessage());

    // création analyse médicale
    $page->switchTab("vw_edit_examens");
    $page->createMedicalAnalysis($this->libelle, $this->analyses);
    $this->assertEquals("Analyse créée", $page->getSystemMessage());
    $this->assertEquals(1, $page->getLineCount());

    $page->switchTab("vw_edit_catalogues");
    $this->assertEquals("1", $page->getAnalysisNumber());
  }
}
