<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps;

use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CPerson;
use Ox\Core\CView;
use Ox\Core\Mutex\CMbMutex;
use Ox\Import\Rpps\Entity\CAbstractExternalRppsObject;
use Ox\Import\Rpps\Entity\CDiplomeAutorisationExercice;
use Ox\Import\Rpps\Entity\CPersonneExercice;
use Ox\Import\Rpps\Entity\CSavoirFaire;
use Ox\Import\Rpps\Exception\CImportMedecinException;
use Ox\Mediboard\Patients\CExercicePlace;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CMedecinExercicePlace;

/**
 * Description
 */
class CExternalMedecinSync
{
    public const MUTEX_TIMEOUT = 300;

    private static $imported_places = [];

    /** @var int */
    private $step;

    /** @var array */
    private $rpps = [];

    /** @var array */
    private $adelis = [];

    /** @var array */
    private $errors = [];

    /** @var array */
    private $updated = [];

    /**
     * @param int $step
     *
     * @return void
     * @throws Exception
     */
    public function synchronizeSomeMedecins(int $step = 50): void
    {
        if (!$this->putMutex()) {
        // Mutex already used
        throw new CImportMedecinException('Mutex is already in use');
        }

        $this->step = $step;

        $ext_medecins = $this->getRandomPersonsToSync();

        // Enforce slave before lauching data from std
        CView::enforceSlave();

        $this->buildExternalDatas($ext_medecins);

        // Disable slave before synchronisation
        CView::disableSlave();

        $this->synchronizeMedecins();
        $this->releaseMutex();
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getRandomPersonsToSync(): array
    {
        $person = new CPersonneExercice();

        $limit = $this->getLimit($person);

        return $person->loadList(['synchronized' => "= '0'"], null, $limit);
    }

    /**
     * @param array $ext_medecins
     *
     * @return void
     * @throws Exception
     */
    private function buildExternalDatas(array $ext_medecins): void
    {
        $this->extractIdsFromMedecins($ext_medecins);

        $external_ids = CMbArray::pluck($ext_medecins, 'identifiant_national');
        $this->addSavoirFaire($external_ids);
        $this->addDiplomeAutorisationExercice($external_ids);
        $this->addMedecins();
        $this->addExercicePlaces();
    }

    /**
     * @param array $ext_medecins
     *
     * @return void
     */
    private function extractIdsFromMedecins(array $ext_medecins): void
    {
        // TODO Handle multiple times the same ID

        /** @var CPersonneExercice $_med */
        foreach ($ext_medecins as $_med) {
            switch ($_med->type_identifiant) {
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_ADELI:
                    $this->adelis[$_med->identifiant] = [
                        CPersonneExercice::class            => $_med,
                        CSavoirFaire::class                 => null,
                        CDiplomeAutorisationExercice::class => null,
                        CMedecin::class                     => null,
                        CExercicePlace::class               => null,
                    ];
                    break;
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_RPPS:
                    $this->rpps[$_med->identifiant] = [
                        CPersonneExercice::class            => $_med,
                        CSavoirFaire::class                 => null,
                        CDiplomeAutorisationExercice::class => null,
                        CMedecin::class                     => null,
                        CExercicePlace::class               => null,
                    ];
                    break;
                default:
                    // Do nothing
            }
        }
    }

    /**
     * @param array $external_ids
     *
     * @return void
     * @throws Exception
     */
    private function addSavoirFaire(array $external_ids): void
    {
        $savoir_faires = $this->loadSavoirFaire($external_ids);

        foreach ($savoir_faires as $_savoir_faire) {
            switch ($_savoir_faire->type_identifiant) {
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_RPPS:
                    $this->rpps[$_savoir_faire->identifiant][CSavoirFaire::class] = $_savoir_faire;
                    break;
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_ADELI:
                    // No adeli for ext_tables
                default:
                    // Do nothing
            }
        }
    }

    /**
     * @param array $external_ids
     *
     * @return void
     * @throws Exception
     */
    private function addDiplomeAutorisationExercice(array $external_ids): void
    {
        $diplomes = $this->loadDiplomeAutorisationExercice($external_ids);

        foreach ($diplomes as $_diplome) {
            switch ($_diplome->type_identifiant) {
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_RPPS:
                    $this->rpps[$_diplome->identifiant][CDiplomeAutorisationExercice::class] = $_diplome;
                    break;
                case CAbstractExternalRppsObject::TYPE_IDENTIFIANT_ADELI:
                    // No adeli for ext_tables
                default:
                    // Do nothing
            }
        }
    }

    /**
     * @param array $external_ids
     *
     * @return array
     * @throws Exception
     */
    private function loadDiplomeAutorisationExercice(array $external_ids): array
    {
        $diplome = new CDiplomeAutorisationExercice();
        $ds      = $diplome->getDS();

        return $diplome->loadList(['identifiant_national' => $ds->prepareIn($external_ids)]);
    }

    /**
     * @param array $external_ids
     *
     * @return array
     * @throws Exception
     */
    private function loadSavoirFaire(array $external_ids): array
    {
        $savoir_faire = new CSavoirFaire();
        $ds           = $savoir_faire->getDS();

        return $savoir_faire->loadList(['identifiant_national' => $ds->prepareIn($external_ids)]);
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    private function addMedecins(): void
    {
        $this->addMedecinsAdeli();
        $this->addMedecinsRpps();
    }

    private function addExercicePlaces(): void
    {
        // Extract siret, siren and id_techniques
        $sirets         = $this->extractFieldFromPersonneExercice('siret_site');
        $sirens         = $this->extractFieldFromPersonneExercice('siren_site');
        $ids_techniques = $this->extractFieldFromPersonneExercice('id_technique_structure');

        // md5 each unique value with prefix
        $hash_sirets         = $this->hashCodes($sirets, CExercicePlace::PREFIX_TYPE_SIRET);
        $hash_sirens         = $this->hashCodes($sirens, CExercicePlace::PREFIX_TYPE_SIREN);
        $hash_ids_techniques = $this->hashCodes($ids_techniques, CExercicePlace::PREFIX_TYPE_ID_TECHNIQUE);

        // Load exercice places using identifier
        $exercice_places = CExercicePlace::loadFromIdentifier(
            array_merge($hash_sirets, $hash_sirens, $hash_ids_techniques)
        );

        // Assign exercice place on lines depending on identifier
        $this->assignExercicePlaces($this->rpps, $exercice_places, $hash_sirets, 'siret_site');
        $this->assignExercicePlaces($this->rpps, $exercice_places, $hash_sirens, 'siren_site');
        $this->assignExercicePlaces($this->rpps, $exercice_places, $hash_ids_techniques, 'id_technique_structure');
        $this->assignExercicePlaces($this->adelis, $exercice_places, $hash_sirets, 'siret_site');
        $this->assignExercicePlaces($this->adelis, $exercice_places, $hash_sirens, 'siren_site');
        $this->assignExercicePlaces($this->adelis, $exercice_places, $hash_ids_techniques, 'id_technique_structure');
    }

    private function assignExercicePlaces(array &$ext_med, array $places, array $hash, string $type): void
    {
        foreach ($ext_med as &$_med) {
            if (
                ($code = $_med[CPersonneExercice::class]->{$type})
                && isset($hash[$code])
                && isset($places[$hash[$code]])
            ) {
                $_med[CExercicePlace::class] = $places[$hash[$code]];
            }
        }
    }

    private function hashCodes(array $codes, string $prefix): array
    {
        $hashes = [];
        foreach ($codes as $_code) {
            if (!isset($hashes[$_code])) {
                $hashes[$_code] = md5($prefix . $_code);
            }
        }

        return $hashes;
    }

    private function extractFieldFromPersonneExercice(string $field): array
    {
        return array_filter(
            array_merge(
                CMbArray::pluck($this->rpps, CPersonneExercice::class, $field),
                CMbArray::pluck($this->adelis, CPersonneExercice::class, $field)
            )
        );
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    private function addMedecinsAdeli(): void
    {
        $medecins = $this->loadMedecins(
            'medecin_exercice_place.adeli',
            array_keys($this->adelis),
            ['medecin_exercice_place' => 'medecin.medecin_id = medecin_exercice_place.medecin_id'],
            ['medecin.medecin_id']
        );

        foreach ($medecins as $_medecin) {
            $this->adelis[$_medecin->adeli][CMedecin::class] = $_medecin;
        }
    }

    /**
     * @param string $field
     * @param array  $ids
     *
     * @return array
     * @throws Exception
     */
    private function loadMedecins(string $field, array $ids, array $ljoin = [], array $group = []): array
    {
        $medecin = new CMedecin();
        $ds      = $medecin->getDS();

        $where = [$field => $ds->prepareIn($ids)];

        return $medecin->loadList($where, null, null, $group, $ljoin);
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    private function addMedecinsRpps(): void
    {
        $medecins = $this->loadMedecins('rpps', array_keys($this->rpps));

        foreach ($medecins as $_medecin) {
            $this->rpps[$_medecin->rpps][CMedecin::class] = $_medecin;
        }
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    private function synchronizeMedecins(): void
    {
        $all_medecins = array_merge($this->rpps, $this->adelis);

        $this->synchronizePlaces($all_medecins);

        foreach ($all_medecins as $_medecins) {
            /** @var CMedecin $mb_medecin */
            $mb_medecin = $_medecins[CMedecin::class];

            /** @var CPersonneExercice $personne_exercice */
            $personne_exercice = $_medecins[CPersonneExercice::class];

            if (!$personne_exercice) {
                continue;
            }

            $mb_medecin = $personne_exercice->synchronize($mb_medecin);

            /** @var CSavoirFaire $savoir_faire */
            if ($savoir_faire = $_medecins[CSavoirFaire::class]) {
                $savoir_faire->synchronize($mb_medecin);
            }

            /** @var CDiplomeAutorisationExercice $diplome */
            if ($diplome = $_medecins[CDiplomeAutorisationExercice::class]) {
                $diplome->synchronize($mb_medecin);
            }

            $this->setVersion($mb_medecin, $personne_exercice->version);

            if ($msg = $mb_medecin->store()) {
                $this->errors[] = $msg;
                continue;
            }

            $this->updated[] = $mb_medecin->_id;

            $this->setSynchronized($personne_exercice);

            if ($savoir_faire) {
                $this->setSynchronized($savoir_faire);
            }

            if ($diplome) {
                $this->setSynchronized($diplome);
            }

            $place = $personne_exercice->synchronizeExercicePlace($mb_medecin, $_medecins[CExercicePlace::class]);
            if ($place && !($place instanceof CMedecinExercicePlace)) {
                $this->errors[] = $place;
            }
        }
    }

    private function synchronizePlaces(array &$lines): void
    {
        foreach ($lines as &$_line) {
            /** @var CExercicePlace $place */
            $place = $_line[CExercicePlace::class];
            /** @var CPersonneExercice $personne_exercice */
            $personne_exercice = $_line[CPersonneExercice::class];

            if (!$personne_exercice->raison_sociale_site) {
                continue;
            }

            if (!$place) {
                $identifier = $personne_exercice->hashIdentifier();
                if (isset(self::$imported_places[$identifier])) {
                    $place = self::$imported_places[$identifier];
                } else {
                    $place                            = new CExercicePlace();
                    $place->exercice_place_identifier = $identifier;
                }

            }

            if ($place->rpps_file_version === $personne_exercice->version) {
                $_line[CExercicePlace::class] = $place;
                continue;
            }

            $msg = $personne_exercice->updateOrCreatePlace($place);
            if (is_string($msg)) {
                $this->errors[] = $msg;
                continue;
            }

            $_line[CExercicePlace::class] = $place;
            self::$imported_places[$place->exercice_place_identifier] = $place;
        }
    }

    /**
     * @param CAbstractExternalRppsObject $object
     *
     * @return void
     * @throws Exception
     *
     */
    private function setSynchronized(CAbstractExternalRppsObject $object): void
    {
        $object->synchronized = '1';
        $object->store();
    }

    /**
     * @param CMedecin $medecin
     * @param string   $version
     *
     * @return void
     */
    private function setVersion(CMedecin $medecin, string $version): void
    {
        $medecin->import_file_version = $version;
    }

    /**
     * @param CAbstractExternalRppsObject $object
     *
     * @return string
     * @throws Exception
     */
    private function getLimit(CAbstractExternalRppsObject $object): string
    {
        $total = $object->getTotalLines();

        return rand(0, ($total < $this->step) ? 0 : ($total - $this->step)) . ",{$this->step}";
    }

    /**
     * @return bool
     */
    private function putMutex(): bool
    {
        $mutex = $this->getMutex();

        return $mutex->lock(self::MUTEX_TIMEOUT);
    }

    /**
     * @return CMbMutex
     */
    private function getMutex(): CMbMutex
    {
        return new CMbMutex(__CLASS__, __METHOD__);
    }

    /**
     * @return void
     */
    private function releaseMutex(): void
    {
        $mutex = $this->getMutex();
        $mutex->release();
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * @param bool $sync
     *
     * @return array
     * @throws Exception
     */
    public function getCounts(bool $sync): array
    {
        $person_exercice               = new CPersonneExercice();
        $person_exercice->synchronized = ($sync) ? '1' : '0';

        $savoir_faire               = new CSavoirFaire();
        $savoir_faire->synchronized = ($sync) ? '1' : '0';

        $diplome               = new CDiplomeAutorisationExercice();
        $diplome->synchronized = ($sync) ? '1' : '0';

        return [
            CPersonneExercice::class            => $person_exercice->countMatchingList(),
            CSavoirFaire::class                 => $savoir_faire->countMatchingList(),
            CDiplomeAutorisationExercice::class => $diplome->countMatchingList(),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAvancement(): array
    {
        $not_sync = $this->getCounts(false);
        $sync     = $this->getCounts(true);

        return [
            CPersonneExercice::class            => $this->buildAvancement(
                $sync[CPersonneExercice::class],
                $not_sync[CPersonneExercice::class]
            ),
            CSavoirFaire::class                 => $this->buildAvancement(
                $sync[CSavoirFaire::class],
                $not_sync[CSavoirFaire::class]
            ),
            CDiplomeAutorisationExercice::class => $this->buildAvancement(
                $sync[CDiplomeAutorisationExercice::class],
                $not_sync[CDiplomeAutorisationExercice::class]
            ),
        ];
    }

    /**
     * @param int $sync_num
     * @param int $not_sync_num
     *
     * @return array
     */
    private function buildAvancement(int $sync_num, int $not_sync_num): array
    {
        $total = $sync_num + $not_sync_num;
        $pct   = ($total > 0) ? (($sync_num / $total) * 100) : 0;

        return [
            'sync'      => number_format($sync_num, 0, ',', ' '),
            'not_sync'  => number_format($not_sync_num, 0, ',', ' '),
            'total'     => number_format($total, 0, ',', ' '),
            'pct'       => number_format($pct, 4, ',', ' '),
            'threshold' => ($pct < 50) ? 'critical' : (($pct < 80) ? 'warning' : 'ok'),
            'width'     => number_format($pct),
        ];
    }
}
