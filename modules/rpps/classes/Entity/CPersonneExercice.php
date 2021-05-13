<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps\Entity;

use Exception;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CMbString;
use Ox\Import\Rpps\Exception\CImportMedecinException;
use Ox\Mediboard\Patients\CExercicePlace;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CMedecinExercicePlace;

/**
 * Description
 */
class CPersonneExercice extends CAbstractExternalRppsObject
{
    /** @var int */
    public $personne_exercice_id;

    /** @var string */
    public $code_civilite_exercice;

    /** @var string */
    public $libelle_civilite_exercice;

    /** @var string */
    public $code_civilite;

    /** @var string */
    public $libelle_civilite;

    /** @var string */
    public $code_profession;

    /** @var string */
    public $libelle_profession;

    /** @var string */
    public $code_categorie_pro;

    /** @var string */
    public $libelle_categorie_pro;

    /** @var string */
    public $code_type_savoir_faire;

    /** @var string */
    public $libelle_type_savoir_faire;

    /** @var string */
    public $code_savoir_faire;

    /** @var string */
    public $libelle_savoir_faire;

    /** @var string */
    public $code_mode_exercice;

    /** @var string */
    public $libelle_mode_exercice;

    /** @var string */
    public $siret_site;

    /** @var string */
    public $siren_site;

    /** @var string */
    public $finess_site;

    /** @var string */
    public $finess_etab_juridique;

    /** @var string */
    public $id_technique_structure;

    /** @var string */
    public $raison_sociale_site;

    /** @var string */
    public $enseigne_comm_site;

    /** @var string */
    public $comp_destinataire;

    /** @var string */
    public $comp_point_geo;

    /** @var string */
    public $num_voie;

    /** @var string */
    public $repetition_voie;

    /** @var string */
    public $code_type_voie;

    /** @var string */
    public $libelle_type_voie;

    /** @var string */
    public $libelle_voie;

    /** @var string */
    public $mention_distrib;

    /** @var string */
    public $cedex;

    /** @var string */
    public $cp;

    /** @var string */
    public $code_commune;

    /** @var string */
    public $libelle_commune;

    /** @var string */
    public $code_pays;

    /** @var string */
    public $libelle_pays;

    /** @var string */
    public $tel;

    /** @var string */
    public $tel2;

    /** @var string */
    public $fax;

    /** @var string */
    public $email;

    /** @var string */
    public $code_departement;

    /** @var string */
    public $libelle_departement;

    /** @var string */
    public $ancien_id_structure;

    /** @var string */
    public $autorite_enregistrement;

    /** @var string */
    public $code_secteur_activite;

    /** @var string */
    public $libelle_secteur_activite;

    /** @var string */
    public $code_section_tableau_pharma;

    /** @var string */
    public $libelle_section_tableau_pharma;

