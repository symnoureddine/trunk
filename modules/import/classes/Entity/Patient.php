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
 * External patient representation
 */
class Patient extends AbstractEntity
{
    public const EXTERNAL_CLASS = 'PATI';

    /** @var string */
    protected $nom;

    /** @var string */
    protected $prenom;

    /** @var DateTime */
    protected $naissance;

    /** @var string */
    protected $nom_jeune_fille;

    /** @var string */
    protected $profession;

    /** @var string */
    protected $email;

    /** @var string */
    protected $tel;

    /** @var string */
    protected $tel2;

    /** @var string */
    protected $tel_autre;

    /** @var string */
    protected $adresse;

    /** @var string */
    protected $cp;

    /** @var string */
    protected $ville;

    /** @var string */
    protected $pays;

    /** @var string */
    protected $matricule;

    /** @var string */
    protected $sexe;

    /** @var string */
    protected $civilite;

    /** @var string */
    protected $rques;

    /** @var mixed */
    protected $medecin_traitant;

    /**
     * @inheritDoc
     */
    public function validate(ValidatorVisitorInterface $validator): ?SpecificationViolation
    {
        return $validator->validatePatient($this);
    }

    /**
     * @inheritDoc
     */
    public function transform(
        TransformerVisitorInterface $transformer,
        ?ExternalReferenceStash $reference_stash = null,
        ?CImportCampaign $campaign = null
    ): ImportableInterface {
        return $transformer->transformPatient($this, $reference_stash, $campaign);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultRefEntities(): array
    {
        return [
            ExternalReference::getNotMandatoryFor('medecin', $this->medecin_traitant),
        ];
    }

    public function getCollections(): array
    {
        return [
            'antecedent'   => 'patient_id',
            'consultation' => 'patient_id',
        ];
    }

    /**
     * TODO Use this for DFSImport
     *
     * @return array
     */
    public function getCollectionsObjects(): array
    {
        return [];
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
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * @return string
     */
    public function getPrenom(): string
    {
        return $this->prenom;
    }

    /**
     * @return DateTime
     */
    public function getNaissance(): ?DateTime
    {
        return $this->naissance;
    }

    /**
     * @return string
     */
    public function getNomJeuneFille(): ?string
    {
        return $this->nom_jeune_fille;
    }

    /**
     * @return string
     */
    public function getProfession(): ?string
    {
        return $this->profession;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getTel(): ?string
    {
        return $this->tel;
    }

    /**
     * @return string
     */
    public function getTel2(): ?string
    {
        return $this->tel2;
    }

    /**
     * @return string
     */
    public function getTelAutre(): ?string
    {
        return $this->tel_autre;
    }

    /**
     * @return string
     */
    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    /**
     * @return string
     */
    public function getCp(): ?string
    {
        return $this->cp;
    }

    /**
     * @return string
     */
    public function getVille(): ?string
    {
        return $this->ville;
    }

    /**
     * @return string
     */
    public function getPays(): ?string
    {
        return $this->pays;
    }

    /**
     * @return string
     */
    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    /**
     * @return string
     */
    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    /**
     * @return string
     */
    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    /**
     * @return mixed
     */
    public function getMedecinTraitant()
    {
        return $this->medecin_traitant;
    }

    public function getRques()
    {
        return $this->rques;
    }
}
