<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\System\CAccessLog;
use Ox\Mediboard\System\CAccessLogArchive;
use Ox\Mediboard\System\CDataSourceLog;
use Ox\Mediboard\System\CDataSourceLogArchive;

CCanDo::checkAdmin();
CView::enforceSlave();

$objects = array(
  new CAccessLog(),
  new CAccessLogArchive(),
  new CDataSourceLog(),
  new CDataSourceLogArchive(),
);

$db = CAppUI::conf("db");
$db = $db["std"]["dbname"];

$ds = CSQLDataSource::get("std");

$stats = array();

/** @var CMbObject $_object */
foreach ($objects as $_object) {
  $_table = $_object->_spec->table;

  $query = "SELECT `aggregate`, COUNT(*) AS records, DATE(MIN(`period`)) AS date_min, DATE(MAX(`period`)) AS date_max
            FROM $_table
            GROUP BY `aggregate`
            ORDER BY `aggregate`";

  $stats[$_table] = array(
    'class' => $_object->_class,
    "data"  => $ds->loadList($query),
  );

  $query = "SELECT data_length, index_length, data_free
            FROM information_schema.TABLES
            WHERE `table_schema` = '$db'
              AND `table_name` = '$_table';";

  $meta          = $ds->loadHash($query);
  $meta["total"] = round($meta["data_length"] + $meta["index_length"], 2);

  $stats[$_table]["meta"] = $meta;
}

$smarty = new CSmartyDP();
$smarty->assign("stats", $stats);
$smarty->display("vw_aggregation_board.tpl");