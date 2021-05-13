<?php
/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * RPU page representation
 */
class RPUPage extends HomePage {
  protected $module_name = "dPurgences";
  protected $tab_name = "vw_idx_rpu";

  /**
   * Création d'un rpu
   *
   * @param string $patientLastname Patient lastname
   * @param string $mode_entree     Mode d'entrée
   * @param string $provenance      Provenance
   * @param string $transport       Transport
   *
   * @return void
   */
  public function createRPU($patientLastname, $mode_entree, $provenance, $transport) {
    $driver = $this->driver;
    $name_form = "editRPU";

    //Click sur le bouton d'admission du patient
    $driver->byCss('#holder_main_courante .button.new')->click();
    $driver->changeFrameFocus();

    //Choix du référent
    $driver->byCss("#".$name_form."__responsable_id > option:nth-child(2)")->click();

    //Choix de l'ide référent
    $driver->byId($name_form."_ide_responsable_id_view")->click();
    $driver->byCss("div.autocomplete li:first-child")->click();

    //Selection du patient
    $driver->byId($name_form."__patient_view")->click();
    $this->patientModalSelector($patientLastname);
    // Reset the focus to the current window
    $driver->switchTo()->defaultContent();
    $driver->changeFrameFocus();

    //Choix du mode d'entrée
    $driver->selectOptionByText($name_form."__mode_entree", $mode_entree);
    //Choix de la provenance
    $driver->selectOptionByText($name_form."__provenance", $provenance);
    //Choix du mode de transport
    $driver->selectOptionByText($name_form."__transport", $transport);

    //Click sur le boutton créer
    $driver->byCss("button.submit")->click();
  }

  /**
   * Effectue la prise en charge d'un RPU
   *
   * @return void
   */
  public function pecRPU() {
    $driver = $this->driver;

    $elements = $driver->findElements(WebDriverBy::id('editRPU_rpu_id'));

    $element = reset($elements);
    $rpu_id = $element->getAttribute('value');

    //Choix du praticien
    $driver->byCss("div#rpu form[name='createConsult-$rpu_id'] select option:nth-child(2)")->click();

    //Création de la prise en charge
    $driver->byCss("div#rpu form[name='createConsult-$rpu_id'] button.new")->click();
  }

  /**
   * Vérifie la possibilité de reconvocation
   *
   * @return string
   */
  public function testPossibleReconvocation() {
    $driver = $this->driver;

    $driver->byXPath("//button[@class='new singleclick'][contains(text(),'Reconvoquer')]")->click();

    // Modale de la consultation
    $driver->changeFrameFocus();

    // Modale du choix de plage
    $driver->changeFrameFocus();

    return $driver->byXPath("//div[@id='listePlages']")->getText();
  }

  /**
   * Vérifie l'application du protocole de RPU
   */
  public function testApplyProtocoleRPU() {
    $driver = $this->driver;

    $form = "editRPU";

    $driver->byCss("#holder_main_courante .button.new")->click();
    $driver->changeFrameFocus();

    $mode_entree    = $driver->byCss("#$form" . "__mode_entree option[selected]")->getAttribute("value");
    $transport      = $driver->byCss("#$form" . "__transport option[selected]")->getAttribute("value");
    $responsable_id = trim($driver->byCss("#$form" . "__responsable_id option[selected]")->getText());

    return array(
      "mode_entree"    => $mode_entree,
      "transport"      => $transport,
      "responsable_id" => $responsable_id
    );
  }

  /**
   * Vérifie la possibilité de choisir un protocole de RPU
   */
  public function testChooseProtocoleRPU($libelle = "Protocole1") {
    $driver = $this->driver;

    $form = "editRPU";

    $driver->byCss("#holder_main_courante .button.new")->click();
    $driver->changeFrameFocus();

    $driver->byCss("button.search")->click();

    $driver->selectOptionByText($form . "_protocole_id", $libelle);

    return trim($driver->byCss("#$form" . "_protocole_id option[selected]")->getText());

    /*
    $driver->byCss("#protocoles_rpu button.tick")->click();

    $mode_entree    = $driver->byId("$form" . "__mode_entree")-;
    $transport      = $driver->byCss("#$form" . "__transport option[selected]")->getAttribute("value");
    $responsable_id = trim($driver->byCss("#$form" . "__responsable_id option[selected]")->getText());

    return array(
      "mode_entree"    => $mode_entree,
      "transport"      => $transport,
      "responsable_id" => $responsable_id
    );*/
  }
}
