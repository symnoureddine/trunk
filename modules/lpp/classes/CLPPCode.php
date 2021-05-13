<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Lpp;

use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;

/**
 * Represent a LPP code
 */
class CLPPCode extends CModelObject {

  /** @var string The LPP code */
  public $code;

  /** @var string The full name of the code */
  public $name;

  /** @var string The date from which the code is not valid anymore */
  public $end_date;

  /** @var integer The maximum age */
  public $max_age;

  /** @var string The type of prestation for the code */
  public $prestation_type;

  /** @var bool Show if there are medical indication for this prestation */
  public $indication;

  /** @var integer The number of the first chapter */
  public $chapter_1;

  /** @var integer The number of the second chapter */
  public $chapter_2;

  /** @var integer The number of the third chapter */
  public $chapter_3;

  /** @var integer The number of the fourth chapter */
  public $chapter_4;

  /** @var integer The number of the fifth chapter */
  public $chapter_5;

  /** @var integer The number of the sixth chapter */
  public $chapter_6;

  /** @var integer The number of the seventh chapter */
  public $chapter_7;

  /** @var integer The number of the eighth chapter */
  public $chapter_8;

  /** @var integer The number of the ninth chapter */
  public $chapter_9;

  /** @var integer The number of the tenth chapter */
  public $chapter_10;

  /** @var integer The rank of the code in its parent chapter */
  public $rank;

  /** @var integer The id of the prosthesis */
  public $prosthesis;

  /** @var integer The first medical reference number */
  public $rmo_1;

  /** @var integer The second medical reference number */
  public $rmo_2;

  /** @var integer The third medical reference number */
  public $rmo_3;

  /** @var integer The fourth medical reference number */
  public $rmo_4;

  /** @var integer The fifth medical reference number */
  public $rmo_5;

  /** @var CLPPDatedPricing[] The dated pricings */
  public $_pricings;

  /** @var CLPPDatedPricing The pricing that's still in effect */
  public $_last_pricing;

  /** @var CLPPCode[] The compatible code */
  public $_compatibilities;

  /** @var CLPPCode[] The incompatible codes */
  public $_incompatibilities;

  /** @var string The id of the parent chapter */
  public $_parent_id;

  /** @var CLPPChapter The parent chapter of the code */
  public $_parent;

  /** @var array The list of the unauthorized expense qualifying */
  public $_unauthorized_expense_qualifying = array();

  /** @var array A conversion table from the db fields to the object fields */
  public static $db_fields = array(
    'CODE_TIPS'  => 'code',
    'NOM_COURT'  => 'name',
    'RMO1'       => 'rmo_1',
    'RMO2'       => 'rmo_2',
    'RMO3'       => 'rmo_3',
    'RMO4'       => 'rmo_4',
    'RMO5'       => 'rmo_5',
    'DATE_FIN'   => 'end_date',
    'AGE_MAX'    => 'max_age',
    'TYPE_PREST' => 'prestation_type',
    'INDICATION' => 'indication',
    'ARBO1'      => 'chapter_1',
    'ARBO2'      => 'chapter_2',
    'ARBO3'      => 'chapter_3',
    'ARBO4'      => 'chapter_4',
    'ARBO5'      => 'chapter_5',
    'ARBO6'      => 'chapter_6',
    'ARBO7'      => 'chapter_7',
    'ARBO8'      => 'chapter_8',
    'ARBO9'      => 'chapter_9',
    'ARBO10'     => 'chapter_10',
    'PLACE'      => 'rank',
    'PROTHESE'   => 'prosthesis'
  );

  /**
   * CLPPCode constructor.
   *
   * @param array $data The data returned from the database
   */
  public function __construct($data = array()) {
    parent::__construct();

    foreach ($data as $_column => $_value) {
      $_field = self::$db_fields[$_column];

      if ($_field == 'indication') {
        $_value = $_value == 'O' ? 1 : 0;
      }

      $this->$_field = $_value;
    }
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();

    $props['code']            = 'str maxLength|7 minLength|7 notNull';
    $props['name']            = 'str notNull';
    $props['end_date']        = 'date';
    $props['max_age']         = 'num default|0';
    $props['prestation_type'] = 'enum list|A|E|L|P|S|R|V';
    $props['indication']      = 'bool';
    $props['chapter_1']       = 'num';
    $props['chapter_2']       = 'num';
    $props['chapter_3']       = 'num';
    $props['chapter_4']       = 'num';
    $props['chapter_5']       = 'num';
    $props['chapter_6']       = 'num';
    $props['chapter_7']       = 'num';
    $props['chapter_8']       = 'num';
    $props['chapter_9']       = 'num';
    $props['chapter_10']      = 'num';
    $props['rank']            = 'num notNull';
    $props['prosthesis']      = 'num';
    $props['rmo_1']           = 'num';
    $props['rmo_2']           = 'num';
    $props['rmo_3']           = 'num';
    $props['rmo_4']           = 'num';
    $props['rmo_5']           = 'num';
    $props['_parent_id']      = 'ref class|CLPPChapter';

    return $props;
  }

