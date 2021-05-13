<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * SupervisionGraphic page representation
 */
class SupervisionGraphicPage extends HomePage {

  protected $module_name = "dPpatients";
  protected $tab_name = "vw_supervision_graph";

  /**
   * Create observation type settings
   *
   * @return void
   */
  public function createObservationTypeSettings() {
    $driver = $this->driver;

    $form = "edit-CObservationValueType";

    // type image
    $driver->byCss("div#list-CObservationValueType button.new")->click();
    $driver->getFormField($form, "label")->sendKeys("Mon image");
    $driver->getFormField($form, "code")->sendKeys("ming");
    $driver->selectOptionByValue($form . "_datatype", "FILE");
    $driver->byCss("button.submit")->click();
    $this->getSystemMessage();

    // type numeric
    $driver->byCss("div#list-CObservationValueType button.new")->click();
    $driver->getFormField($form, "label")->sendKeys("Mon poids");
    $driver->getFormField($form, "code")->sendKeys("mpoids");
    $driver->selectOptionByValue($form . "_datatype", "NM");
    $driver->byCss("button.submit")->click();
    $this->getSystemMessage();

    // type text
    $driver->byCss("div#list-CObservationValueType button.new")->click();
    $driver->getFormField($form, "label")->sendKeys("Mon text");
    $driver->getFormField($form, "code")->sendKeys("mtext");
    $driver->selectOptionByValue($form . "_datatype", "ST");
    $driver->byCss("button.submit")->click();
  }

  /**
   * Create observation unit settings
   *
   * @return void
   */
  public function createObservationUnitSettings() {
    $driver = $this->driver;

    $this->accessControlTab('list-CObservationValueUnit');

    $form = "edit-CObservationValueUnit";

    // unit kg
    $driver->byCss("div#list-CObservationValueUnit button.new")->click();
    $driver->getFormField($form, "label")->sendKeys("kg");
    $driver->getFormField($form, "code")->sendKeys("001-kg");
    $driver->getFormField($form, "desc")->sendKeys("kg");
    $driver->byCss("button.submit")->click();
    $this->getSystemMessage();

    // unit percent
    $driver->byCss("div#list-CObservationValueUnit button.new")->click();
    $driver->getFormField($form, "label")->sendKeys("%");
    $driver->getFormField($form, "code")->sendKeys("001-%");
    $driver->getFormField($form, "desc")->sendKeys("%");
    $driver->byCss("button.submit")->click();
  }

  /**
   * Create graphic
   *
   * @param string $graph_name Graph name
   *
   * @return void
   */
  public function createGraphic($graph_name) {
    $driver = $this->driver;

    $this->accessControlTab('tab-graphs');

    // Graph
    $driver->byCss("div#tab-graphs button.new")->click();
    $driver->waitForAjax("supervision-graph-editor");
    $form_graph = "edit-supervision-graph";

    $driver->getFormField($form_graph, "title")->sendKeys($graph_name);
    $driver->getFormField($form_graph, "__disabled")->click();
    $driver->byCss("td#supervision-graph-editor button.modify")->click();
    $this->getSystemMessage();

    // Axe
    $driver->byCss("td#supervision-graph-axes-list button.new")->click();
    $driver->waitForAjax("supervision-graph-axis-editor");

    $form_axes = "edit-supervision-graph-axis";
    $driver->getFormField($form_axes, "title")->sendKeys("Poids");
    $driver->selectOptionByValue($form_axes . "_display", "lines");
    $driver->getFormField($form_axes, "limit_low")->sendKeys("30");
    $driver->getFormField($form_axes, "limit_high")->sendKeys("150");
    $driver->byCss("td#supervision-graph-axis-editor button.modify")->click();
    $this->getSystemMessage();

    // Serie
    $driver->byCss("div#supervision-graph-series-list button.new")->click();
    $form_serie = "edit-supervision-graph-series";
    $driver->getFormField($form_serie, "title")->sendKeys("Poids");
    $driver->byXPath("(//div[@class='dropdown-trigger'])[1]")->click();
    $driver->selectAutocompleteByText('edit-supervision-graph-series_value_type_id_autocomplete_view', "Poids")->click();
    $driver->byXPath("(//div[@class='dropdown-trigger'])[2]")->click();
    $driver->selectAutocompleteByText('edit-supervision-graph-series_value_unit_id_autocomplete_view', "kg")->click();
    $this->selectColor("0b5394");
    $driver->byXPath("//form[@name='edit-supervision-graph-series']//button[@class='modify']")->click();
  }

  /**
   * Create timestamped data
   *
   * @return void
   */
  public function createTimestampedData() {
    $driver = $this->driver;

    $this->accessControlTab('tab-timed_data');

    $driver->byCss("div#tab-timed_data button.new")->click();
    $driver->waitForAjax("supervision-graph-editor");
    $form_data = "edit-supervision-graph-timed-data";

    $driver->getFormField($form_data, "title")->sendKeys("Commentaire");
    $driver->byXPath("//div[@class='dropdown-trigger']")->click();
    $driver->selectAutocompleteByText('edit-supervision-graph-timed-data_value_type_id_autocomplete_view', "Mon text")->click();
    $driver->getFormField($form_data, "__disabled")->click();
    $driver->selectOptionByValue($form_data . "_type", "str");

    $driver->byCss("td#supervision-graph-editor button.modify")->click();
  }

