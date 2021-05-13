<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Validator;

use Ox\Core\Specification\SpecificationInterface;
use Ox\Import\Framework\Spec\AntecedentSpecBuilder;
use Ox\Import\Framework\Spec\ConsultationAnesthSpecBuilder;
use Ox\Import\Framework\Spec\ConsultationSpecBuilder;
use Ox\Import\Framework\Spec\CorrespondantSpecBuilder;
use Ox\Import\Framework\Spec\EvenementPatientSpecBuilder;
use Ox\Import\Framework\Spec\FileSpecBuilder;
use Ox\Import\Framework\Spec\MedecinSpecBuilder;
use Ox\Import\Framework\Spec\PatientSpecBuilder;
use Ox\Import\Framework\Spec\PlageConsultSpecBuilder;
use Ox\Import\Framework\Spec\SejourSpecBuilder;
use Ox\Import\Framework\Spec\TraitementSpecBuilder;
use Ox\Import\Framework\Spec\UserSpecBuilder;

/**
 * Description
 */
class DefaultValidator extends AbstractValidator
{
    /**
     * @inheritDoc
     */
    protected function getExternalUserSpec(): ?SpecificationInterface
    {
        return (new UserSpecBuilder())->build();
    }

    /**
     * @inheritDoc
     */
    public function getExternalPatientSpec(): ?SpecificationInterface
    {
        return (new PatientSpecBuilder())->build();
    }

    /**
     * @inheritDoc
     */
    protected function getExternalMedecinSpec(): ?SpecificationInterface
    {
        return (new MedecinSpecBuilder())->build();
    }

    /**
     * @inheritDoc
     */
    protected function getExternalPlageConsultSpec(): ?SpecificationInterface
    {
        return (new PlageConsultSpecBuilder())->build();
    }

    /**
     * @inheritDoc
     */
    protected function getExternalConsultationSpec(): ?SpecificationInterface
    {
        return (new ConsultationSpecBuilder())->build();
    }

    /**
     * @inheritDoc
     */
    protected function getConsultationAnesthSpec(): ?SpecificationInterface
    {
        return (new ConsultationAnesthSpecBuilder())->build();
    }

    protected function getExternalSejourSpec(): ?SpecificationInterface
    {
        return (new SejourSpecBuilder())->build();
    }

    protected function getExternalFileSpec(): ?SpecificationInterface
    {
        return (new FileSpecBuilder())->build();
    }

    protected function getExternalAntecedentSpec(): ?SpecificationInterface
    {
        return (new AntecedentSpecBuilder())->build();
    }

    protected function getExternalTraitementSpec(): ?SpecificationInterface
    {
        return (new TraitementSpecBuilder())->build();
    }

    protected function getExternalCorrespondantSpec(): ?SpecificationInterface
    {
        return (new CorrespondantSpecBuilder())->build();
    }

    protected function getExternalEvenementPatientSpec(): ?SpecificationInterface
    {
        return (new EvenementPatientSpecBuilder())->build();
    }
}
