<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Entity;

use DateTime;
use Ox\Core\Specification\SpecificationViolation;
use Ox\Import\Framework\ImportableInterface;
use Ox\Import\Framework\Transformer\TransformerVisitorInterface;
use Ox\Import\Framework\Validator\ValidatorVisitorInterface;

/**
 * Description
 */
class Sejour extends AbstractEntity
{
    private const EXTERNAL_CLASS = 'SEJR';

    /** @var string */
    protected $type;

    /** @var DateTime */
    protected $entree_prevue;

    /** @var DateTime */
    protected $entree_reelle;

    /** @var DateTime */
    protected $sortie_prevue;

    /** @var DateTime */
    protected $sortie_reelle;

    /** @var string */
    protected $libelle;

    protected $patient_id;
    protected $praticien_id;
    protected $group_id;

    /**
     * @inheritDoc
     */
    public function validate(ValidatorVisitorInterface $validator): ?SpecificationViolation
    {
        return $validator->validateSejour($this);
    }

    /**
     * @inheritDoc
     */
    public function transform(
        TransformerVisitorInterface $transformer,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): ImportableInterface {
        return $transformer->transformSejour($this, $reference_stash, $campaign);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultRefEntities(): array
    {
        return [
            ExternalReference::getMandatoryFor('utilisateur', $this->praticien_id),
            ExternalReference::getMandatoryFor('patient', $this->patient_id),
            // TODO Handle Group
        ];
    }

    /**
     * @inheritDoc
     */
    public function getExternalClass()
    {
        return static::EXTERNAL_CLASS;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    /**
     * @return mixed
     */
    public function getPatientId()
    {
        return $this->patient_id;
    }

    /**
     * @return mixed
     */
    public function getPraticienId()
    {
        return $this->praticien_id;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * @return DateTime
     */
    public function getEntreePrevue(): ?DateTime
    {
        return $this->entree_prevue;
    }

    /**
     * @return DateTime
     */
    public function getEntreeReelle(): ?DateTime
    {
        return $this->entree_reelle;
    }

    /**
     * @return DateTime
     */
    public function getSortiePrevue(): ?DateTime
    {
        return $this->sortie_prevue;
    }

    /**
     * @return DateTime
     */
    public function getSortieReelle(): ?DateTime
    {
        return $this->sortie_reelle;
    }
}
