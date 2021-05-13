<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;

/**
 * Class CCodeCCAM
 * Table p_acte
 *
 * Informations sur l'acte CCAM
 * Niveau acte
 */
class CCodeCCAM extends CCCAM {
  /** @var string  */
  const RESOURCE_NAME = 'codeCcam';

  static $cache_layers = Cache::INNER_OUTER;

  // Infos sur le code
  public $code;
  public $libelle_court;
  public $libelle_long;
  public $type_acte;
  public $_type_acte;
  public $sexe_comp;
  public $place_arbo;
  public $date_creation;
  public $date_fin;
  public $frais_dep;

  /** @var string[] Nature d'assurance permises */
  public $assurance;

  /** @var string Classification du code dans l'arborescence */
  public $arborescence;

  /** @var string Forfait spécifique permis par le code (table forfaits) */
  public $_forfait;

  /** @var CInfoTarifCCAM[] Infos historisées sur le code*/
  public $_ref_infotarif;

  /** @var CProcedureCCAM[] Procédures historisées */
  public $_ref_procedures;

  /** @var CNoteCCAM[] Notes */
  public $_ref_notes;

  /** @var CIncompatibiliteCCAM[] Incompatibilités médicales */
  public $_ref_incompatibilites;

  /** @var CActiviteCCAM[] Activités */
  public $_ref_activites;

  /** @var CExtensionPMSI[] */
  public $_ref_extensions;

  // Elements de référence pour la récupération d'informations
  public $_activite;
  public $_phase;

  /**
   * Constructeur à partir du code CCAM
   *
   * @param string $code Le code CCAM
   */
  function __construct($code = null) {
    if (strlen($code) > 7) {
      // Le code $code n'est pas formaté correctement
      if (!preg_match('/^[A-Z]{4}\d{3}(\d(-\d)?)?$/i', $code)) {
        return;
      }

      // Cas ou l'activite et la phase sont indiquées dans le code (ex: BFGA004-1-0)
      $detailCode = explode("-", $code);
      $this->code = strtoupper($detailCode[0]);
      $this->_activite = $detailCode[1];
      if (count($detailCode) > 2) {
        $this->_phase = $detailCode[2];
      }
    }
    else {
      $this->code = strtoupper($code);
    }
  }

  /**
   * @param string $code The code
   *
   * @return CCodeCCAM
   */
  static function get($code) {
    $cache = new Cache(__METHOD__, func_get_args(), self::$cache_layers);
    if ($cache->exists()) {
      return $cache->get();
    }

    // Chargement
    $code_ccam = new CCodeCCAM($code);
    $code_ccam->load();

    return $cache->put($code_ccam, true);
  }

