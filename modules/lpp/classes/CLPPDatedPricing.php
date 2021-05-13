<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Lpp;

use Ox\Core\CMbDT;
use Ox\Core\CModelObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;

/**
 * Description
 */
class CLPPDatedPricing extends CModelObject {

  /** @var string The LPP code */
  public $code;

  /** @var string The date of effect */
  public $begin_date;

  /** @var string The date of the end of effect */
  public $end_date;

  /** @var string The prestation code to use */
  public $prestation_code;

  /** @var bool Indicate if a DEP must be asked */
  public $dep;

  /** @var string The date of government act */
  public $act_date;

  /** @var string The date of publication in the official journal */
  public $jo_date;

  /** @var bool Indicate if the price of the code must be on the quote */
  public $quote_pricing;

  /** @var float The price of the code */
  public $price;

  /** @var float The price increase for the DOM Guadeloupe */
  public $maj_guadeloupe;

  /** @var float The price increase for the DOM Martinique */
  public $maj_martinique;

  /** @var float The price increase for the DOM Guyane */
  public $maj_guyane;

  /** @var float The price increase for the DOM Reunion */
  public $maj_reunion;

  /** @var int The maximum authorized quantity */
  public $max_quantity;

  /** @var float The maximum authorized price */
  public $max_price;

  /** @var float The  amount */
  public $settled_price;

  /** @var int The PECP 1 (?) */
  public $pecp1;

  /** @var int The PECP 2 (?) */
  public $pecp2;

  /** @var int The PECP 3 (?) */
  public $pecp3;

  /** @var array A conversion table from the db fields to the object fields */
  public static $db_fields = array(
    'CODE_TIPS'  => 'code',
    'DEBUTVALID' => 'begin_date',
    'FINHISTO'   => 'end_date',
    'NAT_PREST'  => 'prestation_code',
    'ENTENTE'    => 'dep',
    'ARRETE'     => 'act_date',
    'JO'         => 'jo_date',
    'PUDEVIS'    => 'quote_pricing',
    'TARIF'      => 'price',
    'MAJO_DOM1'  => 'maj_guadeloupe',
    'MAJO_DOM2'  => 'maj_martinique',
    'MAJO_DOM3'  => 'maj_guyane',
    'MAJO_DOM4'  => 'maj_reunion',
    'MAJO_DOM5'  => 'maj_st_pierre_miquelon',
    'MAJO_DOM6'  => 'maj_mayotte',
    'QTE_MAX'    => 'max_quantity',
    'MT_MAX'     => 'max_price',
    'PUREGLEMEN' => 'settled_price',
    'PECP01'     => 'pecp1',
    'PECP02'     => 'pecp2',
    'PECP03'     => 'pecp3',
  );

  /**
   * CLPPDatedPricing constructor.
   *
   * @param array $data The data returned from the database
   */
  public function __construct($data = array()) {
    parent::__construct();

    foreach ($data as $_column => $_value) {
      $_field = self::$db_fields[$_column];

      if ($_field == 'dep') {
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

    $props['code']                   = 'num notNull';
    $props['begin_date']             = 'date notNull';
    $props['end_date']               = 'date';
    $props['prestation_code']        = 'str maxLength|3 notNull';
    $props['dep']                    = 'bool';
    $props['act_date']               = 'date';
    $props['jo_date']                = 'date';
    $props['quote_pricing']          = 'bool';
    $props['price']                  = 'currency notNull';
    $props['maj_guadeloupe']         = 'float';
    $props['maj_martinique']         = 'float';
    $props['maj_guyane']             = 'float';
    $props['maj_reunion']            = 'float';
    $props['maj_st_pierre_miquelon'] = 'float';
    $props['maj_mayotte']            = 'float';
    $props['max_quantity']           = 'num';
    $props['max_price']              = 'currency';
    $props['settled_price']          = 'currency';
    $props['pecp1']                  = 'num';
    $props['pecp2']                  = 'num';
    $props['pecp3']                  = 'num';

    return $props;
  }

  /**
   * Do not remove (loadRefModule() is called in the CModelObject but only declared in CStoredObject, and not in CModelObject)
   *
   * @return void
   */
  function loadRefModule() {
    return;
  }

  /**
   * @param string $code The code to load
   * @param string $date The date from which the pricing takes effect
   *
   * @return CLPPDatedPricing
   */
  public static function loadFromDate($code, $date = null) {
    if (!$date) {
      $date = CMbDT::date();
    }

    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('histo');
    $query->addWhere(
      array(
        $ds->prepare('`CODE_TIPS` = ?', $code),
        $ds->prepare('`DEBUTVALID` <= ?', $date),
        $ds->prepare('`FINHISTO` >= ? OR `FINHISTO` IS NULL', $date)
      )
    );
    $result = $ds->loadHash($query->makeSelect());

    if (!$result) {
      return false;
    }

    return new self($result);
  }

  /**
   * Load all the princings for the given LPP code
   *
   * @param string $code The code LPP
   *
   * @return CLPPDatedPricing[]
   */
  public static function loadFromCode($code) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('histo');
    $query->addWhere($ds->prepare('`CODE_TIPS` = ?', $code));
    $query->addOrder('`DEBUTVALID` DESC');
    $results = $ds->loadList($query->makeSelect());

    $pricings = array();
    if ($results) {
      foreach ($results as $_result) {
        $_pricing                        = new self($_result);
        $pricings[$_pricing->begin_date] = $_pricing;
      }
    }

    return $pricings;
  }

  /**
   * Load the last pricing entry for the given code
   *
   * @param string $code The code
   * @param string $date The date
   *
   * @return CLPPDatedPricing
   */
  public static function loadLast($code, $date = null) {
    $ds = CSQLDataSource::get('lpp');

    $query = new CRequest();
    $query->addSelect('*');
    $query->addTable('histo');

    $where   = array();
    $where[] = $ds->prepare('`CODE_TIPS` = ?', $code);
    if ($date) {
      $where[] = $ds->prepare('`DEBUTVALID` <= ? ', $date);
      $where[] = $ds->prepare('`FINHISTO` >= ?  OR `FINHISTO` IS NULL', $date);
    }

    $query->addWhere($where);
    $query->setLimit('0, 1');
    $query->addOrder('`DEBUTVALID` DESC');
    $result = $ds->loadHash($query->makeSelect());

    if (!$result) {
      return new self();
    }

    return new self($result);
  }
}
