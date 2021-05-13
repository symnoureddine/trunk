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
 * Class CDentCCAM
 * Table t_localisationdents
 *
 * Dents dans la CCAM
 */
class CDentCCAM extends CCCAM {

  public $date_debut;
  public $date_fin;
  public $localisation;
  public $_libelle;

  /**
   * Mapping des données depuis la base de données
   *
   * @param array $row Ligne d'enregistrement de de base de données
   *
   * @return void
   */
  function map($row) {
    $this->date_debut   = $row["DATEDEBUT"];
    $this->date_fin     = $row["DATEFIN"];
    $this->localisation = $row["LOCDENT"];
  }

  /**
   * Chargement de a liste des dents disponibles
   *
   * @return self[] Liste des dents
   */
  static function loadList() {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $ds = self::getSpec()->ds;

    $query = "SELECT t_localisationdents.*
      FROM t_localisationdents
      ORDER BY t_localisationdents.LOCDENT ASC,
        t_localisationdents.DATEFIN ASC";
    $result = $ds->exec($query);

    $listDents = array();
    while ($row = $ds->fetchArray($result)) {
      $dent = new CDentCCAM();
      $dent->map($row);
      $dent->loadLibelle();
      $listDents[$row["DATEFIN"]][] = $dent;
    }

    return $cache->put($listDents);
  }

  /**
   * Chargement d'une dent à partir de son numéro
   *
   * @param string $localisation Numero de la dent
   *
   * @return bool réussite du chargement
   */
  function load($localisation) {
    $localisation = (int) $localisation;
    $result = false;

    $dents = self::getListeDents();

    foreach ($dents as $dent) {
      if ($localisation == $dent['LOCDENT']) {
        $this->map($dent);
        $result = true;
        break;
      }
    }

    return $result;
  }

  /**
   * Chargement du libellé de la dent
   * Table c_dentsincomp
   *
   * @return string libellé de la dent
   */
  function loadLibelle() {
    $dents = self::getLibellesDents();

    $code_dent = str_pad($this->localisation, 2, "0", STR_PAD_LEFT);
    if (array_key_exists($code_dent, $dents)) {
      $this->_libelle = $dents[$code_dent];
    }

    return $this->_libelle;
  }

  /**
   * Charge la liste des libellés des dents à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getLibellesDents() {
    $cache = new Cache(__METHOD__, 'c_dentsincomp', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `c_dentsincomp`;');
    $dents = array();

    if ($list) {
      foreach ($list as $dent) {
        $dents[$dent['CODE']] = $dent['LIBELLE'];
      }
    }

    return $cache->put($dents, true);
  }

  /**
   * Charge la liste des libellés des dents à partir du cache ou de la base de données
   *
   * @return array
   */
  public static function getListeDents() {
    $cache = new Cache(__METHOD__, 't_localisationdents', Cache::INNER_OUTER);
    if ($cache->exists()) {
      return $cache->get();
    }

    self::getSpec();
    $list = self::$spec->ds->loadList('SELECT * FROM `t_localisationdents`;');

    return $cache->put($list, true);
  }
}