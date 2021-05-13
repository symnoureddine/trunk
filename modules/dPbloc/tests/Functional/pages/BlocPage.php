<?php
/**
 * @package Mediboard\Bloc\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * Bloc page representation
 */
class BlocPage extends HomePage {
  protected $module_name = "bloc";
  protected $tab_name = "vw_idx_blocs";

  /**
   * @param string $nom The name of the bloc to create
   */
  public function createBloc($nom) {
    $driver = $this->driver;
    
    $form = "bloc-edit";

    // Click on the create button
    $driver->byCss("a.new")->click();

    // Fill the name of the bloc
    $driver->byId($form . "_nom")->sendKeys($nom);

    // Click on the create button
    $driver->byCss("button.new")->click();
  }

  /**
   * @param string $nom_bloc  The name of the bloc
   * @param string $nom_salle The name of the room
   */
  public function createSalle($nom_bloc, $nom_salle) {
    $driver = $this->driver;

    // Click sur le boutton "Créer une salle"
    $driver->byCss("#salles > table > tbody > tr > td:nth-child(1) > a")->click();

    //Ecriture du nom de la salle
    $driver->byId("salle_nom")->sendKeys($nom_salle);

    //Sélection du bloc
    $driver->byXPath("//option[contains(text(),'$nom_bloc')]")->click();

    // Click on the create button
    $driver->byCss("form[name='salle'] button.new")->click();
  }

  /**
   * @param string $nom_bloc  The name of the bloc
   * @param string $nom_salle The name of the room
   */
  public function createVacation($nom_bloc, $nom_salle) {
    $driver = $this->driver;
    $this->switchTab('vw_edit_planning');

    // Wait for page loading
    $driver->byId("selectBloc_blocs_ids");

    // Use JS to unselect option because of multiple select bug
    // https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/1899
    $driver->executeScript("$('selectBloc_blocs_ids').selectedIndex = -1;");
    $driver->wait()->until(
      function () use ($driver) {
        return count($driver->findElements(WebDriverBy::cssSelector('#selectBloc_blocs_ids option[selected]'))) === 1;
      }
    );

    $driver->selectOptionByText('selectBloc_blocs_ids', $nom_bloc);
    $driver->byCss('button.new')->click();
    $driver->byCss('form[name="editFrm"]');
    $driver->selectOptionByText('editFrm_chir_id', 'CHIR Test');
    $driver->selectOptionByText('editFrm_salle_id', $nom_salle);
    $driver->byCss('input#editFrm_fin_da')->click();
    $driver->byXPath('//div[@class="datepickerControl"]//td[@class="hour"][contains(text(),"12")]')->click();
    $driver->byXPath('//div[@class="datepickerControl"]//button[@class="tick"]')->click();
    $driver->byCss('form[name="editFrm"] button.save')->click();
  }

  /**
   * @return string
   */
  public function getPlageCell() {
    $element = $this->driver->byCss('table#planning_bloc_day td.plageop');

    $content = '';
    if ($element) {
      $content = $element->getText();
    }

    return $content;
  }

  /**
   * Create some blocs
   *
   * @param string $nom Name
   *
   * @return void
   */
  public function createSomeBlocs($nom) {
    $driver = $this->driver;

    $this->accessControlTab("blocs");
    $form = "bloc-edit";

    for ($i = 1; $i < 5; $i++) {
      $driver->byCss("a.new")->click();
      $driver->getFormField($form, "nom")->sendKeys($nom . " " . $i);

      if ($i == 3) {
        // disabled bloc
        $driver->byId($form ."_actif_0")->click();
      }

      $driver->byCss("button.new")->click();
      $this->getSystemMessage();
    }
  }

  /**
   * Create some operating rooms
   *
   * @param string $nom_bloc Name bloc
   * @param string $nom      Name room
   *
   * @return void
   */
  public function createSomeOperatingRooms($nom_bloc, $nom) {
    $driver = $this->driver;

    $this->accessControlTab("salles");
    $form = "salle";

    for ($i = 1; $i < 7; $i++) {
      $driver->byCss("div#salles a.button.new")->click();

      if ($i > 4) {
        $driver->selectOptionByText($form . "_bloc_id", $nom_bloc . " 4");
      }
      else {
        $driver->selectOptionByText($form . "_bloc_id", $nom_bloc . " ". $i);
      }

      $driver->getFormField($form, "nom")->sendKeys($nom . " " . $i);

      if ($i == 2 || $i == 6) {
        // disabled bloc
        $driver->byId($form ."_actif_0")->click();
      }

      $driver->byCss("div#salles button.new")->click();
      $this->getSystemMessage();
    }
  }

  /**
   * Check if blocs are disabled
   *
   * @param string $bloc Bloc name
   *
   * @return int
   */
  public function checkBlocsDisabled($bloc) {
    $driver = $this->driver;

    $this->switchTab("vw_suivi_salles");
    $xpath = "//select[@id='changeDate_blocs_ids']//option[contains(text(), '$bloc')]";
    $elements = count($driver->findElements(WebDriverBy::xpath($xpath)));

    return $elements;
  }

  /**
   * Check if rooms are disabled
   *
   * @param string $bloc2 Bloc 2
   * @param string $bloc4 Bloc 4
   * @param string $room  Room name
   *
   * @return bool
   */
  public function checkRoomsDisabled($bloc2, $bloc4, $room) {
    $driver = $this->driver;

    $this->switchTab("vw_suivi_salles");

    $driver->byXPath("//select[@id='changeDate_blocs_ids']//option[contains(text(), '$bloc2')]")->click();
    $driver->byXPath("//select[@id='changeDate_blocs_ids']//option[contains(text(), '$bloc4')]")->click();
    $xpath_room = "//table[@id='suivi-salles']//td//label[contains(text()[normalize-space()], '$room')]";
    $elements = count($driver->findElements(WebDriverBy::xpath($xpath_room)));

    return $elements < 3;
  }
}