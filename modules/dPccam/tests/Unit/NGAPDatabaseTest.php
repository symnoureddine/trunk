<?php
/**
 * @package Mediboard\Ccam\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam\Tests\Unit;

use Ox\Core\CSQLDataSource;
use Ox\Tests\UnitTestMediboard;

/**
 * Class permettant de tester la base NGAP
 */
class NGAPDatabaseTest extends UnitTestMediboard {

  /** @var array An array of the error */
  protected $errors = array();

  /**
   * Teste la conformit� de la base NGAP pour toutes les sp�cialit�s
   *
   * @return void
   */
  public function testNGAPActsPrice() {
    $specialities = NGAPData::getSpecialities();

    foreach ($specialities as $speciality) {
      $this->checkNGAPActsPriceForSpeciality($speciality);
    }

    $this->assertFalse($this->hasErrors(), $this->report());
  }

  /**
   * Teste la conformit� de la base NGAP pour une sp�cialit�
   *
   * @param integer $speciality The speciality number
   *
   * @return void
   */
  protected function checkNGAPActsPriceForSpeciality($speciality) {
    $acts = NGAPData::getActsForSpeciality($speciality);

    $ds = CSQLDataSource::get('ccamV2');

    foreach ($acts as $act) {
      $query = "SELECT t.`tarif` FROM `tarif_ngap` as t
        LEFT JOIN `specialite_to_tarif_ngap` as s ON s.`tarif_id` = t.`tarif_ngap_id`
        WHERE t.`zone` = 'metro' AND s.specialite = $speciality AND t.`code` = '{$act['code']}'
        AND (t.fin IS NULL OR t.fin >= DATE(NOW())) AND (t.debut IS NULL OR t.debut <= DATE(NOW()));";

      $result = $ds->exec($query);

      if (!$result) {
        $this->addError($speciality, $act['code'], "Code non disponible pour la sp�cialit�");
        continue;
      }

      if ($ds->numRows($result) > 1) {
        $this->addError($speciality, $act['code'], 'Plusieurs entr�es en base pour le code et la sp�cialit�');
        continue;
      }

      $row = $ds->fetchAssoc($result);
      if ($act['price'] != $row['tarif']) {
        $this->addError($speciality, $act['code'], "Prix en base non conforme. Attendu : {$act['price']}, Actuel : {$row['tarif']}");
      }
    }
  }

  /**
   * Ajoute une erreur dans la pile d'erreurs
   *
   * @param integer $speciality Le num�ro de sp�cialit�
   * @param string  $code       Le code NGAP
   * @param string  $error      Le message d'erreur
   *
   * @return void
   */
  protected function addError($speciality, $code, $error) {
    $this->errors[] = array('speciality' => $speciality,'code' => $code,'error' => $error);
  }

  /**
   * V�rifie si il y a eu des erreurs g�n�r�es
   *
   * @return bool
   */
  protected function hasErrors() {
    return count($this->errors) > 0;
  }

  /**
   * Construit le rapport d'erreur
   *
   * @return string
   */
  protected function report() {
    $report = count($this->errors) . " erreurs d�tect�es :\n";

    foreach ($this->errors as $error) {
      $report .= "Sp� {$error['speciality']}, Code {$error['code']} : {$error['error']}\n";
    }

    return $report;
  }
}