  /**
   * Chargement des informations liées à l'acte
   * Table p_acte
   *
   * @return bool Existence ou pas du code CCAM
   */
  function load() {
    $ds = self::getSpec()->ds;

    $query = "SELECT p_acte.*
      FROM p_acte
      WHERE p_acte.CODE = ?";
    $query = $ds->prepare($query, $this->code);
    $result = $ds->exec($query);
    if ($ds->numRows($result) == 0) {
      $this->code = "-";
      return false;
    }

    $row = $ds->fetchArray($result);
    $this->libelle_court  = $row["LIBELLECOURT"];
    $this->libelle_long   = $row["LIBELLELONG"];
    $this->type_acte      = $row["TYPE"];
    $this->sexe_comp      = $row["SEXE"];
    $this->place_arbo     = $row["PLACEARBORESCENCE"];
    $this->date_creation  = $row["DATECREATION"];
    $this->date_fin       = $row["DATEFIN"];
    $this->frais_dep      = $row["DEPLACEMENT"];

    $this->assurance = array();
    $this->assurance[1]["db"]  = $row["ASSURANCE1"];
    $this->assurance[2]["db"]  = $row["ASSURANCE2"];
    $this->assurance[3]["db"]  = $row["ASSURANCE3"];
    $this->assurance[4]["db"]  = $row["ASSURANCE4"];
    $this->assurance[5]["db"]  = $row["ASSURANCE5"];
    $this->assurance[6]["db"]  = $row["ASSURANCE6"];
    $this->assurance[7]["db"]  = $row["ASSURANCE7"];
    $this->assurance[8]["db"]  = $row["ASSURANCE8"];
    $this->assurance[9]["db"]  = $row["ASSURANCE9"];
    $this->assurance[10]["db"] = $row["ASSURANCE10"];

    $this->arborescence = array();
    $this->arborescence[1]["db"]  = $row["ARBORESCENCE1"];
    $this->arborescence[2]["db"]  = $row["ARBORESCENCE2"];
    $this->arborescence[3]["db"]  = $row["ARBORESCENCE3"];
    $this->arborescence[4]["db"]  = $row["ARBORESCENCE4"];
    $this->arborescence[5]["db"]  = $row["ARBORESCENCE5"];
    $this->arborescence[6]["db"]  = $row["ARBORESCENCE6"];
    $this->arborescence[7]["db"]  = $row["ARBORESCENCE7"];
    $this->arborescence[8]["db"]  = $row["ARBORESCENCE8"];
    $this->arborescence[9]["db"]  = $row["ARBORESCENCE9"];
    $this->arborescence[10]["db"] = $row["ARBORESCENCE10"];

    $this->loadTypeLibelle();
    $this->getForfaitSpec();
    $this->loadRefProcedures();
    $this->loadRefNotes();
    $this->loadRefIncompatibilites();

    $this->loadArborescence();
    $this->loadAssurance();
    $this->loadRefInfoTarif();
    $this->loadExtensionsPMSI();

    foreach ($this->_ref_infotarif as $_info_tarif) {
      $_info_tarif->loadLibelleExo();
      $_info_tarif->loadLibellePresc();
      $_info_tarif->loadLibelleForfait();
    }
    $this->loadRefActivites();
    foreach ($this->_ref_activites as $_activite) {
      $_activite->loadLibelle();
      // Ne pas charger les associations possibles des codes complémentaires (des milliers)
      $_activite->_ref_associations = array();
      if ($this->type_acte != 2) {
        $_activite->loadRefAssociations();
      }

      $_activite->loadRefModificateurs();
      foreach ($_activite->_ref_modificateurs as $_date_modif) {
        foreach ($_date_modif as $_modif) {
          $_modif->loadLibelle();
        }
      }
      $_activite->loadRefClassif();
      foreach ($_activite->_ref_classif as $_classif) {
        $_classif->loadCatMed();
        $_classif->loadRegroupement();
      }
      $_activite->loadRefPhases();
      foreach ($_activite->_ref_phases as $_phase) {
        $_phase->loadRefInfo();
        $_phase->loadRefDentsIncomp();
        foreach ($_phase->_ref_dents_incomp as $_dent) {
          $_dent->loadRefDent();
          $_dent->_ref_dent->loadLibelle();
        }
      }
    }

    return true;
  }

  /**
   * Chargement des informations historisées de l'acte
   * Table p_acte_infotarif
   *
   * @return CInfoTarifCCAM[] La liste des informations historisées
   */
  function loadRefInfoTarif() {
    return $this->_ref_infotarif = CInfoTarifCCAM::loadListFromCode($this->code);
  }

  /**
   * Chargement des procédures de l'acte
   * Table p_acte_procedure
   *
   * @return CProcedureCCAM[] La liste des procédures
   */
  function loadRefProcedures() {
    return $this->_ref_procedures = CProcedureCCAM::loadListFromCode($this->code);
  }

  /**
   * Chargement des notes de l'acte
   * Table p_acte_notes
   *
   * @return CNoteCCAM[] La liste des notes
   */
  function loadRefNotes() {
    return $this->_ref_notes = CNoteCCAM::loadListFromCode($this->code);
  }

  /**
   * Chargement des incompatibilités de l'acte
   * Table p_acte_incompatibilite
   *
   * @return CIncompatibiliteCCAM[] La liste des incompatibilités
   */
  function loadRefIncompatibilites() {
    return $this->_ref_incompatibilites = CIncompatibiliteCCAM::loadListFromCode($this->code);
  }

  /**
   * Chargement des activités de l'acte
   * Table p_activite
   *
   * @return CActiviteCCAM[] La liste des activités
   */
  function loadRefActivites() {
    $exclude = array();
    if ($this->arborescence[1]["db"] === "000018" && $this->arborescence[2]["db"] === "000001") {
      $exclude[] = "'1'";
    }
    return $this->_ref_activites = CActiviteCCAM::loadListFromCode($this->code, $exclude);
  }

  /**
   * Chargement du libellé du type
   * Table c_typeacte
   *
   * @return string Libellé du type
   */
  function loadTypeLibelle() {
    $types = self::getListeTypesActe();

    if (array_key_exists($this->type_acte, $types)) {
      $this->_type_acte = $types[$this->type_acte];
    }

    return $this->_type_acte;
  }

  /**
   * Récupération du type de forfait de l'acte
   * (forfait spéciaux des listes SEH)
   * Table forfaits
   *
   * @return void
   */
  function getForfaitSpec() {
    $forfaits = self::getListeForfaits();

    if (array_key_exists($this->code, $forfaits)) {
      $this->_forfait = $forfaits[$this->code];
    }
  }

