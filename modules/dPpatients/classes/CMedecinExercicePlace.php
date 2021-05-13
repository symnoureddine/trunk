<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Exception;
use Ox\AppFine\Server\Appointment\CHonoraryPlace;
use Ox\AppFine\Server\Appointment\CInformationTarifPlace;
use Ox\AppFine\Server\Appointment\CSchedulePlace;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;

/**
 * Description
 */
class CMedecinExercicePlace extends CMbObject
{
    /** @var string */
    public const RESOURCE_NAME = 'medecinExercicePlace';

    /** @var string */
    public const RELATION_MEDECIN = 'medecin';

    /** @var string */
    public const RELATION_EXERCICE_PLACE = 'exercicePlace';

    /** @var string */
    public const RELATION_HONORARY_PLACE = 'honoraryPlace';

    /** @var string */
    public const RELATION_SCHEDULE_PLACE = 'schedulePlace';

    /** @var string */
    public const RELATION_INFORMATION_TARIF_PLACE = 'informationTarifPlace';

    /** @var string */
    public const RELATION_PRESENTATION = 'presentation';

    /** @var string */
    public const FIELDSET_TARGET = 'target';

    /** @var string */
    public const FIELDSET_IDENTIFIERS = 'identifiers';

    /** @var int Primary key */
    public $medecin_exercice_place_id;

    /** @var int */
    public $medecin_id;

    /** @var int */
    public $exercice_place_id;

    /** @var string */
    public $adeli;

    /** @var string */
    public $rpps_file_version;

    /** @var CMedecin */
    public $_ref_medecin;

    /** @var CExercicePlace */
    public $_ref_exercice_place;

    /** @var CMedecin[] */
    public $_ref_medecins;

    /** @var CExercicePlace[] */
    public $_ref_exercice_places;

    /** @var CHonoraryPlace[] */
    public $_ref_honorary_places;

    /** @var CInformationTarifPlace */
    public $_ref_information_tarif_place;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec                           = parent::getSpec();
        $spec->table                    = "medecin_exercice_place";
        $spec->key                      = "medecin_exercice_place_id";
        $spec->uniques['medecin_place'] = ['medecin_id', 'exercice_place_id'];
        $spec->loggable                 = CMbObjectSpec::LOGGABLE_HUMAN;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props['medecin_id']        = 'ref class|CMedecin notNull back|medecins fieldset|target';
        $props['exercice_place_id'] = 'ref class|CExercicePlace notNull back|exercice_places fieldset|target';
        $props['adeli']             = "code confidential mask|9*S*S99999S9 adeli fieldset|identifiers";
        $props['rpps_file_version'] = 'str loggable|0 fieldset|identifiers';

        return $props;
    }

    /**
     * @return CMedecin|CStoredObject
     * @throws Exception
     */
    public function loadRefMedecin(): CMedecin
    {
      return $this->_ref_medecin = $this->loadFwdRef("medecin_id", true);
    }

    /**
     * @return CExercicePlace|CStoredObject
     * @throws Exception
     */
    public function loadRefExercicePlace(): CExercicePlace
    {
      return $this->_ref_exercice_place = $this->loadFwdRef("exercice_place_id", true);
    }

    /**
     * Loads Honorary Place references.
     *
     * @return CHonoraryPlace[]|CStoredObject[]
     * @throws Exception
     */
    public function loadRefHonoraryPlaces(): array
    {
      return $this->_ref_honorary_places = $this->loadBackRefs('honorary_places');
    }

    /**
     * Loads Information Tarif Place reference.
     *
     * @return CInformationTarifPlace|CStoredObject
     * @throws Exception
     */
    public function loadRefInformationTarifPlace(): CInformationTarifPlace
    {
      return $this->_ref_information_tarif_place = $this->loadUniqueBackRef('information_tarif_place');
    }

    /**
     * Loads Exercice Place reference for API response.
     *
     * @return CItem|null
     * @throws Exception
     */
    public function getResourceExercicePlace(): ?CItem
    {
      $exercice_place = $this->loadRefExercicePlace();

      if (!$exercice_place || !$exercice_place->_id) {
        return null;
      }

      return new CItem($exercice_place);
    }

    /**
     * Loads Honorary Place references for API response.
     *
     * @return CCollection
     * @throws Exception
     */
    public function getResourceHonoraryPlace(): ?CCollection
    {
      $honorary_places = $this->loadRefHonoraryPlaces();

      if (!$honorary_places) {
        return null;
      }

      return new CCollection($honorary_places);
    }

    /**
     * @return CCollection|null
     * @throws Exception
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
     * Loads Honorary Place references for API response.
     *
     * @return CItem|null
     * @throws Exception
     */
    public function getResourceInformationTarifPlace(): ?CItem
    {
      $information_tarif_place = $this->loadRefInformationTarifPlace();

      if (!$information_tarif_place || !$information_tarif_place->_id) {
        return null;
      }

      return new CItem($information_tarif_place);
    }
}
