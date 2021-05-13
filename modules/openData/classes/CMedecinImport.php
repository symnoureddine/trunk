<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\OpenData;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CMbString;
use Ox\Core\FileUtil\CFormattedFileReader;
use Ox\Mediboard\Patients\CMedecin;

/**
 * Classe d'import pour l'annuaire des correspondants médicaux avec numéros RPPS
 */
class CMedecinImport implements IShortNameAutoloadable {
  public static $file_url = "https://service.annuaire.sante.fr/annuaire-sante-webservices/V300/services/extraction/PS_LibreAcces";

  CONST MEDECIN_FILE_NAME = "PS_LibreAcces_Personne_activite";

  static $medecin_additionnal_files = array(
    "PS_LibreAcces_Dipl_AutExerc",
    "PS_LibreAcces_SavoirFaire",
  );

  public static $titles = array(
    'TYPEID'              => "Type d'identifiant PP",                             // 0 = ADELI | 8 = RPPS
    'ID'                  => "Identifiant PP",                                    // ADELI OR RPPS
    'NATIONAL_ID'         => "Identification nationale PP",
    'CODE_CIV_EX'         => "Code civilité d'exercice",                          // Titre exercice (Pr | Dr | MC | MG | PC | PG)
    'CIV_EX'              => "Libellé civilité d'exercice",                       // Libellé titre exercice
    'CODE_CIV'            => "Code civilité",                                     // Titre (M | MME | MLLE)
    'CIV'                 => "Libellé civilité",                                  // Libellé titre
    'nom'                 => "Nom d'exercice",                                    // nom
    'prenom'              => "Prénom d'exercice",                                 // prenom
    'CODE_PRO'            => "Code profession",                                   // Type
    'PROFESSION'          => "Libellé profession",                                // Libellé type
    'CODE_CAT_PRO'        => "Code catégorie professionnelle",
    'LIB_CAT_PRO'         => "Libellé catégorie professionnelle",
    'CODE_TYPE_SAVOIR'    => "Code type savoir-faire",
    'LIB_TYPE_SAVOIR'     => "Libellé type savoir-faire",
    'CODE_DISCI'          => "Code savoir-faire",
    'disciplines'         => "Libellé savoir-faire",                              // Disciplines
    'CODE_EXERCICE'       => "Code mode exercice",
    'LIB_EXERCICE'        => "Libellé mode exercice",
    'SIRET'               => "Numéro SIRET site",
    'SIREN'               => "Numéro SIREN site",
    'FINESS'              => "Numéro FINESS site",
    'FINESS_JUR'          => "Numéro FINESS établissement juridique",
    'ID_TECH_STRUCT'      => "Identifiant technique de la structure",
    'RS'                  => "Raison sociale site",
    'ENSEIGNE_COM'        => "Enseigne commerciale site",
    'DEST_COMPL'          => "Complément destinataire (coord. structure)",
    'GEO_COMPL'           => "Complément point géographique (coord. structure)",
    'NUM_VOIE'            => "Numéro Voie (coord. structure)",                    // Adresse
    'REP_VOIE'            => "Indice répétition voie (coord. structure)",         // Adresse
    'CODE_TYPE_VOIE'      => "Code type de voie (coord. structure)",
    'LIB_TYPE_VOIE'       => "Libellé type de voie (coord. structure)",           // Adresse
    'LIB_VOIE'            => "Libellé Voie (coord. structure)",                   // Adresse
    'DISTRIB_MENTION'     => "Mention distribution (coord. structure)",
    'CEDEX'               => "Bureau cedex (coord. structure)",
    'cp'                  => "Code postal (coord. structure)",                    // cp
    'CODE_COMMUNE'        => "Code commune (coord. structure)",
    'ville'               => "Libellé commune (coord. structure)",                // Ville
    'CODE_PAYS'           => "Code pays (coord. structure)",
    'PAYS'                => "Libellé pays (coord. structure)",
    'tel'                 => "Téléphone (coord. structure)",                      // Tel
    'portable'            => "Téléphone 2 (coord. structure)",                    // Portable
    'fax'                 => "Télécopie (coord. structure)",                      // Fax
    'email'               => "Adresse e-mail (coord. structure)",                 // email
    'CODE_DEP_STRUCT'     => "Code département (structure)",
    'LIB_DEP_STRUCT'      => "Libellé département (structure)",
    'ANCIEN_ID_STRUCT'    => "Ancien identifiant de la structure",
    'AUTORITE_ENREG'      => "Autorité d'enregistrement",
    'CODE_SECTEUR_ACT'    => "Code secteur d'activité",
    'LIB_SECTEUR_ACT'     => "Libellé secteur d'activité",
    'CODE_SEC_TAB_PHARMA' => "Code section tableau pharmaciens",
    'LIB_SEC_TAB_PHARMA'  => "Libellé section tableau pharmaciens",
    'EMPTY'               => "\n", // Empty field at the end of the file
  );

