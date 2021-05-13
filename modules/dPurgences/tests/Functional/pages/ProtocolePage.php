<?php
/**
 * Protocole page representation
 */
class ProtocolePage extends HomePage {
  protected $module_name = "urgences";
  protected $tab_name = "vw_protocoles";

  /**
   * Create a protocole
   *
   * @param array $parameters Protocole parameters
   *
   * @return void
   */
  public function testCreateProtocole($params) {
    $driver = $this->driver;

    $form = "editProtocoleRPU";

    $driver->byCss("button.new")->click();

    $libelle = CMbArray::get($params, "libelle", "Protocole");
    $actif   = CMbArray::get($params, "actif", 1);
    $default = CMbArray::get($params, "default", 1);

    $driver->byId($form . "_libelle")->sendKeys($libelle);

    if ($actif) {
      $driver->byId($form . "___actif")->click();
    }

    if ($default) {
      $driver->byId($form . "___default")->click();
    }

    // Pec adm
    $transport   = CMbArray::get($params, "transport", "perso");
    $mode_entree = CMbArray::get($params, "transport", "8");

    $driver->selectOptionByValue($form . "_transport", $transport);
    $driver->selectOptionByValue($form . "_mode_entree", $mode_entree);

    // Pec med
    $chir = CMbArray::get("chir", $params, "CHIR Test");

    $driver->selectAutocompleteByText($form . "_responsable_id", $chir);

    // Geolocalisation
    $uf_soins_id = CMbArray::get("uf_soins_id", $params, "");
    $box_id      = CMbArray::get("box_id", $params, "");

    if ($uf_soins_id) {
      $driver->selectOptionByValue($form . "_uf_soins_id", $uf_soins_id);
    }

    if ($box_id) {
      $driver->selectOptionByValue($form . "_box_id", $box_id);
    }

    $driver->byCss("button.save")->click();
  }
}