<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkRead();

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");

CView::checkin();

// Chargement des fichiers
$object->loadRefsFiles();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("object", $object);
$smarty->assign("count_object", count($object->_ref_files));
$smarty->display("inc_vw_list_files");