  /**
   * Chargement des libellés des assurances
   * Table c_natureassurance
   *
   * @return array Liste des assurances
   */
  function loadAssurance() {
    $nat_assurances = self::getListeAssurances();

    $ds = self::getSpec()->ds;
    foreach ($this->assurance as &$assurance) {
      if (!$assurance["db"]) {
        continue;
      }

      if (array_key_exists($assurance['db'], $nat_assurances)) {
        $assurance['libelle'] = $nat_assurances[$assurance['db']];
      }
    }

    return $this->assurance;
  }

  /**
   * Chargement des informations de l'arborescence du code
   * Table c_arborescence
   *
   * @return array Arborescence complète
   */
  function loadArborescence() {
    $ds = self::getSpec()->ds;
    $pere  = '000001';
    $track = '';
    foreach ($this->arborescence as &$chapitre) {
      $rang = $chapitre['db'];
      if ($rang == '00000') {
        break;
      }

      $chapters = CCCAM::getListChapters($pere);
      if (array_key_exists($rang, $chapters)) {
        $row = $chapters[$rang];

        if (!substr($row['RANG'], -2)) {
          break;
        }

        $track .= substr($row['RANG'], -2) . '.';

        $chapitre['rang'] = $track;
        $chapitre['code'] = $row['CODEMENU'];
        $chapitre['nom']  = $row['LIBELLE'];
        $chapitre['rq']   = array();

        $chapter_notes = CCCAM::getNotesChapters();
        if (array_key_exists($chapitre['code'], $chapter_notes)) {
          foreach ($chapter_notes[$chapitre['code']] as $note) {
            $chapitre['rq'][] = str_replace('¶', "\n", $note['TEXTE']);
          }
        }
      }

      $pere = $chapitre['code'];
    }
    return $this->arborescence;
  }

  /**
   * Load the PMSI extensions
   *
   * @return CExtensionPMSI[]
   */
  public function loadExtensionsPMSI() {
    return $this->_ref_extensions = CExtensionPMSI::loadList($this->code);
  }

  /**
   * Récupération des informations minimales d'un code
   * Non caché
   *
   * @param string $code Code CCAM
   *
   * @return array()
   */
  static function getCodeInfos($code) {
    $cache = new Cache(__METHOD__, func_get_args(), self::$cache_layers);
    if ($cache->exists()) {
      return $cache->get();
    }

    // Chargement
    $ds = self::getSpec()->ds;

    $query = "SELECT p_acte.CODE, p_acte.LIBELLELONG, p_acte.TYPE
        FROM p_acte
        WHERE p_acte.CODE = %";
    $query = $ds->prepare($query, $code);
    $result = $ds->exec($query);
    $code_ccam = $ds->fetchArray($result);

    return $cache->put($code_ccam, true);
  }

  /**
   * Récupération des modificateurs actifs pour une date donnée
   *
   * @param null $date Date de référence
   *
   * @return string Liste des modificateurs actifs
   */
  static function getModificateursActifs($date = null) {
    if (!$date) {
      $date = CMbDT::date();
    }
    $date = CMbDT::format($date, "%Y%m%d");

    $modifs = '';
    foreach (self::getListeForfaitsModificateurs() as $modificateur => $forfaits) {
      foreach ($forfaits as $forfait) {
        if ($forfait['DATEDEBUT'] <= $date && ($forfait['DATEFIN'] == '00000000' || $forfait['DATEFIN'] >= $date)) {
          $modifs .= $modificateur;
          break;
        }
      }
    }

    return $modifs;
  }

  /**
   * Récupération du forfait d'un modificateur
   *
   * @param string $modificateur Lettre clé du modificateur
   * @param string $grille       La grille de tarif a utiliser
   * @param string $date         Date de référence
   *
   * @return array forfait et coefficient
   */
  static function getForfait($modificateur, $grille = '14', $date = null) {
    if (!$date) {
      $date = CMbDT::date();
    }

    /* Surcharge de la date dans le mode test de Pyxvital */
    if (CModule::getActive('oxPyxvital') && CAppUI::gconf('pyxVital General mode') == 'test') {
      $date = CMbDT::date();

      if (CAppUI::gconf('pyxVital General date_ccam')) {
        $date = CAppUI::gconf('pyxVital General date_ccam');
      }
    }

    $date = CMbDT::format($date, "%Y%m%d");
    $valeur = array('forfait' => 0, 'coefficient' => 0);

    $forfaits_mods = self::getListeForfaitsModificateurs();
    if (array_key_exists($modificateur, $forfaits_mods)) {
      foreach ($forfaits_mods[$modificateur] as $forfait_mod) {
        if ($forfait_mod['GRILLE'] == "0{$grille}" && $forfait_mod['DATEDEBUT'] <= $date
            && ($forfait_mod['DATEFIN'] == '00000000' || $forfait_mod['DATEFIN'] >= $date)
        ) {
          $valeur['forfait'] = $forfait_mod['FORFAIT'] / 100;
          $valeur['coefficient'] = $forfait_mod['COEFFICIENT'] / 10;
          break;
        }
      }
    }

    return $valeur;
  }

