<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Tests\Functional\Pages;
use Ox\Tests\HomePage;

/**
 * Facturation page representation
 */
class FacturationPage extends HomePage {
  protected $module_name = "dPcabinet";
  protected $tab_name = "edit_consultation";

  /*
   * Test de cloture de la consultation
   *
   * @return void
   */
  function testClotureCotation($close_modal = false) {
    $driver = $this->driver;

    //Choix du volet Facturation
    if ($close_modal) {
      $this->closeModal();
      sleep(1);
    }
    $driver->waitForAjax('facturation');

    // Cloture de la consultation
    $driver->byId("reglements_button_cloturer_cotation")->click();
    $driver->waitForAjax('facturation');
  }

  /*
   * Test de changement du type de la facture
   *
   * @return void
   */
  function testChangeTypeFacture($type_facture = "accident") {
    $driver = $this->driver;

    // Cloture de la facture
    $driver->byId("type_facture_type_facture")->value($type_facture);
    $driver->waitForAjax('facturation');
  }

  /*
   * Test de cloture de la facture
   *
   * @return void
   */
  function testClotureFacture() {
    $driver = $this->driver;

    // Cloture de la facture
    $driver->byXPath("//*[@id='load_facture']//button[@class='submit']")->click();
    $driver->waitForAjax('facturation');
  }

  /*
   * Test de modification de répartition du montant
   *
   * @return void
   */
  function testChangeRepartitionMontants($montant, $type) {
    $driver = $this->driver;

    //OUvertur de la modale
    $driver->byXPath("//button[contains(@onclick, 'editRepartition')]")->click();

    //Choix du montant à modifier
    $this->driver->byId('Edit-repartitionFacture_du_'.$type)->value($montant);

    // Enregistrement
    $driver->byXPath("//button[contains(@onclick, 'modifRepartition')]")->click();
  }

  /*
   * Test d'ajout de règlement Total
   *
   * @return void
   */
  function testaddReglementTotal($volet_rgt = false) {
    $driver = $this->driver;
    //Nous nous plaçons dans l'onglet règlement si les configurations le necessitent
    if ($volet_rgt) {
      $driver->byXPath("//a[contains(@href, 'reglements_facture-CFacture')]")->click();
    }
    // Ajout du règlement total
    $driver->byId("reglement_button_add")->click();
  }

  /*
   * Récupération du montant que le patient doit
   *
   * @return string
   */
  function testMontantAreglerPatient() {
    return $this->driver->byXPath("//input[contains(@name, 'montant')]")->attribute('value');
  }

  /*
   * Test d'ajout de règlement partiel
   *
   * @return void
   */
  function testaddReglementPartiel($volet_rgt = false, $montant) {
    $driver = $this->driver;
    //Nous nous plaçons dans l'onglet règlement si les configurations le necessitent
    if ($volet_rgt) {
      $driver->byXPath("//a[contains(@href, 'reglements_facture-CFacture')]")->click();
    }
    // Ajout du règlement partiel
    $input_montant = $driver->byXPath("//input[contains(@name, 'montant')]");
    $input_montant->clear();
    $input_montant->value($montant);
    $driver->byId("reglement_button_add")->click();
  }
}