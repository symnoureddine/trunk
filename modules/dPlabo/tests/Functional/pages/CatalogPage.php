<?php
/**
 * @package Mediboard\Labo\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * CatalogPage page representation
 *
 * @package    Tests
 * @subpackage Pages
 * @author     SARL OpenXtrem <dev@openxtrem.com>
 * @license    GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @link       http://www.mediboard.org
 */
class CatalogPage extends HomePage {

  protected $module_name = "labo";
  protected $tab_name = "vw_edit_catalogues";

  /**
   * Créer un catalogue d'analyse
   *
   * @param string $functionName Function name
   * @param string $reference    ID catalog
   * @param string $libelle      Catalog title
   *
   * @return void
   */
  public function createAnalysisCatalog($functionName, $reference, $libelle) {
    $driver = $this->driver;

    // create new a catalog
    //$driver->byCss("td.halfPane a.new")->click();

    $driver->selectOptionByText("editCatalogue_function_id", $functionName);
    $driver->valueRetryByID("editCatalogue_identifiant", $reference);
    $driver->byId("editCatalogue_libelle")->sendKeys($libelle);

    // save
    $driver->byCss("button.submit")->click();
  }

  /**
   * Renvoie le nom trouvé dans la premiere ligne du tableau pour vérifier son existance
   *
   * @return string Catalog name
   */
  public function getCatalogName() {
    return $this->driver->byCss("div.tree-header a:last-child")->getText();
  }

  /**
   * Renvoie le nombre d'analyse lié au catalogue créé
   *
   * @return string Analysis number
   */
  public function getAnalysisNumber() {
    $value = $this->driver->byCss("div.tree-header a:first-child")->getText();
    $value = trim($value);
    $result = explode(" ", $value);

    return $result[0];
  }

  /**
   * Créer d'une analyse médicale
   *
   * @param string $catalogName Function name
   * @param array  $analyses    Analysis array
   *
   * @return void
   */
  public function createMedicalAnalysis($catalogName, $analyses) {
    $driver = $this->driver;
    $formName = "editExamen_";

    // volet informations générales
    $driver->selectOptionByText($formName."catalogue_labo_id", $catalogName);
    $driver->byId($formName."identifiant")->sendKeys($analyses["reference"]);
    $driver->byId($formName."libelle")->sendKeys($analyses["libelle"]);
    $driver->selectOptionByValue($formName."type", $analyses["type"]);
    $driver->byId($formName."unite")->sendKeys($analyses["unite"]);
    $driver->selectOptionByText($formName."realisateur", $analyses["realisateur"]);

    // volet réalisation
    $this->accessControlTab("realisation");
    $driver->byId($formName."age_min")->sendKeys($analyses["age_min"]);
    $driver->byId($formName."age_max")->sendKeys($analyses["age_max"]);
    $driver->byId($formName."materiel")->sendKeys($analyses["materiel"]);

    // volet conservation
    $this->accessControlTab("conservation");
    $driver->byId($formName."conservation")->sendKeys($analyses["conservation"]);
    $driver->byId($formName."quantite_prelevement")->sendKeys($analyses["quantite_pre"]);
    $driver->byId($formName."unite_prelevement")->sendKeys($analyses["unite_pre"]);

    // save
    $driver->byCss("button.submit")->click();
  }

  /**
   * Récupération du nombre de ligne par rapport à la création d'analyse médicale
   *
   * @return int
   */
  public function getLineCount() {
    $driver = $this->driver;

    return count($driver->findElements(WebDriverBy::cssSelector("div.content tr.selected")));
  }
}
