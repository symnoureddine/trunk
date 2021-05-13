<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Spec;

use DateTime;
use Exception;
use Ox\Core\CAppUI;
use Ox\Core\Specification\AndX;
use Ox\Core\Specification\Enum;
use Ox\Core\Specification\Equal;
use Ox\Core\Specification\IsNull;
use Ox\Core\Specification\Match;
use Ox\Core\Specification\MaxLength;
use Ox\Core\Specification\NotNull;
use Ox\Core\Specification\OrX;
use Ox\Core\Specification\SpecificationInterface;

/**
 * External patient spec builder
 */
class PatientSpecBuilder
{

    use SpecBuilderTrait;

    private const NAISSANCE_MIN = '1850-01-01';
    private const CONF_ADRESSE  = 'dPpatients CPatient addr_patient_mandatory';
    private const CONF_CP       = 'dPpatients CPatient cp_patient_mandatory';
    private const CONF_TEL      = 'dPpatients CPatient tel_patient_mandatory';

    private const FIELD_ID               = 'external_id';
    private const FIELD_NOM              = 'nom';
    private const FIELD_PRENOM           = 'prenom';
    private const FIELD_NAISSANCE        = 'naissance';
    private const FIELD_NJF              = 'nom_jeune_fille';
    private const FIELD_PROFESSION       = 'profession';
    private const FIELD_EMAIL            = 'email';
    private const FIELD_TEL              = 'tel';
    private const FIELD_TEL2             = 'tel2';
    private const FIELD_TEL_AUTRE        = 'tel_autre';
    private const FIELD_ADRESSE          = 'adresse';
    private const FIELD_CP               = 'cp';
    private const FIELD_VILLE            = 'ville';
    private const FIELD_PAYS             = 'pays';
    private const FIELD_MATRICULE        = 'matricule';
    private const FIELD_SEXE             = 'sexe';
    private const FIELD_CIVILITE         = 'civilite';
    private const FIELD_MEDECIN_TRAITANT = 'medecin_traitant';

    private const SEXE_F = 'f';
    private const SEXE_M = 'm';


    /**
     * @return SpecificationInterface|null
     * @throws Exception
     */
    public function build(): ?SpecificationInterface
    {
        $specs_to_add = [];

        $specs_to_add[] = $this->buildSpec(self::FIELD_ID);
        $specs_to_add[] = $this->buildSpec(self::FIELD_NOM);
        $specs_to_add[] = $this->buildSpec(self::FIELD_PRENOM);
        $specs_to_add[] = $this->buildSpec(self::FIELD_NAISSANCE);


        if ($spec = $this->buildSpec(self::FIELD_PROFESSION)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_EMAIL)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_TEL)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_TEL2)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_TEL_AUTRE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_ADRESSE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_CP)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_VILLE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_PAYS)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_MATRICULE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_SEXE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_CIVILITE)) {
            $specs_to_add[] = $spec;
        }

        if ($spec = $this->buildSpec(self::FIELD_MEDECIN_TRAITANT)) {
            $specs_to_add[] = $spec;
        }

        // Todo: Handle null (currently not allowed in Composite specification)
        return new AndX(...$specs_to_add);
    }

    /**
     * Build spec depending on $spec_name
     *
     * @param string $spec_name
     *
     * @return SpecificationInterface|null
     * @throws Exception
     */
    private function buildSpec(string $spec_name): ?SpecificationInterface
    {
        switch ($spec_name) {
            case self::FIELD_ID:
                return $this->getNotNullSpec($spec_name);

            case self::FIELD_NOM:
                return new OrX($this->getNamesSpec(self::FIELD_NOM), $this->getNamesSpec(self::FIELD_NJF));
            case self::FIELD_PRENOM:
                return $this->getNamesSpec($spec_name);

            case self::FIELD_NAISSANCE:
                return $this->getNaissanceSpec($spec_name, true);

            case self::FIELD_PROFESSION:
                return $this->getProfessionSpec();

            case self::FIELD_EMAIL:
                return $this->getEmailSpec(self::FIELD_EMAIL);

            case self::FIELD_TEL:
            case self::FIELD_TEL2:
            case self::FIELD_TEL_AUTRE:
                return $this->getTelSpec($spec_name);

            case self::FIELD_ADRESSE:
                return $this->getAdresseSpec();

            case self::FIELD_CP:
                return $this->getCpSpec($spec_name);

            case self::FIELD_NJF:
                return $this->getNomNaissanceSpec();

            case self::FIELD_VILLE:
                return $this->getVilleSpec();

            case self::FIELD_MATRICULE:
                return $this->getMatriculeSpec();

            case self::FIELD_CIVILITE:
                return $this->getCiviliteSpec();

            case self::FIELD_MEDECIN_TRAITANT:
                return $this->getMedecinTraitantSpec();

            default:
                return null;
        }
    }

    /**
     * Build a not null spec
     *
     * @param string $field_name
     *
     * @return NotNull
     */
    private function getNotNullSpec($field_name): NotNull
    {
        return NotNull::is($field_name);
    }

    public function getNamesSpec(string $field_name): ?SpecificationInterface
    {
        return new AndX(
            NotNull::is($field_name),
            MaxLength::is($field_name, 255)
        );
    }

    /**
     * @return SpecificationInterface|null
     */
    private function getAdresseSpec(): ?SpecificationInterface
    {
        return (CAppUI::gconf(self::CONF_ADRESSE)) ? NotNull::is(self::FIELD_ADRESSE) : null;
    }

    /**
     * @return NotNull|OrX|null
     */
    private function getNomNaissanceSpec()
    {
        return NotNull::is(self::FIELD_NJF);
    }

    /**
     * @return AndX|null
     */
    private function getVilleSpec(): ?SpecificationInterface
    {
        if (CAppUI::gconf(self::CONF_ADRESSE)) {
            return new AndX(
                NotNull::is(self::FIELD_VILLE),
                MaxLength::is(self::FIELD_VILLE, 80)
            );
        }

        return null;
    }

    /**
     * @return AndX|null
     */
    private function getPaysSpec(): ?SpecificationInterface
    {
        if (CAppUI::gconf(self::CONF_ADRESSE)) {
            return new AndX(
                NotNull::is(self::FIELD_PAYS),
                MaxLength::is(self::FIELD_PAYS, 80)
            );
        }

        return null;
    }


    private function getProfessionSpec(): SpecificationInterface
    {
        return new OrX(
            MaxLength::is(self::FIELD_PROFESSION, 255),
            IsNull::is(self::FIELD_PROFESSION)
        );
    }

    private function getMatriculeSpec(): OrX
    {
        return new OrX(
            Match::is(self::FIELD_MATRICULE, '/^\d{13,15}$/'),
            IsNull::is(self::FIELD_MATRICULE)
        );
    }

    private function getSexeSpec(): OrX
    {
        return new OrX(
            Enum::is(self::FIELD_SEXE, ['m', 'f', 'u']),
            IsNull::is(self::FIELD_SEXE)
        );
    }

    private function getCiviliteSpec(): OrX
    {
        return new OrX(
            Enum::is(self::FIELD_CIVILITE, ['m', 'mme', 'mlle', 'enf', 'dr', 'pr', 'me', 'vve']),
            IsNull::is(self::FIELD_CIVILITE)
        );
    }

    private function getMedecinTraitantSpec(): MaxLength
    {
        return MaxLength::is(self::FIELD_MEDECIN_TRAITANT, 11);
    }
}
