<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Mediboard\Admin\CLDAP;
use Ox\Mediboard\Sante400\CIdSante400;

$id_ext               = new CIdSante400();
$id_ext->tag          = CAppUI::conf("admin LDAP ldap_tag");
$id_ext->object_class = "CUser";
$list                 = $id_ext->loadMatchingList();

if (count($list) == 0) {
  CAppUI::setMsg("Aucun identifiant à convertir");
}

$count = 0;

foreach ($list as $_id_ext) {
  if (strpos($_id_ext->id400, "-") !== false) {
    continue;
  }
  
  $count++;
  
  $_id_ext->id400 = CLDAP::convertHexaToRegistry($_id_ext->id400);

  if ($msg = $_id_ext->store()) {
    CAppUI::setMsg($msg, UI_MSG_WARNING);
  }
  else {
    CAppUI::setMsg("Identifiant converti");
  }
}

if ($count == 0) {
  CAppUI::setMsg("Aucun identifiant à convertir");
}

echo CAppUI::getMsg();