  public static $phone_fields = array(
    'tel', 'fax', 'portable',
  );

  public static $corresp_civ = array(
    'DR'   => 'dr',
    'PR'   => 'pr',
    'M'    => 'm',
    'MME'  => 'mme',
    "MLLE" => 'mme',
  );

  public static $import_counts = array(
    100, 500, 1000,
  );

  public static $abreviations = array(
    'av'  => 'avenue',
    'pl'  => 'place',
    'st'  => 'saint',
    'bd'  => 'boulevard',
    'brg' => 'bourg',
    'all' => 'allée',
    'che' => 'chemin',
    'fg'  => 'faubourg',
    'lot' => 'lotissement',
    'pte' => 'porte',
    'rte' => 'route',
    ''    => ' ', // Remove spaces from a string for comparison
  );

  public $csv;
  protected $file_reader;

  /** @var CMedecin[] $new_medecins */
  public $new_medecins = array();

  public $conflicts = array();
  public $update_medecins = array();
  public $is_audit;
  public $make_updates;
  public $version;
  public $type;
  public $cps = array();
  public $cp_mandatory;

  public $nb_conflicts = 0;
  public $nb_ok_used = 0;
  public $nb_ok_unused = 0;
  public $nb_new = 0;
  public $nb_ignored = 0;
  public $nb_tel_error = 0;

  /**
   * CMedecinImport constructor.
   *
   * @param string $file_path Path to the file to import
   * @param int    $start     Offset to start import at
   * @param bool   $dry_run   Is the import an audit ?
   * @param bool   $update    Make update or just import news
   * @param string $version   Version of the file used
   * @param string $type      Type of ids to import : all, rpps or adeli
   */
  public function __construct(
      $file_path, $start = 0, $dry_run = true, $update = false, $version = null, $type = "all", $cps = array(), $cp_mandatory = false
  ) {
    $this->file_reader = new CMedecinFileReader($file_path, $start);
    $this->file_reader->setHeader(array_keys(self::$titles));

    $this->is_audit     = $dry_run;
    $this->make_updates = $update;
    $this->version      = $version;
    $this->type         = $type;
    $this->cps          = $cps;
    $this->cp_mandatory = $cp_mandatory;
  }

  /**
   * Parse the file and import CMedecin objects
   *
   * @param int $count Number of lines to parse
   *
   * @return bool
   */
  public function parseMedecinFile($count) {
    $line = null;
    for ($i = 0; $i < $count; $i++) {
      $line = $this->file_reader->readAndSanitizeLine();

      if (!$line) {
        continue;
      }

      if (($line['TYPEID'] === "0" && $this->type === "rpps") || ($line['TYPEID'] === "8" && $this->type === "adeli")) {
        $i--; // Decrease $i to keep the import count
        continue;
      }

      if (($this->cp_mandatory && !$line['cp']) || ($this->cps && $line['cp'] && !in_array(substr($line['cp'], 0, 2), $this->cps))) {
        continue;
      }

      $this->auditMedecin($line);
    }

    if (!$this->is_audit) {
      $this->importNewMedecins();
      if ($this->make_updates) {
        $this->updateMedecins();
      }
    }

    $end = $this->file_reader->getPos();
    $this->file_reader->close();

    return ($line == null) ? null : $end;
  }

