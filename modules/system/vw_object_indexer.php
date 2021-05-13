<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Core\DSHM;

CCanDo::checkAdmin();
CView::checkin();

$index = "index-*-infos";

$prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf('root_dir'));
$keys = array_map(
  function ($v) use ($prefix) {
    return substr($v, strlen($prefix) + 1);
  },
  DSHM::listKeys($index)
);

$indexes_infos = array();
if (!empty($keys)) {
  $indexes_infos = DSHM::multipleGet($keys);
}

usort(
  $indexes_infos,
  function ($a, $b) {
    return strcmp($b['creation_datetime'], $a['creation_datetime']);
  }
);

$smarty = new CSmartyDP();
$smarty->assign('indexes_infos', $indexes_infos);
$smarty->display("vw_object_indexer");