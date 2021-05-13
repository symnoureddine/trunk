<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Forms;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\DSHM;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\System\Forms\CExClass;
use Ox\Mediboard\System\Forms\CExObject;

/**
 * Description
 */
class CExClassRefChecker implements IShortNameAutoloadable {
  protected $ex_class_id;
  protected $start;
  protected $step;
  protected $total;
  protected $ended = false;
  protected $time_start;

  /** @var Cache */
  protected $cache;

  /** @var CExObject */
  protected $ex_object;

  /** @var CSQLDataSource */
  protected $ds;

  protected $ref_errors = [];

  public static $fields = ['object_class' => 'object_id', 'reference_class' => 'reference_id', 'reference2_class' => 'reference2_id'];

  const PREFIX = 'ref_check';
  const PRE_TBL = 'ex_object_';

  /**
   * CExClassRefChecker constructor.
   *
   * @param int $ex_class_id ExClass id to load
   */
  public function __construct($ex_class_id) {
    $this->ex_class_id = $ex_class_id;
    $this->ex_object   = new CExObject($ex_class_id);

    $this->ds = $this->ex_object->getDS();
  }

  /**
   * @param int $start Start used for limit
   * @param int $step  Step used for limit
   *
   * @return void
   * @throws Exception
   */
  public function check($start = 0, $step = 100) {
    $this->init($start, $step);

    if ($this->ended) {
      CAppUI::setMsg("CExClassRefChecker-msg-ended-%s", UI_MSG_OK, static::PRE_TBL . $this->ex_class_id);

      return;
    }

    $ex_objects = $this->getExObjectsToCheck();

    if ($ex_objects) {
      $class_ids = $this->getIdsByClass($ex_objects);

      $objects = $this->getObjectsFromIds($class_ids);

      list($classes_missing, $ids_missing) = $this->getMissingObjects($ex_objects, $objects);

      $this->checkRefsFromForms($ex_objects, $classes_missing, $ids_missing);
    }

    if (!$ex_objects || count($ex_objects) < $this->step) {
      $this->ended = true;
      CAppUI::setMsg("CExClassRefChecker-msg-ended-%s", UI_MSG_OK, static::PRE_TBL . $this->ex_class_id);
    }

    $this->putInCache();
  }

  /**
   * Init vars
   *
   * @param int $start Start to use
   * @param int $step  Step to use
   *
   * @return void
   */
  protected function init($start, $step) {
    $this->start = $start;
    $this->step  = $step;

    $this->cache = new Cache(static::PREFIX, static::PRE_TBL . $this->ex_class_id, Cache::DISTR);
    if ($data = $this->cache->get()) {
      $this->start = isset($data['start']) ? $data['start'] : 0;
      $this->ended = isset($data['ended']) ? $data['ended'] : false;
      $this->total = isset($data['total']) ? $data['total'] : null;
    }

    $this->time_start = microtime(true);
  }

  /**
   * @return array
   * @throws Exception
   */
  protected function getExObjectsToCheck() {
    $query = new CRequest();
    $query->addTable($this->ex_object->getTableName());
    $query->addWhere(["group_id" => $this->ds->prepare("= ?", CGroups::loadCurrent()->_id)]);
    $query->addOrder($this->ex_object->_spec->key);

    if ($this->total === null || $this->total < ($this->start + $this->step)) {
      $this->total = $this->ds->loadResult($query->makeSelectCount());
    }

    $query->select = [];
    $query->addSelect($this->ex_object->_spec->key);

    foreach (static::$fields as $_class => $_id) {
      $query->addSelect($_class);
      $query->addSelect($_id);
    }

    $query->setLimit("{$this->start},{$this->step}");

    return $this->ds->loadList($query->makeSelect());
  }

  /**
   * @param array $ex_objects Array to sort
   *
   * @return array
   */
  protected function getIdsByClass($ex_objects) {
    $classes = array();
    foreach ($ex_objects as $_ex_object) {
      foreach (static::$fields as $_class => $_id) {
        if (!isset($classes[$_ex_object[$_class]])) {
          $classes[$_ex_object[$_class]] = [];
        }

        if (!isset($classes[$_ex_object[$_class]][$_ex_object[$_id]])) {
          $classes[$_ex_object[$_class]][$_ex_object[$_id]] = true;
        }
      }
    }

    return $classes;
  }