  /**
   * @param array $infos A CSV line
   *
   * @return void
   */
  public function auditMedecin($infos) {
    $medecin = new CMedecin();

    // Check if a CMedecin exists with this rpps
    if ($infos['TYPEID'] == 8) {
      $medecin = $medecin->loadByRpps($infos['ID']);
    }
    elseif ($infos['TYPEID'] == 0) {
      $medecin = $medecin->loadByAdeli($infos['ID']);
    }

    // Check if a CMedecin exists with the same nom, prenom, type, cp
    if (!$medecin || !$medecin->_id) {
      $medecin = $medecin->loadMedecinList($infos['nom'], $infos['prenom'], $infos['CODE_PRO'], $infos['cp']);

      // Check if a CMedecin exists with the same nom, prenom, type, cp with 2 first numbers
      if (!$medecin || !$medecin->_id) {
        $medecin = $medecin->loadMedecinList($infos['nom'], $infos['prenom'], $infos['CODE_PRO'], substr($infos['cp'], 0, 2));

        // Create a new CMedecin (doesn't store it yet)
        if (!$medecin || !$medecin->_id) {
          $this->createNewMedecin($infos);
        }
      }
    }

    if ($this->make_updates && $medecin && $medecin->_id) {
      $this->updateMedecin($infos, $medecin);
    }
  }

  /**
   * Ceate a new CMedecin and put it in $this->new_medecins
   *
   * @param array $infos One line from the CSV
   *
   * @return void
   */
  public function createNewMedecin($infos) {
    $medecin = new CMedecin();
    $medecin->bind($infos);

    if ($infos['TYPEID'] == 8) {
      $medecin->rpps = $infos['ID'];
    }
    elseif ($infos['TYPEID'] == 0) {
      $medecin->adeli = $infos['ID'];
    }

    switch ($infos['CODE_CIV_EX']) {
      case 'DR':
        $medecin->titre = 'dr';
        break;
      case 'PR':
        $medecin->titre = 'pr';
        break;
      default:
        // Empty default
    }

    if (!$medecin->titre && $infos['CODE_CIV']) {
      switch ($infos['CODE_CIV']) {
        case "M":
          $medecin->titre = 'm';
          break;
        case "MLLE":
        case "MME":
          $medecin->titre = "mme";
          break;
        default:
          // Empty default
      }
    }

    $medecin->type                = CMedecin::$types[$infos['CODE_PRO']];
    $voie_rep                     = (trim($infos['REP_VOIE'])) ? trim($infos['REP_VOIE']) . ' ' : '';
    $num_voie                     = (trim($infos['NUM_VOIE'])) ? trim($infos['NUM_VOIE']) . ' ' : '';
    $adresse                      = $num_voie . $voie_rep . trim($infos['LIB_TYPE_VOIE']) . ' ' . trim($infos['LIB_VOIE']);
    $medecin->adresse             = trim($adresse);
    $medecin->import_file_version = '';
    $medecin->import_file_version = $this->version;

    $this->sanitizeTel($medecin);
    $medecin = $this->checkTel($medecin);

    $this->new_medecins[] = $medecin;

    if ($this->is_audit) {
      $this->nb_new++;
    }
  }

  /**
   * @param CMedecin $medecin Medecin to check
   *
   * @return CMedecin
   */
  function checkTel($medecin) {
    $errors = $medecin->repair();

    if (array_key_exists('tel', $errors) || array_key_exists('tel_autre', $errors) || array_key_exists('portable', $errors)) {
      $this->nb_tel_error++;
    }

    return $medecin;
  }

  /**
   * @param array    $infos   One line of the CSV
   * @param CMedecin $medecin The CMedecin found
   *
   * @return void
   */
  public function updateMedecin($infos, $medecin) {
    if ($this->is_audit) {
      $this->checkUsed($medecin);
    }

    if ($medecin->ignore_import_rpps) {
      $this->nb_ignored++;

      return;
    }

    $this->sanitizeTel(null, $infos);

    $diffs = $this->checkDiffs($infos, $medecin);
    $this->checkConflict($diffs, $medecin->_id);
  }

