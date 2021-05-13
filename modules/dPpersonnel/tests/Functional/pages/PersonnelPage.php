<?php
/**
 * @package Mediboard\Personnel\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\HomePage;

/**
 * PersonnelPage page representation
 */
class PersonnelPage extends HomePage {

  protected $module_name = "dPpersonnel";
  protected $tab_name = "vw_edit_personnel";

  /**
   * Créer un personnel
   *
   * @param string $personnelName nom de l'utilisateur
   * @param string $personnelType type de personnel
   *
   * @return void
   */
  public function createPersonnel($personnelName, $personnelType) {
    $driver = $this->driver;

    $driver->byCss("button.new")->click();
    $driver->byId("editFrm-__view")->sendKeys($personnelName);
    $driver->byXPath("//div[@id='editFrm-__view_autocomplete']//em[contains(text(),'$personnelName')]")->click();
    $driver->selectOptionByValue("editFrm-_emplacement", $personnelType);
    $driver->byId("editFrm-_btnFuseAction")->click();
  }

  /**
   * Modifier un personnel en changeant le type et actif à non
   *
   * @param string $personnelName nom de l'utilisateur
   * @param string $personnelType type de personnel
   *
   * @return void
   */
  public function editPersonnel($personnelName, $personnelType) {
    $driver = $this->driver;

    $driver->byXPath("//div[@id='area_personnel']//a[contains(text(),'$personnelName')]")->click();

    $driver->changeFrameFocus();
    $driver->byCss("div.modal select[name='emplacement'] option[value='$personnelType']")->click();
    $driver->byCss("input[name='actif'][value='0']")->click();
    $driver->byCss("div form table button.modify")->click();
  }

  /**
   * Renvoie le nom trouvé dans la premiere ligne du tableau
   *
   * @return string le nom du personnel
   */
  public function getPersonnelName() {
    return $this->driver->byCss("div#area_personnel td a")->getText();
  }

  /**
   * Renvoie le type trouvé dans la premiere ligne du tableau
   *
   * @return string le type du personnel
   */
  public function getPersonnelType() {
    return $this->driver->byCss("div#area_personnel td:nth-child(2)")->getText();
  }

  /**
   * Effectue une recherche du personnel
   *
   * @param string $personnelName      nom du personnel
   * @param string $personnelType      type du personnel
   * @param string $personnelFirstName prenom du personnel
   *
   * @return void
   */
  public function searchPersonnel($personnelName, $personnelType = null, $personnelFirstName = null) {
    $driver = $this->driver;

    $element = $driver->byId("filterFrm__user_last_name");
    $element->clear();
    $element->sendKeys($personnelName);

    if ($personnelType) {
      $driver->selectOptionByValue("filterFrm_emplacement", $personnelType);
    }

    if ($personnelFirstName) {
      $element = $driver->byId("filterFrm__user_first_name");
      $element->clear();
      $element->sendKeys($personnelFirstName);
    }

    $driver->byCss("button.search")->click();
    $driver->waitForAjax("area_personnel");
  }
}