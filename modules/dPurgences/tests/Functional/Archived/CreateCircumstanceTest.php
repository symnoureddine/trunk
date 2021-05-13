<?php
/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateCircumstanceTest
 *
 * @description Test creation Circumstance
 * @screen      CircumstancePage
 */
class CreateCircumstanceTest extends SeleniumTestMediboard {
  public $code = "Feu";
  public $libelle  = "ca brule";
  public $commentaire   = "Toute source de chaleur.";

  /** @var CircumstancePage $page */
  public $circonstancePage;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->circonstancePage = new CircumstancePage($this);
//  }

  /**
   * Création d'une Circonstance
   */
  public function testCreateCircumstanceOk() {
    $page = $this->circonstancePage;

    $page->createCircumstance($this->code, $this->libelle, $this->commentaire);

    $this->assertContains("Circonstance créée", $page->getSystemMessage());

    $page->putCircumstanceActiveOrInactive();
    $this->assertContains("Circonstance modifiée", $page->getSystemMessage());
  }
}