  /**
   * @param CMedecin $medecin Medecin to check
   *
   * @return void
   */
  function checkUsed($medecin) {
    $medecin->countPatients();
    if ($medecin->_count_patients_correspondants || $medecin->_count_patients_traites) {
      $this->nb_ok_used++;
    }
    else {
      $this->nb_ok_unused++;
    }
  }

  /**
   * Build an array with the diffs for a CSV line and a CMedecin
   *
   * @param array    $infos   Values to import
   * @param CMedecin $medecin CMedecin in Mediboard
   *
   * @return array
   */
  public function checkDiffs($infos, $medecin) {
    $voie_rep    = ($infos['REP_VOIE']) ? $infos['REP_VOIE'] . ' ' : '';
    $num_voie    = ($infos['NUM_VOIE']) ? $infos['NUM_VOIE'] . ' ' : '';
    $new_address = trim($num_voie . $voie_rep . $infos['LIB_TYPE_VOIE'] . ' ' . $infos['LIB_VOIE']);

    $diffs = array(
      'old' => array(),
      'new' => array(),
    );

    if ($infos['TYPEID'] == 8 && $infos['ID'] != $medecin->rpps) {
      $diffs['old']['rpps'] = $medecin->rpps;
      $diffs['new']['rpps'] = $infos['ID'];
    }
    elseif ($infos['TYPEID'] == 0 && $infos['ID'] != $medecin->adeli) {
      $diffs['old']['adeli'] = $medecin->adeli;
      $diffs['new']['adeli'] = $infos['ID'];
    }

    $civ_field = (isset($infos['CODE_CIV_EX']) && $infos['CODE_CIV_EX']) ? $infos['CODE_CIV_EX'] : $infos['CODE_CIV'];
    if ((isset(self::$corresp_civ[$civ_field]) && $medecin->titre != self::$corresp_civ[$civ_field])
        || (!isset(self::$corresp_civ[$civ_field]) && $medecin->titre)
    ) {
      $diffs['old']['titre'] = $medecin->titre;
      $diffs['new']['titre'] = (isset(self::$corresp_civ[$civ_field])) ? self::$corresp_civ[$civ_field] : '';
    }

    if (CMbString::lower($infos['nom']) != CMbString::lower($medecin->nom)) {
      $diffs['old']['nom'] = $medecin->nom;
      $diffs['new']['nom'] = $infos['nom'];
    }

    if (CMbString::lower($infos['prenom']) != CMbString::lower($medecin->prenom)) {
      $diffs['old']['prenom'] = $medecin->prenom;
      $diffs['new']['prenom'] = $infos['prenom'];
    }

    if (CMedecin::$types[$infos['CODE_PRO']] != $medecin->type) {
      $diffs['old']['type'] = $medecin->type;
      $diffs['new']['type'] = CMedecin::$types[$infos['CODE_PRO']];
    }

    if (CMbString::lower($infos['disciplines']) != CMbString::lower($medecin->disciplines)) {
      $diffs['old']['disciplines'] = $medecin->disciplines;
      $diffs['new']['disciplines'] = $infos['disciplines'];
    }

    if (CMbString::lower($new_address) != CMbString::lower($medecin->adresse)) {
      $diffs['old']['adresse'] = $medecin->adresse;
      $diffs['new']['adresse'] = $new_address;
    }

    if ($infos['cp'] != $medecin->cp) {
      $diffs['old']['cp'] = $medecin->cp;
      $diffs['new']['cp'] = $infos['cp'];
    }

    if (CMbString::lower($infos['ville']) != CMbString::lower($medecin->ville)) {
      $diffs['old']['ville'] = $medecin->ville;
      $diffs['new']['ville'] = $infos['ville'];
    }

    if ($infos['tel'] != $medecin->tel) {
      $diffs['old']['tel'] = $medecin->tel;
      $diffs['new']['tel'] = $infos['tel'];
    }

    if ($infos['portable'] != $medecin->portable) {
      $diffs['old']['portable'] = $medecin->portable;
      $diffs['new']['portable'] = $infos['portable'];
    }

    if ($infos['fax'] != $medecin->fax) {
      $diffs['old']['fax'] = $medecin->fax;
      $diffs['new']['fax'] = $infos['fax'];
    }

    if ($infos['email'] != $medecin->email) {
      $diffs['old']['email'] = $medecin->email;
      $diffs['new']['email'] = $infos['email'];
    }

    //if ($infos['mssante_address'] != $medecin->mssante_address) {
    //  $diffs['old']['mssante_address'] = $medecin->mssante_address;
    //  $diffs['new']['mssante_address'] = $infos['mssante_address'];
    //}

    return $this->update_medecins[$medecin->_id] = $diffs;
  }