    /**
     * @inheritdoc
     */
    function getSpec(): CMbObjectSpec
    {
        $spec        = parent::getSpec();
        $spec->table = "personne_exercice";
        $spec->key   = 'personne_exercice_id';

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps(): array
    {
        $props = parent::getProps();

        $props['code_profession']                = 'str notNull';
        $props['libelle_profession']             = 'str notNull';
        $props['code_categorie_pro']             = 'str';
        $props['libelle_categorie_pro']          = 'str';
        $props['code_type_savoir_faire']         = 'str';
        $props['libelle_type_savoir_faire']      = 'str';
        $props['code_savoir_faire']              = 'str';
        $props['libelle_savoir_faire']           = 'str';
        $props['code_civilite_exercice']         = 'str';
        $props['libelle_civilite_exercice']      = 'str';
        $props['code_civilite']                  = 'str';
        $props['libelle_civilite']               = 'str';
        $props['code_mode_exercice']             = 'str';
        $props['libelle_mode_exercice']          = 'str';
        $props['siret_site']                     = 'str';
        $props['siren_site']                     = 'str';
        $props['finess_site']                    = 'str';
        $props['finess_etab_juridique']          = 'str';
        $props['id_technique_structure']         = 'str';
        $props['raison_sociale_site']            = 'str';
        $props['enseigne_comm_site']             = 'str';
        $props['comp_destinataire']              = 'str';
        $props['comp_point_geo']                 = 'str';
        $props['num_voie']                       = 'str';
        $props['repetition_voie']                = 'str';
        $props['code_type_voie']                 = 'str';
        $props['libelle_type_voie']              = 'str';
        $props['libelle_voie']                   = 'str';
        $props['mention_distrib']                = 'str';
        $props['cedex']                          = 'str';
        $props['cp']                             = 'str';
        $props['code_commune']                   = 'str';
        $props['libelle_commune']                = 'str';
        $props['code_pays']                      = 'str';
        $props['libelle_pays']                   = 'str';
        $props['tel']                            = 'str';
        $props['tel2']                           = 'str';
        $props['fax']                            = 'str';
        $props['email']                          = 'str';
        $props['code_departement']               = 'str';
        $props['libelle_departement']            = 'str';
        $props['ancien_id_structure']            = 'str';
        $props['autorite_enregistrement']        = 'str';
        $props['code_secteur_activite']          = 'str';
        $props['libelle_secteur_activite']       = 'str';
        $props['code_section_tableau_pharma']    = 'str';
        $props['libelle_section_tableau_pharma'] = 'str';

        return $props;
    }

    /**
     * @param CMedecin|null $medecin
     *
     * @return CMedecin
     */
    public function synchronize(?CMedecin $medecin = null): CMedecin
    {
        if (!$medecin || !$medecin->_id) {
            $medecin = new CMedecin();
        }

        // TODO load medecin with fields ?

        $medecin->nom    = ($this->nom) ? CMbString::upper($this->nom) : null;
        $medecin->prenom = ($this->prenom) ? CMbString::capitalize(CMbString::lower($this->prenom)) : null;
        $medecin->type   = ($this->code_profession) ? CMedecin::$types[$this->code_profession] : null;
        $medecin->cp     = ($this->cp) ?: null; // Temporary, remove when exercice_places will be used

        if (!$medecin->_id && $medecin->nom && $medecin->prenom && $medecin->type) {
            $medecin->loadMatchingObjectEsc();
        }

        // Update only once adeli on medecin. Will be removed when exercice places will be used
        if (
            $this->type_identifiant == CAbstractExternalRppsObject::TYPE_IDENTIFIANT_ADELI
            && !$medecin->adeli && $medecin->import_file_version != $this->version
        ) {
            $medecin->adeli = $this->identifiant;
        } elseif ($this->type_identifiant == CAbstractExternalRppsObject::TYPE_IDENTIFIANT_RPPS) {
            $medecin->rpps = $this->identifiant;
        }

        // Temporary, remove when exercice_places will be used
        $medecin->adresse   = $this->buildAdresse();
        $medecin->tel       = ($this->tel) ? $this->sanitizeTel($this->tel) : null;
        $medecin->tel_autre = ($this->tel2) ? $this->sanitizeTel($this->tel2) : null;
        $medecin->fax       = ($this->fax) ? $this->sanitizeTel($this->fax) : null;
        $medecin->email     = ($this->email) ?: null;
        $medecin->ville     = ($this->libelle_commune) ?: null;

        $medecin->categorie_professionnelle = ($this->libelle_categorie_pro) ? CMbString::lower(
            $this->libelle_categorie_pro
        ) : null;
        $this->addSavoirFaire($medecin);
        $medecin->titre = ($this->code_civilite_exercice) ? CMbString::lower($this->code_civilite_exercice) : null;
        $this->addSex($medecin);
        $this->addModeExercice($medecin);

        return $medecin;
    }

    /**
     * @param CMedecin $medecin
     *
     * @return string|CMedecinExercicePlace
     * @throws Exception
     */
    public function synchronizeExercicePlace(CMedecin $medecin, ?CExercicePlace $place)
    {
        if (!$medecin->_id) {
            throw new CImportMedecinException('CMedecin must be a valide object');
        }

        if (!$place || !$place->_id) {
            return null;
        }

        $medecin_place = new CMedecinExercicePlace();
        $medecin_place->medecin_id = $medecin->_id;
        $medecin_place->exercice_place_id = $place->_id;
        $medecin_place->loadMatchingObjectEsc();

        if ($this->type_identifiant === self::TYPE_IDENTIFIANT_ADELI) {
            $medecin_place->adeli = $this->identifiant;
        }

        $medecin_place->rpps_file_version = $this->version;

        if ($msg = $medecin_place->store()) {
            return $msg;
        }

        return null;
    }

    public function hashIdentifier(): string
    {
        if ($this->siret_site) {
            return md5(CExercicePlace::PREFIX_TYPE_SIRET . $this->siret_site);
        }

        if ($this->siren_site) {
            return md5(CExercicePlace::PREFIX_TYPE_SIREN . $this->siren_site);
        }

        if ($this->id_technique_structure) {
            return md5(CExercicePlace::PREFIX_TYPE_ID_TECHNIQUE . $this->id_technique_structure);
        }

        return md5($this->identifiant_national);
    }

    /**
     * @param CMedecin              $medecin
     * @param CMedecinExercicePlace $place
     *
     * @return CMedecinExercicePlace|string
     * @throws Exception
     */
    public function updateOrCreatePlace(CExercicePlace $place)
    {
        // Already imported for the file
        if ($place->_id && $place->rpps_file_version && $place->rpps_file_version == $this->version) {
            return $place;
        }

        $place->rpps_file_version = $this->version;
        $place->siret             = ($this->siret_site) ?: null;
        $place->siren             = ($this->siren_site) ?: null;
        $place->finess            = ($this->finess_site) ?: null;
        $place->finess_juridique  = ($this->finess_etab_juridique) ?: null;
        $place->id_technique      = ($this->id_technique_structure) ?: null;
        $place->raison_sociale    = ($this->raison_sociale_site) ?: null;
        $place->enseigne_comm     = ($this->enseigne_comm_site) ?: null;
        $place->comp_destinataire = ($this->comp_destinataire) ?: null;
        $place->comp_point_geo    = ($this->comp_point_geo) ?: null;
        $place->cp                = ($this->cp) ?: null;
        $place->commune           = ($this->libelle_commune) ?: null;
        $place->pays              = ($this->libelle_pays) ?: null;
        $place->tel               = ($this->tel) ? $this->sanitizeTel($this->tel) : null;
        $place->tel2              = ($this->tel2) ? $this->sanitizeTel($this->tel2) : null;
        $place->fax               = ($this->fax) ? $this->sanitizeTel($this->fax) : null;
        $place->email             = ($this->email) ?: null;
        $place->departement       = ($this->libelle_departement) ?: null;

        if ($adresse = $this->buildAdresse()) {
            $place->adresse = $adresse;
        }

        if ($msg = $place->store()) {
            return $msg;
        }

        return $place;
    }

    /**
     * @return string
     */
    private function buildAdresse(): ?string
    {
        $adresse = null;
        if ($this->num_voie) {
            $adresse = $this->num_voie . ' ';
        }

        if ($this->libelle_type_voie) {
            $adresse .= $this->libelle_type_voie . ' ';
        }

        if ($this->libelle_voie) {
            $adresse .= $this->libelle_voie . ' ';
        }

        if ($this->mention_distrib) {
            $adresse .= $this->mention_distrib . ' ';
        }

        if ($this->cedex) {
            $adresse .= $this->cedex;
        }

        return $adresse;
    }

    /**
     * @param CMedecin $medecin
     *
     * @return void
     */
    private function addSavoirFaire(CMedecin $medecin): void
    {
        if (!$this->code_savoir_faire) {
            return;
        }

        // Reset disciplines
        if ($medecin->import_file_version != $this->version) {
            $medecin->disciplines = null;
        }

        $new_discipline = "{$this->code_savoir_faire} : {$this->libelle_savoir_faire}";
        if (strpos($medecin->disciplines, $new_discipline) === false) {
            $medecin->disciplines .= $new_discipline . "\n";
        }
    }

    /**
     * @param CMedecin $medecin
     *
     * @return void
     */
    private function addSex(CMedecin $medecin): void
    {
        if (!$this->code_civilite) {
            return;
        }

        switch (CMbString::lower($this->code_civilite)) {
            case 'm':
                $medecin->sexe = 'm';
                break;
            case 'mme':
            case 'mlle':
                $medecin->sexe = 'f';
                break;
            default:
                $medecin->sexe = 'u';
        }
    }

    /**
     * @param CMedecin $medecin
     *
     * @return void
     */
    private function addModeExercice(CMedecin $medecin): void
    {
        if (!$this->code_mode_exercice) {
            return;
        }

        switch (CMbString::lower($this->code_mode_exercice)) {
            case 'l':
                $medecin->mode_exercice = 'liberal';
                break;
            case 's':
                $medecin->mode_exercice = 'salarie';
                break;
            case 'b':
                $medecin->mode_exercice = 'benevole';
                break;
            default:
                // Do nothing
        }
    }

    /**
     * @param string $str
     *
     * @return string|null
     */
    private function sanitizeTel(string $str): ?string
    {
        $str = preg_replace('/\D+/', '', $str);

        if (strlen($str) !== 10) {
            return null;
        }

        return $str;
    }
}