  /**
   * Récupération du coefficient d'association
   *
   * @param string $code Code d'association
   *
   * @return float
   */
  static function getCoeffAsso($code) {
    $ds = self::getSpec()->ds;
    $valeur = 100;

    if ($code == 'X') {
      $valeur = 0;
    }
    elseif ($code) {
      $associations = self::getListeCodesAssociation();
      foreach ($associations as $association) {
        if ($code == $association['CODE'] && $association['DATEFIN'] == '00000000') {
          $valeur = $association['COEFFICIENT'] / 10;
        }
      }
    }

    return $valeur;
  }

  /**
   * Recherche de codes CCAM
   *
   * @param string $code       Codes partiels à chercher
   * @param string $keys       Mot clés à chercher
   * @param int    $max_length Longueur maximum du code
   * @param string $where      Autres paramètres where
   *
   * @return array Tableau d'actes
   */
  static function findCodes($code='', $keys='', $max_length = null, $where = null) {
    $ds = self::getSpec()->ds;

    $query = "SELECT CODE, LIBELLELONG
                FROM p_acte
                WHERE 1 ";

    $keywords = explode(" ", $keys);
    $codes    = explode(" ", $code);
    CMbArray::removeValue("", $keywords);
    CMbArray::removeValue("", $codes);

    if ($keys != "") {
      $listLike = array();
      $codeLike = array();
      foreach ($keywords as $value) {
        $listLike[] = "LIBELLELONG LIKE '%".addslashes($value)."%'";
      }
      if ($code != "") {
        // Combiner la recherche de code et libellé
        foreach ($codes as $value) {
          $codeLike[] = "CODE LIKE '".addslashes($value) . "%'";
        }
        $query .= " AND ( (";
        $query .= implode(" OR ", $codeLike);
        $query .= ") OR (";
      }
      else {
        // Ou uniquement le libellé
        $query .= " AND (";
      }
      $query .= implode(" AND ", $listLike);
      if ($code != "") {
        $query .= ") ) ";
      }

    }
    if ($code && !$keys) {
      // Ou uniquement le code
      $codeLike = array();
      foreach ($codes as $value) {
        $codeLike[] = "CODE LIKE '".addslashes($value) . "%'";
      }
      $query .= "AND ". implode(" OR ", $codeLike);
    }

    if ($max_length) {
      $query .= " AND LENGTH(CODE) < $max_length ";
    }

    if ($where) {
      $query .= "AND " . $where;
    }

    $query .= " ORDER BY CODE LIMIT 0 , 100";

    $result = $ds->exec($query);
    $master = array();
    $i = 0;
    while ($row = $ds->fetchArray($result)) {
      $master[$i]["LIBELLELONG"] = $row["LIBELLELONG"];
      $master[$i]["CODE"] = $row["CODE"];
      $i++;
    }

    return($master);
  }

  /**
   * Charge la liste des natures d'assurance à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeAssurances() {
    $cache = new Cache(__METHOD__, 'c_natureassurance', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `c_natureassurance`;');
    $assurances = array();
    if ($list) {
      foreach ($list as $assurance) {
        $assurances[$assurance['CODE']] = $assurance['LIBELLE'];
      }
    }

    return $cache->put($assurances, true);
  }

  /**
   * Charge la liste des types d'actes à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeTypesActe() {
    $cache = new Cache(__METHOD__, 'c_typeacte', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `c_typeacte`;');
    $types = array();
    if ($list) {
      foreach ($list as $type) {
        $types[$type['CODE']] = $type['LIBELLE'];
      }
    }

    return $cache->put($types, true);
  }

  /**
   * Charge la liste des codes d'association et leurs coefficients à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeCodesAssociation() {
    $cache = new Cache(__METHOD__, 't_association', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `t_association`;');

    return $cache->put($list, true);
  }

  /**
   * Charge la liste des forfaits de modificateurs à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeForfaitsModificateurs() {
    $cache = new Cache(__METHOD__, 't_modificateurforfait', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `t_modificateurforfait`;');
    $modificateurs = array();
    foreach ($list as $modificateur) {
      if (!array_key_exists($modificateur['CODE'], $modificateurs)) {
        $modificateurs[$modificateur['CODE']] = array();
      }

      $modificateurs[$modificateur['CODE']][] = $modificateur;
    }

    return $cache->put($modificateurs, true);
  }

  /**
   * Retourne les forfaits (SEH, FSD, FFM) par code
   *
   * @return array
   */
  public static function getListeForfaits() {
    $cache = new Cache(__METHOD__, 'forfaits', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `forfaits`;');
    $forfaits = array();
    foreach ($list as $row) {
      $forfaits[$row['code']] = $row['forfait'];
    }

    return $cache->put($forfaits, true);
  }
}
