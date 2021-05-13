<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Validator;

use Ox\Core\Specification\SpecificationInterface;
use Ox\Core\Specification\SpecificationViolation;
use Ox\Import\Framework\Configuration\ConfigurableInterface;
use Ox\Import\Framework\Configuration\ConfigurationTrait;
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
use Ox\Import\Framework\Entity\ValidationAwareInterface;

/**
 * Description
 */
abstract class AbstractValidator implements ValidatorVisitorInterface, ConfigurableInterface {
  use ConfigurationTrait;

  /**
   * @param SpecificationInterface|null $spec
   * @param ValidationAwareInterface    $object
   *
   * @return SpecificationViolation|null
   */
  protected function validateObject(?SpecificationInterface $spec, ValidationAwareInterface $object): ?SpecificationViolation {
    if ($spec === null) {
      return null;
    }

    $remains = $spec->remainderUnsatisfiedBy($object);

    if ($remains instanceof SpecificationInterface) {
      return $remains->toViolation($object);
    }

    return null;
  }

  /**
   * Get the specification for external user
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalUserSpec(): ?SpecificationInterface;

  /**
   * Return the specifications for external patient
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalPatientSpec(): ?SpecificationInterface;

  /**
   * Return the specifications for external medecin
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalMedecinSpec(): ?SpecificationInterface;

  /**
   * Return the specifications for external plageconsult
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalPlageConsultSpec(): ?SpecificationInterface;

  /**
   * Return the specification for external consultation
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalConsultationSpec(): ?SpecificationInterface;

  /**
   * Return the specification for external consultation anesth
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getConsultationAnesthSpec(): ?SpecificationInterface;

  /**
   * Return the specification for external sejour
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalSejourSpec(): ?SpecificationInterface;

  /**
   * Return the specifications for external file
   *
   * @return SpecificationInterface|null
   */
  abstract protected function getExternalFileSpec(): ?SpecificationInterface;

  abstract protected function getExternalAntecedentSpec(): ?SpecificationInterface;

  abstract protected function getExternalTraitementSpec(): ?SpecificationInterface;

  abstract protected function getExternalCorrespondantSpec(): ?SpecificationInterface;

  abstract protected function getExternalEvenementPatientSpec(): ?SpecificationInterface;

  /**
   * @inheritDoc
   */
  public function validateUser(User $user): ?SpecificationViolation {
    return $this->validateObject($this->getExternalUserSpec(), $user);
  }

  /**
   * @inheritDoc
   */
  public function validatePatient(Patient $patient): ?SpecificationViolation {
    return $this->validateObject($this->getExternalPatientSpec(), $patient);
  }

  /**
   * @inheritDoc
   */
  public function validateMedecin(Medecin $medecin): ?SpecificationViolation {
    return $this->validateObject($this->getExternalMedecinSpec(), $medecin);
  }

  /**
   * @inheritDoc
   */
  public function validatePlageConsult(PlageConsult $plage_consult): ?SpecificationViolation {
    return $this->validateObject($this->getExternalPlageConsultSpec(), $plage_consult);
  }

  /**
   * @inheritDoc
   */
  public function validateConsultation(Consultation $consultation): ?SpecificationViolation {
    return $this->validateObject($this->getExternalConsultationSpec(), $consultation);
  }

  /**
   * @inheritDoc
   */
  public function validateConsultationAnesth(ConsultationAnesth $consultation): ?SpecificationViolation {
    return $this->validateObject($this->getConsultationAnesthSpec(), $consultation);
  }

  /**
   * @inheritDoc
   */
  public function validateSejour(Sejour $sejour): ?SpecificationViolation {
    return $this->validateObject($this->getExternalSejourSpec(), $sejour);
  }

  /**
   * @inheritDoc
   */
  public function validateFile(File $file): ?SpecificationViolation {
    return $this->validateObject($this->getExternalFileSpec(), $file);
  }

  public function validateAntecedent(Antecedent $antecedent): ?SpecificationViolation
  {
      return $this->validateObject($this->getExternalAntecedentSpec(), $antecedent);
  }

  public function validateTraitement(Traitement $traitement): ?SpecificationViolation
  {
      return $this->validateObject($this->getExternalTraitementSpec(), $traitement);
  }

  public function validateCorrespondant(Correspondant $correspondant): ?SpecificationViolation
  {
      return $this->validateObject($this->getExternalCorrespondantSpec(), $correspondant);
  }

  public function validateEvenementPatient(EvenementPatient $evenement_patient): ?SpecificationViolation
  {
      return $this->validateObject($this->getExternalEvenementPatientSpec(), $evenement_patient);
  }
}
