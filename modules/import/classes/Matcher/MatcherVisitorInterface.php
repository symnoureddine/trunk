<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Matcher;

use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CCorrespondant;
use Ox\Mediboard\Patients\CEvenementPatient;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CTraitement;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Description
 */
interface MatcherVisitorInterface {
  /**
   * Match a user
   *
   * @param CUser $user
   *
   * @return CUser
   */
  public function matchUser(CUser $user): CUser;

  /**
   * Match a patient
   *
   * @param CPatient $patient
   *
   * @return CPatient
   */
  public function matchPatient(CPatient $patient): CPatient;

  /**
   * Match a medecin
   *
   * @param CMedecin $medecin
   *
   * @return CMedecin
   */
  public function matchMedecin(CMedecin $medecin): CMedecin;

  /**
   * Match a plage consult
   *
   * @param CPlageconsult $plage_consult
   *
   * @return CPlageconsult
   */
  public function matchPlageConsult(CPlageconsult $plage_consult): CPlageconsult;

  /**
   * Match a consultation
   *
   * @param CConsultation $consultation
   *
   * @return CConsultation
   */
  public function matchConsultation(CConsultation $consultation): CConsultation;

  /**
   * Match a consultation
   *
   * @param CConsultAnesth $consultation
   *
   * @return CConsultAnesth
   */
  public function matchConsultationAnesth(CConsultAnesth $consultation): CConsultAnesth;

  /**
   * Match a sejour
   *
   * @param CSejour $sejour
   *
   * @return CSejour
   */
  public function matchSejour(CSejour $sejour): CSejour;

  /**
   * Match a file
   *
   * @param CFile $file
   *
   * @return CFile
   */
  public function matchFile(CFile $file): CFile;

  public function matchAntecedent(CAntecedent $antecedent): CAntecedent;

  public function matchTraitement(CTraitement $trt): CTraitement;

  public function matchCorrespondant(CCorrespondant $correspondant): CCorrespondant;

  public function matchEvenementPatient(CEvenementPatient $evenement_patient): CEvenementPatient;
}
