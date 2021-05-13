<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use Ox\Core\CRequest;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSecondaryFunction;

/**
 * Autocomplete des modèles
 */
CCanDo::checkRead();

$user_id       = CView::get("user_id", "ref class|CMediusers");
$function_id   = CView::get("function_id", "ref class|CFunctions");
$object_class  = CView::get("object_class", "str");
$object_id     = CView::get("object_id", "num");
$keywords      = CView::get("keywords_modele", "str");
$fast_edit     = CView::get("fast_edit", "bool default|1");
$mode_store    = CView::get("mode_store", "bool default|1");
$modele_vierge = CView::get("modele_vierge", "bool default|1");
$type          = CView::get("type", "str");
$appFine       = CView::get("appFine", "bool default|0");

CView::checkin();

CView::enableSlave();

$compte_rendu = new CCompteRendu();

$modeles = array();
$favoris = array();

$where = array(
  "actif" => "= '1'"
);

if (!$fast_edit) {
  $where["fast_edit"]     = " = '0'";
  $where["fast_edit_pdf"] = " = '0'";
}

if ($mode_store) {
  $where["type"] = "= 'body'";
}
elseif ($type) {
  $where["type"] = "= '$type'";
}

if ($object_class) {
  $where["object_class"] = "= '$object_class'";
}

$module     = CModule::getActive("dPcompteRendu");
$is_admin   = $module && $module->canAdmin();
$is_cabinet = CAppUI::isCabinet();

// Niveau utilisateur
if ($user_id) {
  $user = CMediusers::get($user_id);
  $user->loadRefFunction();

  $users_ids = array($user->_id);

  $curr_user = CMediusers::get();

  if ($mode_store) {
    $users_ids[] = $curr_user->_id;
  }

  if ($object_class) {
    switch ($object_class) {
      default:
        break;
      case "COperation":
        $object = new $object_class;
        $object->load($object_id);
        $fields_chir = array("chir_id", "chir_2_id", "chir_3_id", "chir_4_id");

        foreach ($fields_chir as $_field_chir) {
          if ($object->$_field_chir) {
            $users_ids[] = $object->$_field_chir;
          }
        }
    }
  }

  $users_ids = array_unique($users_ids);

  foreach ($users_ids as $key => $_user_id) {
    if ($_user_id == $curr_user->_id) {
      continue;
    }
    $_user = CMediusers::get($_user_id);
    if (!$_user->getPerm(PERM_EDIT)) {
      unset($users_ids[$key]);
    }
  }

  $where["user_id"] = CSQLDataSource::prepareIn($users_ids);

  $modeles = $compte_rendu->seek($keywords, $where, 100, false, null, "nom");

  if (CAppUI::pref("show_favorites")) {
    $ds = CSQLDataSource::get('std');
    $where_favoris = $where;
    unset($where_favoris["user_id"]);
    $where_favoris["author_id"] = "= '".CMediusers::get()->_id."'";
    $request = new CRequest(false);
    $request->addSelect(array("modele_id", "COUNT(*)"));
    $request->addTable("compte_rendu");
    $request->addWhere($where_favoris);
    $request->addGroup("modele_id");
    $request->addOrder("COUNT(*) DESC");
    $request->setLimit("10");
    $favoris = $ds->loadList($request->makeSelect());
  }
}

// Niveau fonction
// Inclusion des fonctions secondaires de l'utilisateur connecté
// et de l'utilisateur concerné
unset($where["user_id"]);

$function_ids = array();

if ($is_admin || !$is_cabinet || CAppUI::gconf("dPcompteRendu CCompteRenduAcces access_function")) {
  if (CModule::getActive('appFineClient') && $appFine && $is_admin) {
    $function_appFine = new CFunctions();
    $ds = $function_appFine->getDS();
    $where_appFine = array();
    $where_appFine['group_id'] = $ds->prepare('= ?', CGroups::loadCurrent()->_id);
    $function_ids = $function_appFine->loadIds($where_appFine);
  }
  elseif ($user_id) {
    $sec_function = new CSecondaryFunction();
    $whereSecFunc = array();
    $whereSecFunc["user_id"] = " = '$curr_user->_id'";
    if ($user->getPerm(PERM_EDIT)) {
      $whereSecFunc["user_id"] = "IN ('$user->_id', '$curr_user->_id')";
    }

    $function_sec = $sec_function->loadList($whereSecFunc);
    $function_ids = array_merge(CMbArray::pluck($function_sec, "function_id"), array($user->function_id, $curr_user->function_id));
  }
  else {
    $function_ids = array($function_id);
  }

  $where["function_id"] = CSQLDataSource::prepareIn($function_ids);
  $modeles = array_merge($modeles, $compte_rendu->seek($keywords, $where, 100, false, null, "nom"));
}

// Niveau établissement
// Inclusion de l'établissement courant si différent de l'établissement de l'utilisateur
if ($is_admin || !$is_cabinet || CAppUI::gconf("dPcompteRendu CCompteRenduAcces access_group")) {
  unset($where["function_id"]);

  if ($function_id && !$user_id) {
    $function = new CFunctions();
    $function->load($function_id);
    $where["group_id"] = "= '$function->group_id'";
  }
  else {
    $where["group_id"] = CSQLDataSource::prepareIn($user->_group_id, CGroups::loadCurrent()->_id);
  }

  $modeles = array_merge($modeles, $compte_rendu->seek($keywords, $where, 100, false, null, "nom"));

  $modeles = CModelObject::naturalSort($modeles, array("nom"), true);
}

// Niveau instance
unset($where["group_id"]);

$where[] = "user_id IS NULL AND function_id IS NULL AND group_id IS NULL AND object_id IS NULL";

$modeles = array_merge($modeles, $compte_rendu->seek($keywords, $where, 100, false, null, "nom"));

if (CModule::getActive('appFineClient') && $appFine && $is_admin) {
  foreach ($modeles as $_modele) {
    if (!$_modele->function_id) {
      continue;
    }
    $_modele->loadRefFunction();
  }
}

$modeles = CModelObject::naturalSort($modeles, array("nom"), true);

if ($favoris) {
  $modeles_with_favorites = array();
  foreach ($favoris as $_modele) {
    if (array_key_exists($_modele["modele_id"], $modeles)) {
      $modeles_with_favorites[$_modele["modele_id"]] = $modeles[$_modele["modele_id"]];
      $modeles_with_favorites[$_modele["modele_id"]]->_utilisations = $_modele["COUNT(*)"];
    }
  }
  foreach ($modeles as $_id => $_modele) {
    if (!isset($modeles_with_favorites[$_id])) {
      $modeles_with_favorites[$_id] = $_modele;
    }
  }

  $modeles = $modeles_with_favorites;
}

$smarty = new CSmartyDP();

$smarty->assign("modeles" , $modeles);
$smarty->assign("nodebug" , true);
$smarty->assign("keywords", $keywords);
$smarty->assign("modele_vierge", $modele_vierge);
$smarty->assign("appFine", $appFine);

$smarty->display("inc_modele_autocomplete");
