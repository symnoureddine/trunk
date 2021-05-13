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
 * Mediusers page representation
 */
class MediusersPage extends HomePage {
  protected $module_name = "mediusers";
  protected $tab_name = "vw_idx_mediusers";

  /**
   * Try to create a mediusers with the given params
   *
   * @param String $username Username
   * @param String $password Password
   * @param String $function Function
   * @param String $type     Type
   * @param String $profile  Profile
   * @param String $name     Last name
   *
   * @return void
   */
  public function createMediusers($username, $password = null, $function, $type, $profile, $name) {
    $driver = $this->driver;
    $driver->byCss("a.button.new")->click();
    $driver->changeFrameFocus();

    $form = "mediuser";
    $password ?: $password = "LRb4iGwg";

    $driver->valueRetryByID($form . "__user_username", $username);
    $driver->valueRetryByID($form . "__user_password", $password);
    $driver->valueRetryByID($form . "__user_password2", $password);
    $driver->byXPath("//*[@class='select_functions']//option[contains(text(),'$function')]")->click();
    $driver->byXPath("//select[@id='{$form}__user_type']/option[contains(text(),'$type')]")->click();
    $driver->byXPath("//select[@id='{$form}__profile_id']/option[text() = '$profile']")->click();
    $driver->valueRetryByID($form . "__user_last_name", $name);
    $driver->byCss("button.submit")->click();
  }

  /**
   * Try to open the tooltip of the given username in order to retrieve user informations
   *
   * @param String $username Username
   *
   * @return array
   */
  public function getMediusersInfos($username) {
    $driver = $this->driver;
    $driver->triggerOnmouseover("//*[contains(text(),'$username')]");

    $type    = $driver->byXPath("//label[@for='_user_type_view']/parent::td")->text();
    $profile = $driver->byXPath("//label[@for='_profile_id']/parent::td")->text();

    return array(
      "type"    => $type,
      "profile" => $profile,
    );
  }

  /**
   * Mediusers search
   *
   * @param string $keywords Keywords
   * @param string $function Mediusers function
   * @param string $type     Mediusers type
   *
   * @return void
   */
  public function searchMediusers($keywords = null, $function = null, $type = null) {
    $driver = $this->driver;
    $form = "listFilter";
    $driver->waitForAjax("result_search_mb");
    if ($keywords) {
      $driver->getFormField($form, "filter")->value($keywords);
    }
    if ($function) {
      $driver->selectOptionByText($form . "_function_id", $function, true);
    }
    if ($type) {
      $driver->selectOptionByText($form . "__user_type", $type);
    }
    $driver->byCss("form[name=listFilter] button.search[type=submit]")->click();
  }

  /**
   * Edit the mediuser type and profile based on his name
   * Profile needs to be attached to the type
   *
   * @param string $lastname Mediuser Lastname
   * @param string $type     Mediuser type
   * @param string $profile  Mediuser new profile
   *
   * @return void
   */
  public function editMediuserByName($lastname, $type = null, $profile = null) {
    $driver = $this->driver;
    $driver->byXPath("//*[contains(text(),'$lastname')]/preceding-sibling::td[@class='compact']/button")->click();

    // Implicit wait
    $field = $driver->byId("mediuser__user_username");

    if ($type) {
      $driver->selectOptionByText("mediuser__user_type", $type);
    }
    if ($profile) {
      $driver->selectOptionByText("mediuser__profile_id", $profile, true);
    }

    if ($type || $profile) {
      $field->submit();
      $driver->waitForAjax("result_search_mb");
      $this->getSystemMessage();
    }
  }

  /**
   * Check if the profile_id select element is enabled
   * Usefull for assertion
   *
   * @return bool
   */
  public function canEditProfile() {
    return $this->driver->findElementsById("mediuser__profile_id")[0]->enabled();
  }
  /**
   * Check if the user_type administrator option is enabled
   * Usefull for assertion
   *
   * @return bool
   */
  public function canSetAdmin() {
    return $this->driver->findElementsByCss("#mediuser__user_type option[value='1']")[0]->enabled();
  }

  /**
   * Test l'ajout d'un compte de facturation par défaut
   *
   * @return int
   */
  public function editCompteFactutation() {
    $driver = $this->driver;
    $this->accessControlTab('facturation');

    //Ouverture du paramétrage des comptes
    $driver->byCss("button.fas.fa-cog.notext")->click();
    //Ajout d'un compte de facturation
    $driver->byCss("button.new.notext")->click();

    //Renseignement du nom
    $driver->byId("Edit-CMediusersCompteCh-none_name")->value("Compte 1");

    //Enregistrement
    $driver->byCss("button.submit")->click();
    sleep(3);

    //Fermeture de la modale
    $driver->byXPath("//button[@class='close notext'][1]")->click();

    //Autocomplete pour choisir le compte de facturation
    $driver->byId("mediuser_compte_ch_id_autocomplete_view")->value("Compte 1");
    $driver->byCss("div.autocomplete li:first-child")->click();

    //Enregistrement de l'utilisateur
    $driver->byXPath("//*[@id='mediuser_functions_hide']/button[@class='modify']")->click();
    sleep(3);

    //Réouverture de l'utilisateur
    $driver->byXPath("//*[contains(text(),'WAYNE')]/preceding-sibling::td[@class='compact']/button")->click();
    sleep(3);

    //Récupération de la valeur du compte de facturation
    $xpath = "//input[@name='compte_ch_id_autocomplete_view'][@value='Compte 1']";
    $elts  = $driver->findElementsByXpath($xpath);
    return count($elts);
  }
}