  /**
   * Load the pricings for this codes
   *
   * @return CLPPDatedPricing[]
   */
  public function loadPricings() {
    if (!$this->_pricings) {
      $this->_pricings = CLPPDatedPricing::loadFromCode($this->code);
    }

    return $this->_pricings;
  }

  /**
   * Load the latest pricing available at the given date
   *
   * @param string $date The date
   *
   * @return CLPPDatedPricing
   */
  public function loadLastPricing($date = null) {
    if (!$this->_last_pricing) {
      $this->_last_pricing = CLPPDatedPricing::loadLast($this->code, $date);
    }

    return $this->_last_pricing;
  }

  /**
   * Load the code compatibile with this one
   *
   * @return CLPPCode[]
   */
  public function loadCompatibilities() {
    if (!$this->_compatibilities) {
      $ds = CSQLDataSource::get('lpp');

      $query = new CRequest();
      $query->addSelect('`fiche`.*');
      $query->addTable('fiche');
      $query->addRJoinClause('comp', "`fiche`.`CODE_TIPS` = `comp`.`CODE2`");
      $query->addWhere($ds->prepare('`comp`.`CODE1` = ?', $this->code));
      $results = $ds->loadList($query->makeSelect());

      $this->_compatibilities = array();
      if ($results) {
        foreach ($results as $_result) {
          $this->_compatibilities[] = new self($_result);
        }
      }
    }

    return $this->_compatibilities;
  }

  /**
   * Load the code incompatibile with this one
   *
   * @return CLPPCode[]
   */
  public function loadIncompatibilities() {
    if (!$this->_incompatibilities) {
      $ds = CSQLDataSource::get('lpp');

      $query = new CRequest();
      $query->addSelect('`fiche`.*');
      $query->addTable('fiche');
      $query->addRJoinClause('incomp', "`fiche`.`CODE_TIPS` = `incomp`.`CODE2`");
      $query->addWhere($ds->prepare('`incomp`.`CODE1` = ?', $this->code));
      $results = $ds->loadList($query->makeSelect());

      $this->_incompatibilities = array();
      if ($results) {
        foreach ($results as $_result) {
          $this->_incompatibilities[] = new self($_result);
        }
      }
    }

    return $this->_incompatibilities;
  }

  /**
   * Get the parent id from the chapter fields
   *
   * @return string
   */
  public function getParentId() {
    $this->_parent_id = '0';

    for ($i = 1; $i <= 10; $i++) {
      $field = "chapter_$i";
      if (!$this->$field || $this->$field == '0') {
        break;
      }

      $_chapter = $this->$field;
      switch ($_chapter) {
        case 10:
          $_chapter = 'A';
          break;
        case 11:
          $_chapter = 'B';
          break;
        case 12:
          $_chapter = 'C';
          break;
        case 13:
          $_chapter = 'D';
          break;
        case 14:
          $_chapter = 'E';
          break;
        case 15:
          $_chapter = 'F';
          break;
        default:
      }

      $this->_parent_id .= $_chapter;
    }

    return $this->_parent_id;
  }

  /**
   * Load the parent chapter of the code
   *
   * @return CLPPChapter
   */
  public function loadParent() {
    if (!$this->_parent) {
      $this->getParentId();

      /* It is possible that the full chapter code doesn't exists, so we load the levels chapters until we found one that exists */
      $i = strlen($this->_parent_id);
      while (!$this->_parent) {
        $parent_id = $this->_parent_id;
        if ($i != strlen($this->_parent_id)) {
          $parent_id = substr($this->_parent_id, 0, $i);
        }
        $this->_parent = CLPPChapter::load($parent_id);

        $i--;
        if ($i <= 2) {
          break;
        }
      }
    }

    return $this->_parent;
  }

  /**
   * Get the list of authorized expense qualifying
   *
   * @return array
   */
  public function getQualificatifsDepense() {
    if (CModule::getActive('oxPyxvital') && $this->_last_pricing->code) {
      $ds    = CSQLDataSource::get('sesam-vitale');
      $query = new CRequest();
      $query->addTable('t7');
      $query->addSelect(array('g', 'f', 'e', 'd', 'n', 'a', 'b'));
      $query->addWhere($ds->prepare('`code` = ?', $this->_last_pricing->prestation_code));

      $result = $ds->loadHash($query->makeSelect());

      $this->_unauthorized_expense_qualifying = array();
      if ($result) {
        foreach ($result as $_qualif => $_value) {
          if (!$_value) {
            $this->_unauthorized_expense_qualifying[] = $_qualif;
          }
        }
      }
    }

    return $this->_unauthorized_expense_qualifying;
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
   * Load the CLPPCode from the given code
   *
   * @param string $code The LPP code to load
   *
   * @return CLPPCode
   */
  public static function load($code) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('fiche');
    $query->addWhere($ds->prepare('`CODE_TIPS` = ?', $code));
    $result = $ds->loadHash($query->makeSelect());

    if (!$result) {
      return false;
    }

    return new self($result);
  }

