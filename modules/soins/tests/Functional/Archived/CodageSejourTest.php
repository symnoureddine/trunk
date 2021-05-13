<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * SuiviSoins Test
 *
 * @description Teste le codage des actes sur un séjour
 * @screen      SejourPage
 */
class CodageSejourTest extends SeleniumTestMediboard {

  /**
   * Teste le codage d'actes incompatibles sur un séjour
   *
   * @pref   ccam_sejour 1
   * @config dPccam codage use_cotation_ccam 1
   * @config dPsalleOp CActeCCAM check_incompatibility blockOperationAlertOthers
   */
  public function testBlocageIntervIncompatibiliteCCAM() {
    $page = new SejourPage($this);
    $this->importObject("soins/tests/Functional/data/codage_sejour.xml");

    $page->openCotationSejour();
    $page->codageActeCCAM('BELB001');
    $page->dismissAlert();

    $this->assertContains('Acte CCAM créé', $page->getSystemMessage());

    $page->codageActeCCAM('BFGA004');

    $message = $page->getSystemMessage();
    $this->assertContains('Acte CCAM créé', $message);
    $this->assertContains('Acte incompatible avec le codage de BELB001', $message);
    $this->assertTrue($page->isWarningMessage());
  }

  /**
   * Teste le codage d'un praticien remplacé dans le dossier de soins
   *
   * @pref   ccam_sejour 1
   * @config dPccam codage use_cotation_ccam 1
   * @config dPsalleOp CActeCCAM check_incompatibility blockOperationAlertOthers
   */
  public function testCodageCCAMRemplacant() {
    $page = new SejourPage($this);
    $this->importObject("dPsalleOp/tests/Functional/data/codage_remplacant.xml");
    $page->openDossierSoins('Actes');

    $page->openCodageFor('CHIR Codage', CMbDT::date('-2 days'));
    $this->assertNotContains('remplaçant', $page->getCodageCCAMHeader());
    $this->assertContains('CHIR Codage', $page->getCodageCCAMHeader());
    $page->codageActeCCAM('YYYY015');

    $this->assertContains('Acte CCAM créé', $page->getSystemMessage());
    $page->closeCodageModal();

    $page->openCodageFor('CHIR Codage', CMbDT::date());
    $this->assertContains('remplaçant', $page->getCodageCCAMHeader());
    $this->assertContains('CHIR Codage', $page->getCodageCCAMHeader());
    $this->assertContains('CHIR Remplacant', $page->getCodageCCAMHeader());
    $page->codageActeCCAM('YYYY015');

    $this->assertContains('Acte CCAM créé', $page->getSystemMessage());
  }
}