  /**
   * Check if the diffs are conflicts or not
   *
   * @param array $diffs      The difference between the 2 CMedecins
   * @param int   $medecin_id The ID of the old CMedecin
   *
   * @return void
   */
  public function checkConflict($diffs, $medecin_id) {
    $all_ok = true;
    foreach ($diffs['old'] as $_field => $_value) {
      // Empty field get a value, no conflict
      if ($_value == '' && $diffs['new'][$_field] != '') {
        continue;
      }

      // Mediboard field have a value and imported field don't, no conflict
      if ($_value != '' && $diffs['new'][$_field] == '') {
        continue;
      }

      // Fields don't have the same value, conflict
      if (!CMbString::compareAdresses($_value, $diffs['new'][$_field], self::$abreviations)) {
        $all_ok = false;
        $this->storeConflict($medecin_id, $_field, $diffs['new'][$_field], $this->is_audit);
      }
    }

    if (!$all_ok) {
      $this->nb_conflicts++;
      $this->conflicts[$medecin_id] = true;
    }
  }

  /**
   * Import the new medecins
   *
   * @return void
   */
  public function importNewMedecins() {
    foreach ($this->new_medecins as $_med) {
      $_med->enableImporting();

      if ($msg = $_med->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        $this->nb_new++;
        CAppUI::setMsg("CMedecin-msg-create", UI_MSG_OK);
      }
    }
  }

  /**
   * Met à jour lles médecins
   *
   * @return void
   */
  public function updateMedecins() {
    foreach ($this->update_medecins as $_id => $_diff) {
      $medecin = new CMedecin();
      $medecin->load($_id);

      $medecin->actif = 1;

      if (isset($_diff['new']['rpps']) && !$medecin->rpps) {
        $medecin->rpps = $_diff['new']['rpps'];
      }

      if (isset($_diff['new']['adeli']) && !$medecin->adeli) {
        $medecin->adeli = $_diff['new']['adeli'];
      }

      // Update the blank fields for the CMedecin
      foreach ($_diff['new'] as $_field => $_value) {
        if (!$medecin->$_field) {
          $medecin->$_field = $_value;
        }
      }

      if (isset($this->conflicts[$_id])) {
        if ($msg = $medecin->store()) {
          CAppUI::setMsg($msg, UI_MSG_WARNING);
        }

        // If conflicts exists do not update fields other than blank fields
        continue;
      }

      if (!$medecin || !$medecin->_id) {
        CAppUI::setMsg('CMedecinImport-update-error', UI_MSG_WARNING);

        return;
      }

      foreach ($_diff['new'] as $_field => $_value) {
        if ($_value) {
          $medecin->$_field = $_value;
        }
      }

      $medecin->import_file_version = $this->version;

      $medecin = $this->checkTel($medecin);

      if ($msg = $medecin->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
        continue;
      }

      $this->checkUsed($medecin);

      CAppUI::setMsg('CMedecin-msg-modify', UI_MSG_OK);
    }
  }

