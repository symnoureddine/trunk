<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateSupervisionGraphicPerop
 *
 * @description Test creation of a supervision graphic
 * @screen      SupervisionGraphicPage
 */
class CreateSupervisionGraphicPeropTest extends SeleniumTestMediboard {

  /** @var $dpPage SupervisionGraphicPage */
  public $page = null;

  public $graph_name = "Mon graph";
  public $graph_lastname = "Graph_perop";
  public $patientLastname = "wayne";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->page = new SupervisionGraphicPage($this);
//    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
//  }

  /**
   * Cr�� un graphique de supervision avec les param�tres de surveillance
   *
   * @config [CConfiguration] monitoringBloc general active_graph_supervision 1
   */
  public function testCreateSupervisionGraphicWithSettingsOk() {
    $pageGraphic = $this->page;

    // Settings
    $pageGraphic->switchTab("vw_config_param_surveillance");
    $pageGraphic->createObservationTypeSettings();
    $this->assertEquals("Type d'observation cr��", $pageGraphic->getSystemMessage());
    $pageGraphic->createObservationUnitSettings();
    $this->assertEquals("Unit� cr��e", $pageGraphic->getSystemMessage());

    // Graphic
    $pageGraphic->switchTab("vw_supervision_graph");
    $pageGraphic->createGraphic($this->graph_name);
    $this->assertEquals("S�rie cr��e", $pageGraphic->getSystemMessage());

    // Timestamped data
    $pageGraphic->createTimestampedData();
    $this->assertEquals("Donn�e textuelle cr��", $pageGraphic->getSystemMessage());

    // Image
    $pageGraphic->createImage();
    $this->assertEquals("Image enregistr�e", $pageGraphic->getSystemMessage());

    // Pack
    $pageGraphic->createPackWithGraphicAndDatas($this->graph_name, $this->graph_lastname);
    $this->assertEquals("Lien cr��", $pageGraphic->getSystemMessage());

    // Check graphic created
    $pageGraphic->switchTab("vw_idx_patients");
    $this->assertEquals(3, $pageGraphic->checkGraphicAndDatasInPerop($this->patientLastname, $this->graph_lastname));
  }
}