  /**
   * Load all the descendants codes of the given chapter
   *
   * @param string $parent_id The parent id
   *
   * @return CLPPCode[]
   */
  public static function loadFromParent($parent_id) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('fiche');

    $where = array();

    self::makeWhereChapters($parent_id, $where, $ds);

    $query->addWhere($where);
    $results = $ds->loadList($query->makeSelect());

    $codes = array();
    if ($results) {
      foreach ($results as $_result) {
        $codes[] = new self($_result);
      }
    }

    return $codes;
  }

  /**
   * Search a code by its code, name and/or chapter
   *
   * @param string $code       The partial or complete code to search
   * @param string $text       A keyword to search
   * @param string $chapter_id The parent chapter id
   * @param string $date_valid Only get the codes that are still valid to the given date
   * @param int    $start      The start
   * @param int    $limit      The number of results to get
   *
   * @return CLPPCode[]
   */
  public static function search($code = null, $text = null, $chapter_id = null, $date_valid = null, $start = 0, $limit = 0) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('fiche');
    $where   = array();
    $whereOr = array();

    if ($code) {
      $whereOr[] = $ds->prepare('`CODE_TIPS` LIKE ?', "$code%");
    }
    if ($text) {
      $whereOr[] = $ds->prepare('`NOM_COURT` LIKE ?', strtoupper(addslashes("%$text%")));
    }

    if (count($whereOr)) {
      $where[] = implode(' OR ', $whereOr);
    }

    if ($chapter_id) {
      self::makeWhereChapters($chapter_id, $where, $ds);
    }

    if ($date_valid) {
      $where[] = $ds->prepare('`DATE_FIN` IS NULL OR `DATE_FIN` >= ?', $date_valid);
    }

    $query->addWhere($where);

    if ($limit) {
      $query->setLimit("$start, $limit");
    }

    $query->addOrder(
      array(
        '`ARBO1` ASC',
        '`ARBO2` ASC',
        '`ARBO3` ASC',
        '`ARBO4` ASC',
        '`ARBO5` ASC',
        '`ARBO6` ASC',
        '`ARBO7` ASC',
        '`ARBO8` ASC',
        '`ARBO9` ASC',
        '`ARBO10` ASC',
        '`PLACE` ASC'
      )
    );

    $results = $ds->loadList($query->makeSelect());

    $codes = array();
    if ($results) {
      foreach ($results as $_result) {
        $codes[] = new self($_result);
      }
    }

    return $codes;
  }

  /**
   * Get the prestation allowed for the given medical speciality
   *
   * @param integer $speciality The id of the CPAM specialty
   *
   * @return array
   */
  public static function getAllowedPrestationCodes($speciality) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addTable('code_prestation_to_specialite');
    $query->addSelect('code_prestation');
    $query->addWhere($ds->prepare('`specialite` = ?', $speciality));

    $results = $ds->loadList($query->makeSelect());

    $codes_prestation = array();
    foreach ($results as $result) {
      $codes_prestation[] = $result['code_prestation'];
    }

    return $codes_prestation;
  }

  /**
   * Count the results
   *
   * @param string $code       The partial or complete code to search
   * @param string $text       A keyword to search
   * @param string $chapter_id The parent chapter id
   *
   * @return integer
   */
  public static function count($code = null, $text = null, $chapter_id = null) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addTable('fiche');
    $where = array();

    if ($code) {
      $where[] = $ds->prepare('`CODE_TIPS` LIKE ?', "$code%");
    }
    if ($text) {
      $where[] = $ds->prepare('`NOM_COURT` LIKE ?', strtoupper(addslashes("%$text%")));
    }
    if ($chapter_id) {
      self::makeWhereChapters($chapter_id, $where, $ds);
    }

    $query->addWhere($where);

    return $ds->loadResult($query->makeSelectCount());
  }

  /**
   * Make the where clauses for querying the chapter
   *
   * @param string         $chapter_id The chapter id
   * @param array          $where      The where clauses
   * @param CSQLDataSource $ds         The datasource
   *
   * @return void
   */
  public static function makeWhereChapters($chapter_id, &$where, $ds) {
    for ($i = 1; $i < strlen($chapter_id); $i++) {
      switch ($chapter_id[$i]) {
        case 'A':
          $_chapter = 10;
          break;
        case 'B':
          $_chapter = 11;
          break;
        case 'C':
          $_chapter = 13;
          break;
        case 'D':
          $_chapter = 14;
          break;
        case 'E':
          $_chapter = 15;
          break;
        case 'F':
          $_chapter = 16;
          break;
        default:
          $_chapter = $chapter_id[$i];
      }

      $where[] = $ds->prepare("`ARBO$i` = ?", $_chapter);
    }
  }
}
