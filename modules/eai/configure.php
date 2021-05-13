<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * Configure
 */
CCanDo::checkAdmin();

$object_servers = array(
  "eai" => array(
    "CInteropActorHandler"
  ),
  "sip" => array(
    "CSipObjectHandler"
  ),
  "smp" => array(
    "CSmpObjectHandler"
  ),
  "sms" => array(
    "CSmsObjectHandler"
  ),
  "sa"  => array (
    "CSaObjectHandler",
    "CSaEventObjectHandler",
  )
);

$group = new CGroups();
$groups = $group->loadList();
foreach ($groups as $_group) {
  $_group->loadConfigValues(); 
  $_group->isIPPSupplier();
  $_group->isNDASupplier();
}      

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("object_servers", $object_servers);
$smarty->assign("groups"        , $groups);
$smarty->display("configure.tpl");

