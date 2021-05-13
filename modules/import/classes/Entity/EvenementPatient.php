<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Entity;

use DateTime;
use Ox\Core\CStoredObject;
use Ox\Core\Specification\SpecificationViolation;
use Ox\Import\Framework\ImportableInterface;
use Ox\Import\Framework\Transformer\TransformerVisitorInterface;
use Ox\Import\Framework\Validator\ValidatorVisitorInterface;

/**
 * External evenement patient representation
 */
class EvenementPatient extends AbstractEntity
{
    private const EXTERNAL_CLASS = 'EVTPA';

    /** @var DateTime */
    protected $date;

    /** @var string */
    protected $libelle;

    /** @var string */
    protected $description;

    /** @var string */
    protected $praticien_id;

    /** @var string */
    protected $patient_id;

    /** @var string */
    protected $type;

    //    protected $default_refs = true;

    /**
     * @inheritDoc
     */
    public function validate(ValidatorVisitorInterface $validator): ?SpecificationViolation
    {
        return $validator->validateEvenementPatient($this);
    }

    /**
     * @inheritDoc
     */
    public function transform(
        TransformerVisitorInterface $transformer,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): ImportableInterface {
        return $transformer->transformEvenementPatient($this, $reference_stash, $campaign);
    }

    /**
     * Get the refs objects to to import
     *
     * @return array
     */
    public function getDefaultRefEntities(): array
    {
        return [
            ExternalReference::getMandatoryFor('patient', $this->patient_id),
            ExternalReference::getMandatoryFor('utilisateur', $this->praticien_id),
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
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @return CStoredObject
     */
    public function getMbObject(): ?CStoredObject
    {
        return $this->mb_object;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getLibelle(): string
    {
        return $this->libelle;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPraticienId(): string
    {
        return $this->praticien_id;
    }

    /**
     * @return string
     */
    public function getPatientId(): string
    {
        return $this->patient_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

}
