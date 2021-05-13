<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Unit;

use Ox\Core\CMbDT;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\Generators\CConsultationGenerator;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use \Ox\Tests\UnitTestMediboard;

/**
 * Class CActeNGAPTest
 * @package Unit
 */
class CActeNGAPTest extends UnitTestMediboard {
  /**
   * Vérifie que le complément de Nuit est bien ajouté automatiquement
   */
  public function testComplementNuitAuto() {
    $act = self::createActe('CS', 41);

    $date = CMbDT::date();
    /* Ensure that the date is not a sunday or an holyday, for ensuring that the test does not fail */
    while (!CMbDT::isWorkingDay($date)) {
      $date = CMbDT::date('+1 day', $date);
    }

    $act->execution = "{$date} 21:15:00";
    $act->store();

    $this->assertEquals('N', $act->complement);
  }

  /**
   * Vérifie que le complément de Nuit est bien supprimé si l'heure d'exécution est modifiée
   */
  public function testRemoveComplementNuitAuto() {
    $act = self::createActe('CS', 41);

    $date = CMbDT::date();
    /* Ensure that the date is not a sunday or an holyday, for ensuring that the test does not fail */
    while (!CMbDT::isWorkingDay($date)) {
      $date = CMbDT::date('+1 day', $date);
    }

    $act->execution = "{$date} 21:15:00";
    $act->store();

    $act->execution = "{$date} 18:15:00";
    $act->store();

    $this->assertEquals('', $act->complement);
  }

  /**
   * Vérifie que le complément Férié est bien appliqué si la date d'exécution tombe un jour férié
   *
   * @config ref_pays 1
   */
  public function testComplementFerieAuto() {
    $act = self::createActe('CS', 41);

    $act->execution = CMbDT::format(CMbDT::date(), '%Y-08-15 18:00:00');
    $act->store();

    $this->assertEquals('F', $act->complement);
  }

  /**
   * Vérifie que le complément Férié est bien appliqué si la date d'exécution tombe un dimanche
   */
  public function testComplementFerieSundayAuto() {
    $act = self::createActe('CS', 41);

    $act->execution = CMbDT::date('next sunday') . ' 18:00:00';
    $act->store();

    $this->assertEquals('F', $act->complement);
  }

  /**
   * Vérifie que le complément Férié est bien supprimé si la date d'exécution est modifiée
   */
  public function testRemoveComplementFerieAuto() {
    $act = self::createActe('CS', 41);

    $act->execution = CMbDT::date('next sunday') . ' 18:00:00';
    $act->store();

    $act->execution = CMbDT::date('next monday') . ' 18:00:00';
    $act->store();

    $this->assertEquals('', $act->complement);
  }

  /**
   * Vérifie que le complément Férié est prioritaire sur le Nuit
   */
  public function testPrioriteComplementNuitAuto() {
    $act = self::createActe('CS', 41);

    $act->execution = CMbDT::date('next sunday') . ' 21:00:00';
    $act->store();

    $this->assertEquals('N', $act->complement);
  }

  /**
   * Vérifie que les compléments ne sont pas appliqués si ils ne sont pas autorisés pour l'acte
   */
  public function testComplementNonAutorise() {
    $act = self::createActe('MPC', 41);

    $act->execution = CMbDT::date('next sunday') . ' 21:00:00';
    $act->store();

    $this->assertEquals('', $act->complement);
  }

  /**
   * Generate a user with the given CPAM speciality
   *
   * @param int $spec_cpam_id
   *
   * @return CMediusers
   * @throws \Exception
   */
  private static function generateUser(int $spec_cpam_id): CMediusers {
    $user = (new CMediusersGenerator())->generate('Médecin', $spec_cpam_id);
    $user->spec_cpam_id = 41;
    $user->store();

    return $user;
  }

  /**
   * Generate a consultation for the given user
   *
   * @param CMediusers $user
   *
   * @return CConsultation
   * @throws \Exception
   */
  private static function generateConsultation(CMediusers $user): CConsultation {
    return (new CConsultationGenerator())->setType('normal')->setPraticien($user)->generate();
  }

  /**
   * Create a CActeNGAP with the given code, for a user of the given CPAM speciality, and set the necessary data
   *
   * @param string $code
   * @param int    $spec_cpam_executant
   *
   * @return CActeNGAP
   * @throws \Exception
   */
  private static function createActe(string $code = 'C', int $spec_cpam_executant = 1): CActeNGAP {
    $user = self::generateUser($spec_cpam_executant);

    $consultation = self::generateConsultation($user);

    $act = new CActeNGAP();
    $act->object_class = $consultation->_class;
    $act->object_id = $consultation->_id;
    $act->executant_id = $user->_id;
    $act->coefficient = 1;
    $act->quantite = 1;
    $act->code = $code;
    $act->execution = CMbDT::dateTime();

    return $act;
  }
}
