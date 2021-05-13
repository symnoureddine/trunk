<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\OpenData;

use Ox\Core\CMbObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;

/**
 * Description
 */
class CCommuneFrance extends CMbObject {
  /** @var integer Primary key */
  public $commune_france_id;
  public $INSEE;
  public $commune;
  public $departement;
  public $region;
  public $statut;
  public $superficie;
  public $population;
  public $point_geographique;
  public $forme_geographique;

  public $_refs_commune_cp;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec                   = parent::getSpec();
    $spec->dsn              = 'INSEE';
    $spec->loggable         = false;
    $spec->uniques['INSEE'] = array('INSEE');
    $spec->table            = "communes_france_new";
    $spec->key              = "commune_france_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                       = parent::getProps();
    $props["INSEE"]              = "str minLength|3 maxLength|5 notNull";
    $props["commune"]            = "str notNull seekable";
    $props["departement"]        = "str";
    $props["region"]             = "str";
    $props["statut"]             = "enum list|comm|cheflieu|souspref|pref|prefregion|capital default|comm";
    $props["superficie"]         = "num min|0";
    $props["population"]         = "num min|0";
    $props["point_geographique"] = "str";
    $props["forme_geographique"] = "str";

    return $props;
  }

  function loadRefCommuneCp(){
    return $this->_refs_commune_cp = $this->loadBackRefs("cp");
  }

  /**
   * @return array
   */
  function getGeoPoint() {
    if (!$this->point_geographique) {
      return array();
    }
    $coors = explode(',', $this->point_geographique);
    return array(
      'x' => trim($coors[0]),
      'y' => trim($coors[1]),
    );
  }

  /**
   * @param string $insee_code INSEE code of the commune
   *
   * @return CCommuneFrance
   */
  function loadByInsee($insee_code) {
    $this->INSEE = $insee_code;

    $this->loadMatchingObject();

    return $this;
  }

  /**
   * @param string $column Column name
   * @param string $needle Value to search for
   *
   * @return array|false
   */
  static function getCommunesForCpName($column, $needle) {
    $ds = CSQLDataSource::get('INSEE');

    $prefix = 'P';
    $needle = "$needle%";
    if ($column !== 'code_postal') {
      $prefix = 'C';
      $needles = explode(' ', $needle);
      $needle = (count($needles) > 1) ? '%' . implode('%', $needles) . '%' : "%$needle";
    }
    $query = new CRequest();
    $query->addSelect(array('C.commune', 'P.code_postal', 'C.departement', 'C.INSEE', "'France' AS pays"));
    $query->addTable(array('communes_france_new C', 'communes_cp P'));
    $query->addWhere(
      array(
        "$prefix.$column"     => $ds->prepareLike($needle),
        "C.commune_france_id" => '= `P`.`commune_id`',
      )
    );

    return $ds->loadList($query->makeSelect());
  }
}
