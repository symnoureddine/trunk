<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Validator;

use Ox\Core\Specification\SpecificationViolation;
use Ox\Import\Framework\Entity\Antecedent;
use Ox\Import\Framework\Entity\Consultation;
use Ox\Import\Framework\Entity\ConsultationAnesth;
use Ox\Import\Framework\Entity\Correspondant;
use Ox\Import\Framework\Entity\EvenementPatient;
use Ox\Import\Framework\Entity\File;
use Ox\Import\Framework\Entity\Medecin;
use Ox\Import\Framework\Entity\Patient;
use Ox\Import\Framework\Entity\PlageConsult;
use Ox\Import\Framework\Entity\Sejour;
use Ox\Import\Framework\Entity\Traitement;
use Ox\Import\Framework\Entity\User;

/**
 * Description
 */
interface ValidatorVisitorInterface {
  /**
   * Validate an external user
   *
   * @param User $user
   *
   * @return SpecificationViolation|null
   */
  public function validateUser(User $user): ?SpecificationViolation;

  /**
   * Validate an external patient
   *
   * @param Patient $patient
   *
   * @return SpecificationViolation|null
   */
  public function validatePatient(Patient $patient): ?SpecificationViolation;

  /**
   * Validate an external medecin
   *
   * @param Medecin $medecin
   *
   * @return SpecificationViolation|null
   */
  public function validateMedecin(Medecin $medecin): ?SpecificationViolation;

  /**
   * Validate an external plage consult
   *
   * @param PlageConsult $plage_consult
   *
   * @return SpecificationViolation|null
   */
  public function validatePlageConsult(PlageConsult $plage_consult): ?SpecificationViolation;

  /**
   * Validate an external consultation anesth
   *
   * @param Consultation $consultation
   *
   * @return SpecificationViolation|null
   */
  public function validateConsultation(Consultation $consultation): ?SpecificationViolation;

  /**
   * Validate an external consultation
   *
   * @param ConsultationAnesth $consultation
   *
   * @return SpecificationViolation|null
   */
  public function validateConsultationAnesth(ConsultationAnesth $consultation): ?SpecificationViolation;

  /**
   * Validate an external sejour
   *
   * @param Sejour $sejour
   *
   * @return SpecificationViolation|null
   */
  public function validateSejour(Sejour $sejour): ?SpecificationViolation;

  /**
   * Validate an external file
   *
   * @param File $file
   *
   * @return SpecificationViolation|null
   */
  public function validateFile(File $file): ?SpecificationViolation;

  public function validateAntecedent(Antecedent $antecedent): ?SpecificationViolation;

  public function validateTraitement(Traitement $traitement): ?SpecificationViolation;

  public function validateCorrespondant(Correspondant $correspondant): ?SpecificationViolation;

  public function validateEvenementPatient(EvenementPatient $evenement_patient): ?SpecificationViolation;
}
