<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * RessourcesTest
 *
 * @description Tests sur les ressources
 *
 * @screen      RessourcesPage
 */
class RessourcesTest extends SeleniumTestMediboard
{
    /** @var RessourcesPage $page
     */
    public $ressourcePage;

    public $libelle     = "Libellé";
    public $description = 'Description';
    public $color       = "#000000";
    public $actif       = 1;
    public $debut       = "8";
    public $fin         = "18";

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        //    parent::setUp();
        //    $this->ressourcePage = new RessourcesPage($this);
    }

//    public function testCreateRessource()
//    {
//        $this->markTestSkipped('FW update, need ref');
//        $page = $this->ressourcePage;
//
//        $libelle = $page->testCreateRessource(
//            [
//                "libelle"     => $this->libelle,
//                "description" => $this->description,
//                "color"       => $this->color,
//                "actif"       => $this->actif,
//            ]
//        );
//
//        $this->assertEquals("$this->libelle\n$this->description", $libelle);
//    }

    public function testCreatePlageRessource()
    {
        $this->markTestSkipped('FW update, need ref');
        $this->testCreateRessource();

        $page = $this->ressourcePage;

        $libelle_plage = $page->testCreatePlageRessource(
            [
                "debut"   => $this->debut,
                "fin"     => $this->fin,
                "libelle" => $this->libelle,
            ]
        );

        $this->assertEquals($this->libelle, $libelle_plage);
    }
}
