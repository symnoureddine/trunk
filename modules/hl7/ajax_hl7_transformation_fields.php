<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Interop\Hl7\CHL7v2Transformation;

CCanDo::checkAdmin();

$segment_name = CValue::get("segment_name");
$version      = CValue::get("version");
$extension    = CValue::get("extension");
$message      = CValue::get("message");
$profil       = CValue::get("profil");
$target       = CValue::get("target");

$trans         = new CHL7v2Transformation($version, $extension, $message);
$tree_fields   = $trans->getFieldsTree($segment_name);
$tree_segments = $trans->getSegments();

$smarty = new CSmartyDP();

$smarty->assign("profil"       , $profil);
$smarty->assign("version"      , $version);
$smarty->assign("extension"    , $extension);
$smarty->assign("message"      , $message);
$smarty->assign("tree_fields"  , $tree_fields);
$smarty->assign("tree_segments", $tree_segments);
$smarty->assign("target"       , $target);

$smarty->display("inc_hl7v2_transformation_fields.tpl");