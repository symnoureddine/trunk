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

$user_username   = CValue::get("user_username");
$user_first_name = CValue::get("user_first_name");
$user_last_name  = CValue::get("user_last_name");

// LDAP filtering
$user_username   = CLDAP::escape($user_username);
$user_first_name = CLDAP::escape($user_first_name);
$user_last_name  = CLDAP::escape($user_last_name);

// Création du template
$smarty = new CSmartyDP();

if ($user_username || $user_first_name || $user_last_name) {
  try {
    $source_ldap = CLDAP::poolConnect(null, CGroups::loadCurrent()->_id);

    if (!$source_ldap || !$source_ldap->_ldapconn) {
      CAppUI::stepAjax('CSourceLDAP_all-unreachable', UI_MSG_ERROR);
    }

    $source_ldap->ldap_bind($source_ldap->_ldapconn, $source_ldap->user, $source_ldap->password, true);
  }
  catch (CMbException $e) {
    $e->stepAjax(UI_MSG_ERROR);
  }

  $choose_filter = "";
  if ($user_username) {
    $choose_filter = $source_ldap->isAlternativeBinding() ? "(cn={$user_username}*)" : "(samaccountname=$user_username*)";
  }
  if ($user_first_name) {
    $choose_filter .= "(givenname=$user_first_name*)";
  }
  if ($user_last_name) {
    $choose_filter .= "(sn=$user_last_name*)";
  }

  $filter = "(|$choose_filter)";
  $filter = utf8_encode($filter);

  try {
    $results = $source_ldap->ldap_search($source_ldap->_ldapconn, $filter);
  }
  catch (CMbException $e) {
    $e->stepAjax(UI_MSG_ERROR);
  }

  $nb_users = $results["count"];
  unset($results["count"]);

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

  $mediuser = new CMediusers();

  $smarty->assign("users", $users);
  $smarty->assign("mediuser", $mediuser);
  $smarty->assign("nb_users", $nb_users);
  $smarty->assign("givenname", CMbString::capitalize($user_first_name));
  $smarty->assign("sn", strtoupper($user_last_name));
  $smarty->assign("samaccountname", strtolower($user_username));
  $smarty->display("inc_search_user_ldap.tpl");
}
else {
  $smarty->display("inc_choose_filter_ldap.tpl");
}
