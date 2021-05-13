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
   * Cr�ation d'une cat�gorie de fichier appel�e NomCategorie pour le type d'objet S�jour
   */
  public function testCreateFilesCategoryOk() {
    $page = new FilesCategoryPage($this);
    $page->createFilesCategory();

    $this->assertEquals("Cat�gorie cr��e", $page->getSystemMessage());
  }
}