  /**
   * Store a conflict
   *
   * @param int    $medecin_id CMedecin id we want to store a conflict for
   * @param string $field      Name of the modified field
   * @param string $value      New value of the field
   * @param bool   $audit      Running an audit or not
   *
   * @return void
   */
  public function storeConflict($medecin_id, $field, $value, $audit) {
    $conflict               = new CImportConflict();
    $conflict->object_id    = $medecin_id;
    $conflict->object_class = 'CMedecin';
    $conflict->import_tag   = 'import_rpps';
    $conflict->field        = $field;
    $conflict->audit        = ($audit) ? '1' : '0';
    $conflict->value        = $value;

    $conflict->loadMatchingObjectEsc();

    $conflict->file_version = $this->version;

    if ($conflict && !$conflict->_id) {
      if ($msg = $conflict->store()) {
        CAppUI::stepAjax($msg, UI_MSG_WARNING);

        return;
      }
    }
  }

  /**
   * Put the stats in SHM
   *
   * @param float $duration Duration of the import
   *
   * @return void
   */
  public function setStatsInSHM($duration) {
    $stats             = $this->getStats();
    $stats['duration'] = $duration;

    $cache     = new Cache('CMedecinImport', 'stats', Cache::OUTER | Cache::DISTR);
    $shm_stats = $cache->get();

    if ($shm_stats) {
      foreach ($shm_stats as $_stat => $_value) {
        $stats[$_stat] += $_value;
      }
    }

    $cache->put($stats);
  }

  /**
   * @param array $titles The titles of the file
   *
   * @return array
   */
  public static function getTitlesErrors($titles) {
    $errors = array();
    if (count($titles) != count(self::$titles)) {
      CAppUI::stepAjax("CMedecinImport-titles-bad-number", UI_MSG_WARNING);
    }

    $i = 0;
    foreach (self::$titles as $_title) {
      $real_title = utf8_decode($titles[$i]);
      if (CMbString::lower($_title) != CMbString::lower($real_title)) {
        $errors[$_title] = $real_title;
      }
      $i++;
    }

    return $errors;
  }

  /**
   * @return array
   */
  public function getStats() {
    return array(
      'nb_news'            => $this->nb_new,
      'nb_exists'          => $this->nb_ok_used + $this->nb_ok_unused,
      'nb_exists_conflict' => $this->nb_conflicts,
      'nb_exists_used'     => $this->nb_ok_used,
      'nb_exists_unused'   => $this->nb_ok_unused,
      'nb_rpps_ignored'    => $this->nb_ignored,
      'nb_tel_error'       => $this->nb_tel_error,
      'duration'           => '',
    );
  }

  /**
   * Sanitize the phones from a CMedecin
   *
   * @param CMedecin $medecin Remove non numeric chars from the phones fields
   * @param array    $line    Line to sanitize
   *
   * @return void
   */
  protected function sanitizeTel($medecin = null, &$line = array()) {
    foreach (self::$phone_fields as $_field) {
      if ($medecin && $medecin instanceof CMedecin) {
        if (isset($medecin->$_field)) {
          $old_field        = $medecin->$_field;
          $medecin->$_field = preg_replace('/[^0-9]/', '', $medecin->$_field);
          if ($medecin->$_field != $old_field) {
            $this->nb_tel_error++;
          }
        }
      }

      if ($line && is_array($line)) {
        if (isset($line[$_field])) {
          $old_field     = $line[$_field];
          $line[$_field] = preg_replace('/[^0-9]/', '', $line[$_field]);
          if ($line[$_field] != $old_field) {
            $this->nb_tel_error++;
          }
        }
      }
    }
  }

  /**
   * @return int
   */
  public static function getStartOffset() {
    $cache = new Cache('CMedecinImport', 'import_offset', Cache::OUTER | Cache::DISTR);

    return $cache->get();
  }

  /**
   * @param int $last_offset Offset to start at
   *
   * @return void
   */
  public static function setStartOffset($last_offset) {
    $cache = new Cache('CMedecinImport', 'import_offset', Cache::OUTER | Cache::DISTR);
    $cache->put($last_offset);
  }
}
