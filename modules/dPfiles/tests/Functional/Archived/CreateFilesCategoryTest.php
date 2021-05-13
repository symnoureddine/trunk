<?php
/**
 * @package Mediboard\Files\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateFilesCategoryTest
 *
 * @description Test creation of a files category
 * @screen      FilesCategoryPage
 */
class CreateFilesCategoryTest extends SeleniumTestMediboard {

  /**
   * Création d'une catégorie de fichier appelée NomCategorie pour le type d'objet Séjour
   */
  public function testCreateFilesCategoryOk() {
    $page = new FilesCategoryPage($this);
    $page->createFilesCategory();

    $this->assertEquals("Catégorie créée", $page->getSystemMessage());
  }
}