  /**
   * @param array $class_ids Array of ids indexed with their class
   *
   * @return array
   * @throws Exception
   */
  protected function getObjectsFromIds($class_ids) {
    $objects = [];
    foreach ($class_ids as $_class => $_ids) {
      /** @var CStoredObject $obj */
      $obj = new $_class();
      $ds  = $obj->getDS();

      $query = new CRequest();
      $query->addSelect($obj->_spec->key);
      $query->addTable($obj->_spec->table);
      $query->addWhere([$obj->_spec->key => $ds->prepareIn(array_keys($_ids))]);

      $objects[$_class] = $ds->loadColumn($query->makeSelect());
    }

    $objects = array_map("array_flip", $objects);

    return $objects;
  }

  /**
   * @param array $ex_objects Objects to check
   * @param array $objects    Object used for the check
   *
   * @return array
   */
  protected function getMissingObjects($ex_objects, $objects) {
    $classes_missing = [];
    $ids_missing     = [];
    foreach ($ex_objects as $_ex_object) {
      foreach (static::$fields as $_class => $_id) {
        $class_name = $_ex_object[$_class];
        $obj_id     = $_ex_object[$_id];

        // La classe référencée n'existe plus
        if (!isset($objects[$class_name])) {
          if (!isset($classes_missing[$class_name])) {
            $classes_missing[$class_name] = true;
          }

          continue;
        }

        // L'identifiant référencé n'existe plus
        if (!isset($objects[$class_name][$obj_id])) {
          if (!isset($ids_missing[$class_name])) {
            $ids_missing[$class_name] = [];
          }

          if (!isset($ids_missing[$class_name][$obj_id])) {
            $ids_missing[$class_name][$obj_id] = true;
          }
        }
      }
    }

    return [
      $classes_missing,
      $ids_missing
    ];
  }

  /**
   * @param array $ex_objects     Objects to check
   * @param array $class_missings Class missing
   * @param array $ids_missings   Ids missing
   *
   * @return void
   */
  protected function checkRefsFromForms($ex_objects, $class_missings, $ids_missings) {
    foreach ($ex_objects as $_ex_object) {
      foreach (static::$fields as $_field_class => $_field_id) {
        $class_name = $_ex_object[$_field_class];
        $obj_id     = $_ex_object[$_field_id];

        if (isset($class_missings[$class_name])) {
          // TODO La classe référencée par $_field_class n'existe plus
        }

        if (isset($ids_missings[$class_name]) && isset($ids_missings[$class_name][$obj_id])) {
          if (!in_array($_ex_object[$this->ex_object->_spec->key], $this->ref_errors)) {
            $this->ref_errors[] = $_ex_object[$this->ex_object->_spec->key];
            CAppUI::setMsg("Erreur de référence", UI_MSG_WARNING);
          }
        }
      }
    }
  }

  /**
   * Put infos in cache
   *
   * @return void
   */
  protected function putInCache() {
    $data = ($this->cache->get()) ?: ['start' => 0, 'errors' => [], 'ended' => false, 'total' => 0, 'step' => $this->step];

    $next_start = $this->start + $this->step;

    $end = microtime(true) - $this->time_start;
    if ($end < 60) {
      $this->step *= 2;
    }
    elseif ($end > 60) {
      $this->step /= 2;
    }

    $data['total'] = $this->total;
    $data['start'] = ($next_start > $this->total) ? $this->total : $next_start;
    $data['ended'] = $this->ended;
    $data['step']  = $this->step;
    foreach ($this->ref_errors as $_ex_object_id) {
      if (!in_array($_ex_object_id, $data['errors'])) {
        $data['errors'][] = $_ex_object_id;
      }
    }

    $this->cache->put($data);
  }

  /**
   * Get the keys from DSHM
   *
   * @param CExClass[] $ex_classes ExClass to check
   *
   * @return array|false
   */
  public static function getKeys($ex_classes) {
    $keys = [];
    foreach ($ex_classes as $_ex_class) {
      $keys[] = CExClassRefChecker::PREFIX . '-' . CExClassRefChecker::PRE_TBL . $_ex_class->_id;
    }

    return array_combine($keys, DSHM::multipleGet($keys));
  }
}
