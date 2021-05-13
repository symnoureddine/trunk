<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\OpenData;

use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\System\Forms\CExObject;

/**
 * Description
 */
class CImportConflict extends CMbObject {
  /** @var integer Primary key */
  public $import_conflict_id;

  public $field;
  public $value;
  public $audit;
  public $file_version;
  public $import_tag;
  public $ignore;

  public $object_class;
  public $object_id;
  public $_ref_object;

  public $_ignore;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec           = parent::getSpec();
    $spec->loggable = false;
    $spec->table    = "import_conflict";
    $spec->key      = "import_conflict_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();

    $props["field"]        = "str notNull";
    $props["value"]        = "text markdown";
    $props["audit"]        = "bool default|1";
    $props["file_version"] = "str";
    $props["import_tag"]   = "str";
    $props["ignore"]       = "bool default|0";
    $props["object_id"]    = "ref notNull class|CMbObject meta|object_class back|import_conflict";
    $props["object_class"] = "str notNull class show|0";

    $props["_ignore"]       = "set list|0|1";

    return $props;
  }

  /**
   * Get all the conflicts for a class
   *
   * @param string $nom    CMedecin-nom
   * @param string $prenom CMedecin-prenom
   * @param string $cp     CMedecin-cp
   * @param array  $ids    List of CMedecin ids to load
   * @param int    $start  Start at position
   * @param int    $step   Number of results
   * @param bool   $audit  Search for audit conflicts or not
   *
   * @return array
   */
  static function getConflictsGroupByMedecin($nom, $prenom, $cp, $ids = array(), $start = 0, $step = 100, $audit = true) {
    $conflict = new self();
    $ds       = $conflict->getDS();

    $table = $conflict->_spec->table;

    $ljoin = array(
      'medecin' => "(`$table`.object_class = 'CMedecin' AND `$table`.object_id = `medecin`.medecin_id)",
    );

    $conflict->audit = ($audit) ? '1' : '0';
    $conflict->import_tag = 'import_rpps';

    $where = array();

    if ($nom) {
      $where['medecin.nom'] = $ds->prepareLike("$nom%");
    }

    if ($prenom) {
      $where['medecin.prenom'] = $ds->prepareLike("$prenom%");
    }

    if ($cp) {
      $where['medecin.cp'] = $ds->prepareLike("$cp%");
    }

    if ($ids) {
      $where["{$table}.object_id"] = $ds->prepareIn($ids);
    }

    $conflicts_by_med = $conflict->loadList($where, "$table.object_id ASC", "$start,$step", "$table.object_id", $ljoin);

    $conflicts = array();
    foreach ($conflicts_by_med as $_conflict) {
      $med_id = $_conflict->object_id;
      $_conflict->nullifyProperties();
      $_conflict->object_class = 'CMedecin';
      $_conflict->object_id = $med_id;
      $_conflict->import_tag = $conflict->import_tag;
      $_conflict->audit = $conflict->audit;

      $_conflicts = $_conflict->loadMatchingListEsc();

      if (!array_key_exists($_conflict->object_id, $conflicts)) {
        $conflicts[$_conflict->object_id] = array();
      }

      foreach ($_conflicts as $_conf) {
        $conflicts[$_conf->object_id][] = $_conf;
      }
    }

    return $conflicts;
  }

  /**
   * Get all the conflicts for a medecin
   *
   * @param int|array $medecin_id Medecin id to get conflicts for
   * @param string    $tag        Tag to search for
   *
   * @return CImportConflict[]|array
   * @throws Exception
   */
  static function getConflictsForMedecin($medecin_id, $tag = null, $audit = false) {
    $ds = CSQLDataSource::get('std');

    $ids = (is_array($medecin_id)) ? $medecin_id : explode('|', $medecin_id);

    $query = new CRequest();
    $query->addSelect('import_conflict_id');
    $query->addTable('import_conflict');
    $where =  array(
      'object_id' => $ds->prepareIn($ids),
    );

    if ($tag) {
      $where['import_tag'] = $ds->prepare("= ?", $tag);
    }
    else {
      $where['import_tag'] = 'IS NULL';
    }

    if ($audit) {
      $where['audit'] = "= '1'";
    }
    else {
      $where['audit'] = "= '0'";
    }

    $query->addWhere($where);

    $conflicts_ids = $ds->loadList($query->makeSelect());
    if ($conflicts_ids) {
      $conflicts_ids = CMbArray::pluck($conflicts_ids, 'import_conflict_id');
    }

    return $conflicts_ids;
  }

  /**
   * Count the number of conflicts for an object_class
   *
   * @param string $object_class Class to count conflicts for
   * @param string $import_tag   Import tag
   * @param bool   $audit        Select the audits or not
   *
   * @return array|false
   */
  function getCountConflictsByObject($object_class, $import_tag, $audit) {
    $ds    = $this->getDS();
    $query = new CRequest();
    $query->addWhere(
      array(
        'audit'        => $ds->prepare('= ?', ($audit) ? '1' : '0'),
        'object_class' => $ds->prepare('= ?', $object_class),
        'import_tag'   => $ds->prepare('= ?', $import_tag),
      )
    );
    $query->addTable($this->getSpec()->table);
    $query->addGroup('object_id');
    $query->addOrder('object_id ASC');

    return $ds->loadList($query->makeSelectCount());
  }

  /**
   * Retourne les conflits pour un correspondant lié au patient
   * Si le tableau retrouné est vide alors il n'y a aucun conflit
   *
   * @param CPatient $patient    Patient to search conflicts for
   * @param bool     $return_ids Return the medecin_ids and the conflicts
   *
   * @return array
   */
  static function getConflictsForPatient($patient, $return_ids = false) {
    $patient->loadRefsCorrespondants();

    $medecin_ids = array();
    if ($patient->_ref_medecin_traitant && $patient->_ref_medecin_traitant->_id) {
      $medecin_ids[] = $patient->_ref_medecin_traitant->_id;
    }

    if ($patient->_ref_pharmacie && $patient->_ref_pharmacie->_id) {
      $medecin_ids[] = $patient->_ref_pharmacie->_id;
    }

    foreach ($patient->_ref_medecins_correspondants as $curr_corresp) {
      // Needed to avoid a notice in inc_widget_correspondants
      $curr_corresp->_ref_medecin->loadRefSpecCPAM();
      $medecin_ids[] = $curr_corresp->_id;
    }

    $conflicts = CImportConflict::getConflictsForMedecin($medecin_ids);

    return ($return_ids) ? array('medecin_ids' => $medecin_ids, 'conflicts' => $conflicts) : $conflicts;
  }

  /**
   * @param CStoredObject $object
   * @deprecated
   * @todo redefine meta raf
   * @return void
   */
  public function setObject(CStoredObject $object) {
    CMbMetaObjectPolyfill::setObject($this, $object);
  }

  /**
   * @param bool $cache
   * @deprecated
   * @todo redefine meta raf
   * @return bool|CStoredObject|CExObject|null
   * @throws Exception
   */
  public function loadTargetObject($cache = true) {
    return CMbMetaObjectPolyfill::loadTargetObject($this, $cache);
  }

  /**
   * @inheritDoc
   * @todo remove
   */
  function loadRefsFwd() {
    parent::loadRefsFwd();
    $this->loadTargetObject();
  }
}
