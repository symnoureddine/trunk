<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Lpp;

use Ox\Core\CModelObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;

/**
 * Represents a chapter of the LPP architecture
 */
class CLPPChapter extends CModelObject {

  /** @var string The id of the chapter in the architecture */
  public $id;

  /** @var string The name of the chapter */
  public $name;

  /** @var integer The rank of the chapter in his level */
  public $rank;

  /** @var string The id of the parent of the chapter */
  public $parent_id;

  /** @var CLPPChapter The direct ancestor */
  public $_parent;

  /** @var CLPPChapter[] The descendants */
  public $_descendants;

  /** @var CLPPCode[] The LPP codes that descend from this chapter */
  public $_codes;

  /** @var array A conversion table from the db fields to the object fields */
  public static $db_fields = array(
    'ID'      => 'id',
    'PARENT'  => 'parent_id',
    'INDEX'   => 'rank',
    'LIBELLE' => 'name'
  );

  /**
   * CLPPChapter constructor.
   *
   * @param array $data The data returned from the database
   */
  public function __construct($data = array()) {
    parent::__construct();

    foreach ($data as $_column => $_value) {
      $_field = self::$db_fields[$_column];

      $this->$_field = $_value;
    }
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  public function getProps() {
    $props = parent::getProps();

    $props['id']        = 'str notNull';
    $props['name']      = 'str notNull';
    $props['rank']      = 'num notNull';
    $props['parent_id'] = 'str notNull';

    return $props;
  }

  /**
   * Load the parent of the chapter
   *
   * @return CLPPChapter
   */
  public function loadDirectAncestor() {
    if ($this->parent_id != 0) {
      $this->_parent = self::load($this->parent_id);
    }

    return $this->_parent;
  }

  /**
   * Load all the ancestors of this chapter
   *
   * @return void
   */
  public function loadAncestors() {
    $this->loadDirectAncestor();

    if ($this->_parent) {
      $this->_parent->loadAncestors();
    }
  }

  /**
   * Load the direct descendants of this chapter
   *
   * @return CLPPChapter[]
   */
  public function loadDirectDescendants() {
    if (!$this->_descendants) {
      $this->_descendants = self::loadfromParent($this->id);
    }

    return $this->_descendants;
  }

  /**
   * Load the LPP codes that descend from this chapter
   *
   * @return CLPPCode[]
   */
  public function loadCodes() {
    if (!$this->_codes) {
      $this->_codes = CLPPCode::search(null, null, $this->id);
    }

    return $this->_codes;
  }

  /**
   * Do not remove (loadRefModule() is called in the CModelObject but only declared in CStoredObject, and not in CModelObject)
   *
   * @return void
   */
  public function loadRefModule() {
    return;
  }

  /**
   * Load the chapter with the given id
   *
   * @param string $id The id of the chapter
   *
   * @return CLPPChapter
   */
  public static function load($id) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('arborescence');
    $query->addWhere($ds->prepare('`ID` = ?', $id));
    $result = $ds->loadHash($query->makeSelect());

    if (!$result) {
      return false;
    }

    return new self($result);
  }

  /**
   * Load the direct descendants of the given chapter
   *
   * @param string $parent_id The parent id
   *
   * @return CLPPChapter[]
   */
  public static function loadfromParent($parent_id) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('arborescence');
    $query->addWhere($ds->prepare('`PARENT` = ?', $parent_id));
    $query->addOrder('`INDEX` ASC');

    $results = $ds->loadList($query->makeSelect());

    $chapters = array();
    if ($results) {
      foreach ($results as $_result) {
        $chapters[] = new self($_result);
      }
    }

    return $chapters;
  }
}
