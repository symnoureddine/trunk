<?php
/**
 * @package Mediboard\Ssr\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Functional\Pages;


use Ox\Tests\HomePage;

/**
 * Traduction de remplacement Representation
 */
class TraductionRemplacementPage extends HomePage {
  protected $module_name = "system";
  protected $tab_name = "view_translations";

  /**
   * Création d'une traduction de remplacement
   *
   * @param string $name_old Traduction existante
   * @param string $name_new Nouvelle traduction
   *
   * @return void
   */
  public function createTraduction($name_old, $name_new) {
    $driver = $this->driver;
    $driver->byCss("button.new")->click();

    $name_form = "editTranslationO";
    $driver->byId($name_form . "_source")->value($name_old);

    $driver->selectAutocompleteByText($name_form."_source", "mod-system-tab-view_translations")->click();
    $driver->byId($name_form . "_translation")->value($name_new);

    // Reset the focus to the current window
    $driver->window('');

    $driver->byCss("button.save")->click();
  }

  /**
   * Récupère le nom de l'onglet
   *
   * @return string
   */
  public function getNameTabTraduction() {
    return $this->driver->byXPath("//a[@href='?m=system&tab=view_translations']")->text();
  }
}