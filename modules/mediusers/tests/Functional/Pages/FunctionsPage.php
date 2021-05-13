<?php
/**
 * @package Mediboard\Mediusers\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers\Tests\Functional\Pages;
use Ox\Tests\HomePage;


/**
 * Test de paramétrage de fonction
 */
class FunctionsPage extends HomePage {
  protected $module_name =  "mediusers";
  protected $tab_name = "vw_idx_functions";

  /**
   * Test de création de fonction
   *
   * @param string $name_fct  Nom de la fonction
   * @param string $type_fct  Type de la fonction
   * @param string $color_fct Couleur de la fonction
   *
   * @return void
   */
  public function testaddFunction($name_fct, $type_fct, $color_fct) {
    $driver = $this->driver;

    $driver->byCss("a.button.new")->click();
    $driver->changeFrameFocus();

    $name_form = "editFrm_";
    //Ajout de l'intitulé
    $driver->byId($name_form . "text")->value($name_fct);
    //Choix de l'établissement
    $driver->byXPath("//select[@id='".$name_form."group_id']/option[2]")->click();
    //Choix du type
    $driver->selectOptionByValue($name_form."type", $type_fct);
    //Choix de la couleur
    $this->selectColor($color_fct);

    //Création de la fonction
    $driver->byCss("button.submit")->click();
  }

  /**
   * Test d'ajout d'un utilisateur à une fonction secondaire
   *
   * @param string $name_fct  Nom de la fonction
   * @param string $name_user Nom de la personne à ajouter
   *
   * @return void
   */
  public function testAddUserSecondaryFunction($name_fct, $name_user) {
    $driver = $this->driver;
    //Ouverture de la fonction recherchée
    $driver->byXPath("//span[contains(text(),'$name_fct')]/../../td[@class='compact']/button")->click();
    $driver->changeFrameFocus();

    //Accès à la tabulation utilisateurs secondaires
    $this->accessControlTab('CFunctions-back-users');
    $this->accessControlTab('list-secondary-users');

    //Recherche de l'utilisateur
    $this->driver->byId('addSecUser__user_view')->value($name_user);
    $driver->byXPath("//input[@id='addSecUser_user_id']/..//span[contains(text(),'$name_user')]")->click();

    //Enregistrement
    $driver->byCss("button.submit")->click();
  }
}