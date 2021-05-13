<?php
/**
 * @package Mediboard\Forms\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateFormTest
 *
 * @description Test creation of a form and a tag
 * @screen      FormPage
 */
class CreateFormTest extends SeleniumTestMediboard {
  /** @var FormPage */
  public $formPage;
  public $formName  = "Test formulaire 1";
  public $tagName  = "Tag etablissement";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->formPage = new FormPage($this);
//  }

  /**
   * Cr�ation d'un formulaire et d'une �tiquette puis associer cette �tiquette au formulaire
   */
  public function testCreateFormAndTagOk() {
    $page = $this->formPage;
    $page->createForm($this->formName);
    $this->assertEquals("Formulaire cr��", $page->getSystemMessage());

    $page->createTag($this->tagName);
    $this->assertEquals("�tiquette cr��e", $page->getSystemMessage());

    $page->closeModal();
    $page->associateTagWithForm($this->tagName);
    $this->assertEquals("�tiquette associ�e", $page->getSystemMessage());
  }
}