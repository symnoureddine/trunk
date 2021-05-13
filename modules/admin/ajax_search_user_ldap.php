<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CLDAP;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Sante400\CIdSante400;

CCanDo::checkRead();

$object_id = CValue::get("object_id");

$mediuser = new CMediusers();
$mediuser->load($object_id);
$user = $mediuser->_ref_user;

try {
  $source_ldap = CLDAP::poolConnect(null, CGroups::loadCurrent()->_id);

  if (!$source_ldap || !$source_ldap->_ldapconn) {
    CAppUI::stepAjax('CSourceLDAP_all-unreachable', UI_MSG_ERROR);
  }

  // Ne pas mettre de retours chariots
  $filter = "(|(givenname=" . CLDAP::escape($mediuser->_user_first_name) . "*)(sn=" . CLDAP::escape($mediuser->_user_last_name) . "*)(samaccountname=" . CLDAP::escape($mediuser->_user_username) . "*))";

  if ($source_ldap->isAlternativeBinding()) {
    $filter = "(|(givenname=" . CLDAP::escape($mediuser->_user_first_name) . "*)(sn=" . CLDAP::escape($mediuser->_user_last_name) . "*)(cn=" . CLDAP::escape($mediuser->_user_username) . "*))";
  }

  $filter = utf8_encode($filter);

  $source_ldap->ldap_bind($source_ldap->_ldapconn, $source_ldap->user, $source_ldap->password, true);

  $results = $source_ldap->ldap_search($source_ldap->_ldapconn, $filter);
}
catch (CMbException $e) {
  $e->stepAjax(UI_MSG_ERROR);
}

$nb_users = 0;
if (is_array($results)) {
  $nb_users = $results["count"];
  unset($results["count"]);
}

$users = array();
foreach ($results as $key => $_result) {
  $objectguid                     = CLDAP::getObjectGUID($_result, $source_ldap);
  $users[$key]["objectguid"]      = $objectguid;
  $users[$key]["user_username"]   = $source_ldap->isAlternativeBinding() ? CLDAP::getValue($_result, 'cn') : CLDAP::getValue($_result, "samaccountname");
  $users[$key]["user_first_name"] = CLDAP::getValue($_result, "givenname");
  $users[$key]["user_last_name"]  = CLDAP::getValue($_result, "sn");
  $users[$key]["actif"]           = (CLDAP::getValue($_result, 'useraccountcontrol') & 2) ? 0 : 1;

  $idex               = new CIdSante400();
  $idex->tag          = CAppUI::conf("admin LDAP ldap_tag");
  $idex->id400        = $objectguid;
  $idex->object_class = "CUser";
  $idex->loadMatchingObject();

  $users[$key]["associate"] = $idex->_id ? $idex->object_id : null;
}

$smarty = new CSmartyDP();
$smarty->assign("users", $users);
$smarty->assign("mediuser", $mediuser);
$smarty->assign("nb_users", $nb_users);
$smarty->assign("givenname", CMbString::capitalize($mediuser->_user_first_name));
$smarty->assign("sn", strtoupper($mediuser->_user_last_name));
$smarty->assign("samaccountname", strtolower($mediuser->_user_username));
$smarty->assign("close_modal", '1');
$smarty->display("inc_search_user_ldap.tpl");
