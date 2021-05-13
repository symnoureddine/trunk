<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Cache;

/**
 * Class CActiviteClassifCCAM
 * Table p_activite_classif
 *
 * Classification des actes
 * Niveau activite
 */
class CActiviteClassifCCAM extends CCCAM {

  public $date_effet;
  public $arrete_minist;
  public $publication_jo;
  public $categorie_medicale;
  public $_categorie_medicale;
  public $code_regroupement;
  public $_regroupement;

  /**
   * Mapping des données depuis la base de données
   *
   * @param array $row Ligne d'enregistrement de de base de données
   *
   * @return void
   */
  function map($row) {
    $this->date_effet         = $row["DATEEFFET"];
    $this->arrete_minist      = $row["DATEARRETE"];
    $this->publication_jo     = $row["DATEPUBJO"];
    $this->categorie_medicale = $row["CATMED"];
    $this->code_regroupement  = $row["REGROUP"];
  }

  /**
   * Chargement de a liste des classifications pour une activite
   *
   * @param string $code     Code CCAM
   * @param string $activite Activité CCAM
   *
   * @return self[] Liste des classifications historisées
   */
  static function loadListFromCodeActivite($code, $activite) {
    $ds = self::$spec->ds;

    $query = "SELECT p_activite_classif.*
      FROM p_activite_classif
      WHERE p_activite_classif.CODEACTE = %1
      AND p_activite_classif.ACTIVITE = %2
      ORDER BY p_activite_classif.DATEEFFET DESC";
    $query = $ds->prepare($query, $code, $activite);
    $result = $ds->exec($query);

    $list_classif = array();
    while ($row = $ds->fetchArray($result)) {
      $classif = new CActiviteClassifCCAM();
      $classif->map($row);
      $list_classif[$row["DATEEFFET"]] = $classif;
    }

    return $list_classif;
  }

  /**
   * Chargement du libellé de la catégorie médicale
   * Table c_categoriemedicale
   *
   * @return string le libellé de la catégorie
   */
  function loadCatMed() {
    $categories = self::getListeCategoriesMedicales();

    if (array_key_exists($this->categorie_medicale, $categories)) {
      $this->_categorie_medicale = $categories[$this->categorie_medicale];
    }

    return $this->_categorie_medicale;
  }

  /**
   * Chargement du libellé de regroupement
   * Table c_coderegroupement
   *
   * @return string le libellé du regroupement
   */
  function loadRegroupement() {
    $codes = self::getListeCodesRegroupement();

    if (array_key_exists($this->code_regroupement, $codes)) {
      $this->_regroupement = $codes[$this->code_regroupement];
    }

    return $this->_regroupement;
  }

  /**
   * Charge la liste des catégories médicales à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeCategoriesMedicales() {
    $cache = new Cache(__METHOD__, 'c_categoriemedicale', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `c_categoriemedicale`;');
    $categories = array();
    if ($list) {
      foreach ($list as $categorie) {
        $categories[$categorie['CODE']] = $categorie['LIBELLE'];
      }
    }

    return $cache->put($categories, true);
  }

  /**
   * Charge la liste des codes de regroupement médicales à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeCodesRegroupement() {
    $cache = new Cache(__METHOD__, 'c_coderegroupement', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `c_coderegroupement`;');
    $codes = array();
    if ($list) {
      foreach ($list as $code) {
        $codes[$code['CODE']] = $code['LIBELLE'];
      }
    }

    return $cache->put($codes, true);
  }
}