  /**
   * Create image
   *
   * @return void
   */
  public function createImage() {
    $driver = $this->driver;

    $this->accessControlTab('tab-timed_pictures');

    $driver->byCss("div#tab-timed_pictures button.new")->click();
    $driver->waitForAjax("supervision-graph-editor");
    $form_image = "edit-supervision-graph-timed-picture";

    $driver->getFormField($form_image, "title")->sendKeys("Orientation");
    $driver->byXPath("//div[@class='dropdown-trigger']")->click();
    $driver->selectAutocompleteByText('edit-supervision-graph-timed-picture_value_type_id_autocomplete_view', "Mon image")->click();
    $driver->getFormField($form_image, "__disabled")->click();

    $driver->byCss("td#supervision-graph-editor button.modify")->click();
    $this->getSystemMessage();

    // select images
    $driver->byXPath("//button[contains(@onclick, 'chosePredefinedPicture')]")->click();
    $driver->byXPath("(//form[contains(@name, 'select-picture')]//a)[1]")->click();
    $this->getSystemMessage();

    $driver->byXPath("//button[contains(@onclick, 'chosePredefinedPicture')]")->click();
    $driver->byXPath("(//form[contains(@name, 'select-picture')]//a)[2]")->click();
  }

  /**
   * Create pack with a graphic and some datas
   *
   * @param string $graph_name     Graphic name
   * @param string $graph_lastname Graphic name with package
   *
   * @return void
   */
  public function createPackWithGraphicAndDatas($graph_name, $graph_lastname) {
    $driver = $this->driver;

    $this->accessControlTab('tab-packs');

    $driver->byCss("div#tab-packs button.new")->click();
    $driver->waitForAjax("supervision-graph-editor");
    $form_pack = "edit-supervision-graph-pack";

    $driver->getFormField($form_pack, "title")->sendKeys($graph_lastname);
    $driver->getFormField($form_pack, "__disabled")->click();

    $driver->byCss("td#supervision-graph-editor button.modify")->click();
    $this->getSystemMessage();

    $form_graph = "edit-supervision-graph-to-pack";

    // Add graphic
    $driver->byCss("div#graph-to-pack-list button:nth-child(1)")->click();
    $driver->selectOptionByText($form_graph . "_graph_id", $graph_name);
    $driver->byXPath("//form[@name='$form_graph']//button[@class='modify']")->click();
    $this->getSystemMessage();

    // Add textual data
    $driver->byCss("div#graph-to-pack-list button:nth-child(2)")->click();
    $driver->selectOptionByText($form_graph . "_graph_id", "Commentaire");
    $driver->byXPath("//form[@name='$form_graph']//button[@class='modify']")->click();
    $this->getSystemMessage();

    // Add image
    $driver->byCss("div#graph-to-pack-list button:nth-child(3)")->click();
    $driver->selectOptionByText($form_graph . "_graph_id", "Orientation");
    $driver->byXPath("//form[@name='$form_graph']//button[@class='modify']")->click();
  }

  /**
   * Check graphic created and datas in Perop
   *
   * @param string $patientLastname Patient last name
   * @param string $graph_name      Graphic name
   *
   * @return int
   */
  public function checkGraphicAndDatasInPerop($patientLastname, $graph_name) {
    $driver = $this->driver;

    $dp_page = new DossierPatientPage($driver, false);
    $dp_page->searchPatientByName($patientLastname);

    $driver->byCss("div#search_result_patient tr.patientFile div.noted a")->click();
    $driver->waitForAjax("vwPatient");

    $elt     = $driver->byXPath("//span[contains(text(), 'Intervention')]");
    $actions = $driver->action();
    $actions->moveToElement($elt);
    $actions->perform();
    $driver->byXPath("//button[contains(@onclick, 'dossierBloc')]")->click();

    $driver->changeFrameFocus();
    $driver->byXPath("//li[contains(@onmouseup, 'reloadSurveillance')]//a")->click();
    $driver->waitForAjax("surveillance_perop");

    // select Graphic
    $form_graph_perop = "change-operation-graph-pack-perop";
    $driver->selectOptionByText($form_graph_perop . "_graph_pack_id", $graph_name);
    $this->getSystemMessage();

    // check datas
    $elt    = $driver->findElements(WebDriverBy::xpath("//div[@class='vis-inner' and contains(text(), 'Orientation')]"));
    $result = count($elt);
    $elt    = $driver->findElements(WebDriverBy::xpath("//div[@class='vis-inner' and contains(text(), 'Commentaire')]"));
    $result += count($elt);
    $elt    = $driver->findElements(WebDriverBy::xpath("//div[@class='axis' and contains(text(), 'Poids')]"));
    $result += count($elt);

    return $result;
  }
}
