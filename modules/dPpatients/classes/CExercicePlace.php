<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Exception;
use Ox\AppFine\Server\Appointment\CContactPlace;
use Ox\AppFine\Server\Appointment\CPresentation;
use Ox\AppFine\Server\Appointment\CSchedulePlace;
use Ox\AppFine\Server\Appointment\CTemporaryInformation;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;

/**
 * Description
 */
class CExercicePlace extends CMbObject
{
    public const PREFIX_TYPE_SIREN        = 'siren-';
    public const PREFIX_TYPE_SIRET        = 'siret-';
    public const PREFIX_TYPE_ID_TECHNIQUE = 'id_technique-';
    public const PREFIX_TYPE_MEDECIN      = 'medecin-';

    /** @var string */
    public const RESOURCE_NAME = 'exercicePlace';

    /** @var string */
    public const RELATION_MEDECIN_EXERCICE_PLACE = 'medecinExercicePlace';

    /** @var string */
    public const RELATION_PRESENTATION = 'presentation';

    /** @var string */
    public const RELATION_CONTACT_PLACE = "contactPlace";

    /** @var string */
    public const RELATION_SCHEDULE_PLACE = "schedulePlace";

    /** @var string */
    public const RELATION_TEMPORARY_INFORMATION = "temporaryInformation";

    /** @var integer Primary key */
    public $exercice_place_id;

    /** @var int */
    public $exercice_place_identifier;

    /** @var string */
    public $siret;
    /** @var string */
    public $siren;
    /** @var string */
    public $finess;
    /** @var string */
    public $finess_juridique;
    /** @var string */
    public $id_technique;
    /** @var string */
    public $raison_sociale;
    /** @var string */
    public $enseigne_comm;
    /** @var string */
    public $comp_destinataire;
    /** @var string */
    public $comp_point_geo;
    /** @var string */
    public $adresse;
    /** @var string */
    public $cp;
    /** @var string */
    public $commune;
    /** @var string */
    public $pays;
    /** @var string */
    public $tel;
    /** @var string */
    public $tel2;
    /** @var string */
    public $fax;
    /** @var string */
    public $email;
    /** @var string */
    public $departement;
    /** @var string */
    public $annule;
    /** @var string */
    public $rpps_file_version;

    /** @var CMedecinExercicePlace[] */
    public $_refs_medecin_exercice_places;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec                        = parent::getSpec();
        $spec->table                 = "exercice_place";
        $spec->key                   = "exercice_place_id";
        $spec->uniques["identifier"] = ['exercice_place_identifier'];
        $spec->loggable              = CMbObjectSpec::LOGGABLE_HUMAN;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props['exercice_place_identifier'] = 'str notNull fieldset|default';
        $props['siret']                     = 'str length|14 fieldset|default';
        $props['siren']                     = 'str fieldset|default';
        $props['finess']                    = 'str length|9 confidential mask|9xS9S99999S9 fieldset|default';
        $props['finess_juridique']          = 'str length|9 confidential mask|9xS9S99999S9 fieldset|default';
        $props['id_technique']              = 'str fieldset|default';
        $props['raison_sociale']            = 'str fieldset|default';
        $props['enseigne_comm']             = 'str fieldset|default';
        $props['comp_destinataire']         = 'str fieldset|default';
        $props['comp_point_geo']            = 'str fieldset|default';
        $props['adresse']                   = 'str seekable fieldset|default';
        $props['cp']                        = 'str length|5 seekable fieldset|default';
        $props['commune']                   = 'str seekable fieldset|default';
        $props['pays']                      = 'str seekable fieldset|default';
        $props['tel']                       = 'phone fieldset|default';
        $props['tel2']                      = 'phone fieldset|default';
        $props['fax']                       = 'phone fieldset|default';
        $props['email']                     = 'str fieldset|default';
        $props['departement']               = 'str fieldset|default';
        $props['annule']                    = 'bool default|0 fieldset|default';
        $props['rpps_file_version']         = 'str loggable|0 fieldset|default';

        return $props;
    }

    /**
     * @inheritDoc
     */
    public function store()
    {
        $this->completeField('exercice_place_identifier');
        if (!$this->_id || !$this->exercice_place_identifier) {
            $this->updateIdentifier();
        }

        return parent::store();
    }

    private function updateIdentifier(): void
    {
        if ($this->siren) {
            $this->exercice_place_identifier = md5(self::PREFIX_TYPE_SIREN . $this->siren);
        } elseif ($this->siret) {
            $this->exercice_place_identifier = md5(self::PREFIX_TYPE_SIRET . $this->siret);
        } elseif ($this->id_technique) {
            $this->exercice_place_identifier = md5(self::PREFIX_TYPE_ID_TECHNIQUE . $this->id_technique);
        }
    }

    public static function loadFromIdentifier(array $identifiers): array
    {
        $place = new self();
        $ds    = $place->getDS();

        $places = $place->loadList(['exercice_place_identifier' => $ds->prepareIn($identifiers)]) ?: [];
        $places_hash = [];
        /** @var CExercicePlace $_place */
        foreach ($places as $_place) {
            $places_hash[$_place->exercice_place_identifier] = $_place;
        }

        return $places_hash;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function loadRefsMedecinExercicePlaces(): array
    {
      return $this->_refs_medecin_exercice_places = $this->loadBackRefs("exercice_places");
    }

    /**
     * @return CCollection|null
     * @throws Exception|CApiException
     */
    public function getResourceMedecinExercicePlace(): ?CCollection
    {
      $medecin_exercice_place                    = new CMedecinExercicePlace();
      $medecin_exercice_place->exercice_place_id = $this->_id;

      $medecin_exercice_places = $medecin_exercice_place->loadMatchingList();

      if (empty($medecin_exercice_places)) {
        return null;
      }

      return new CCollection($medecin_exercice_places);
    }

    /**
     * @return CItem|null
     * @throws Exception|CApiException
     */
    public function getResourcePresentation(): ?CItem
    {
      $presentation               = new CPresentation();
      $presentation->object_id    = $this->_id;
      $presentation->object_class = $this->_class;
      $presentation->loadMatchingObject();

      if (!$presentation || !$presentation->_id) {
        return null;
      }

      return new CItem($presentation);
    }

    /**
     * @return CItem|null
     * @throws Exception|CApiException
     */
    public function getResourceContactPlace(): ?CItem
    {
      $contact_place               = new CContactPlace();
      $contact_place->object_id    = $this->_id;
      $contact_place->object_class = $this->_class;
      $contact_place->loadMatchingObject();

      if (!$contact_place || !$contact_place->_id) {
        return null;
      }

      return new CItem($contact_place);
    }

    /**
     * @return CCollection|null
     * @throws Exception|CApiException
     */
    public function getResourceSchedulePlace(): ?CCollection
    {
      $schedule_place               = new CSchedulePlace();
      $schedule_place->object_id    = $this->_id;
      $schedule_place->object_class = $this->_class;

      $schedule_places = $schedule_place->loadMatchingList();

      if (empty($schedule_places)) {
        return null;
      }

      return new CCollection($schedule_places);
    }

    /**
     * @return CItem|null
     * @throws Exception|CApiException
     */
    public function getResourceTemporaryInformation(): ?CItem
    {
      $temporary_information               = new CTemporaryInformation();
      $temporary_information->object_id    = $this->_id;
      $temporary_information->object_class = $this->_class;
      $temporary_information->active       = true;
      $temporary_information->loadMatchingObject();

      if (!$temporary_information || !$temporary_information->_id) {
        return null;
      }

      return new CItem($temporary_information);
    }
}
