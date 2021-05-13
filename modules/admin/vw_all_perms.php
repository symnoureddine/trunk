<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CPermModule;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();
$users_id     = CView::get("users_ids", "str");
$profiles_ids = CView::get("profiles_ids", "str");
$only_profil  = CView::get("only_profil", "bool default|0");
$only_user    = CView::get("only_user", "bool default|0");
CView::checkin();
CView::enforceSlave();

if ($profiles_ids && $users_id) {
  $only_user = $only_profil = 0;
}
elseif ($profiles_ids && !$users_id) {
  $only_user = 1;
}
elseif (!$profiles_ids && $users_id) {
  $only_profil = 1;
}

// Liste des modules
$listModules = CModule::getActive();

// Matrice des droits
$perms = array(
  PERM_DENY => "interdit",
  PERM_READ => "lecture",
  PERM_EDIT => "ecriture"
);

$views = array(
  PERM_DENY => "caché",
  PERM_READ => "menu",
  PERM_EDIT => "administration"
);

$icons = array(
  PERM_DENY => "empty",
  PERM_READ => "read",
  PERM_EDIT => "edit"
);

$matriceProfil = array();
$profiles = array();

if (!$only_profil) {
  $where_profile = array();
  if (is_array($profiles_ids) && count($profiles_ids)) {
    $where_profile["user_id"] = CSQLDataSource::prepareIn(array_keys($profiles_ids));
  }

  $profiles = CUser::getProfiles($where_profile);

  $where = array("mod_id" => "IS NOT NULL");
  $whereGeneral = array("mod_id" => "IS NULL");

  foreach ($profiles as $_profile) {
    foreach ($listModules as $curr_mod) {
      $matriceProfil[$_profile->_id][$curr_mod->_id] = array(
        "text"     => $perms[PERM_DENY] . "/" . $views[PERM_DENY],
        "type"     => "général",
        "permIcon" => $icons[PERM_DENY],
        "viewIcon" => $icons[PERM_DENY],
      );
    }
  }

  $profilPermModule = new CPermModule();

  $whereGeneral["user_id"] = CSQLDataSource::prepareIn(array_keys($profiles));
  $where["user_id"]        = $whereGeneral["user_id"];
  $listProfilPermModules   = $profilPermModule->loadList($where);

  $profilPermModules = $profilPermModule->loadList($whereGeneral);

  foreach ($profilPermModules as $_perm_module) {
    $profilPermGeneralPermission = $_perm_module->permission;
    $profilPermGeneralView       = $_perm_module->view;
    foreach ($listModules as $_module) {
      $matriceProfil[$_perm_module->user_id][$_module->_id] = array(
        "text"     => $perms[$profilPermGeneralPermission] . "/" . $views[$profilPermGeneralView],
        "type"     => "général",
        "permIcon" => $icons[$profilPermGeneralPermission],
        "viewIcon" => $icons[$profilPermGeneralView],
      );
    }
  }

  foreach ($listProfilPermModules as $curr_perm) {
    $matriceProfil[$curr_perm->user_id][$curr_perm->mod_id] = array(
      "text"     => $perms[$curr_perm->permission] . "/" . $views[$curr_perm->view],
      "type"     => "spécifique",
      "permIcon" => $icons[$curr_perm->permission],
      "viewIcon" => $icons[$curr_perm->view],
    );
  }
}

$matrice = array();
$listFunctions = array();

if (!$only_user) {
  $where = array();
  $ljoin = array();

  if (is_array($users_id) && count($users_id)) {
    $ljoin = array(
      "users_mediboard" => "users_mediboard.function_id = functions_mediboard.function_id"
    );

    $where["users_mediboard.user_id"] = CSQLDataSource::prepareIn(array_keys($users_id));
  }

  // Liste des utilisateurs
  $listFunctions = CMediusers::loadFonctions(PERM_READ, null, null, null, $where, $ljoin);

  $where = array(
    "actif" => "= '1'"
  );

  if (is_array($users_id) && count($users_id)) {
    $where["users_mediboard.user_id"] = CSQLDataSource::prepareIn(array_keys($users_id));
  }

  $ljoin = array(
    "users" => "users.user_id = users_mediboard.user_id"
  );
  $order = "user_last_name, user_first_name";

  $users = CStoredObject::massLoadBackRefs($listFunctions, "users", $order, $where, $ljoin);

  CStoredObject::massLoadFwdRef($users, "_profile_id");

  foreach ($listFunctions as $curr_function) {
    foreach ($curr_function->loadRefsUsers() as $_user) {
      $_user->loadRefProfile();
    }
  }

  // Mapping profil - utilisateur
  $mapping_user_profile = array();

  foreach ($users as $_user) {
    $mapping_user_profile[$_user->_profile_id][] = $_user->_id;
  }

  foreach ($users as $curr_user) {
    foreach ($listModules as $_module) {
      $matrice[$curr_user->_id][$_module->_id] = array(
        "text"     => $perms[PERM_DENY] . "/" . $views[PERM_DENY],
        "type"     => "général",
        "permIcon" => $icons[PERM_DENY],
        "viewIcon" => $icons[PERM_DENY],
      );
    }
  }

  $where = array();

  $permModule = new CPermModule();

  $where["user_id"] = CSQLDataSource::prepareIn(CMbArray::pluck($users, "_id"));
  $listPermsModules = $permModule->loadList($where, "mod_id");

  $where["user_id"]       = CSQLDataSource::prepareIn(CMbArray::pluck($users, "_profile_id"));
  $listPermsModulesProfil = $permModule->loadList($where, "mod_id");

  foreach ($listPermsModulesProfil as $curr_perm) {
    foreach ($mapping_user_profile[$curr_perm->user_id] as $_user_id) {
      $_sub_perm = array(
        "text"     => $perms[$curr_perm->permission] . "/" . $views[$curr_perm->view],
        "type"     => "profil",
        "permIcon" => $icons[$curr_perm->permission],
        "viewIcon" => $icons[$curr_perm->view],
      );

      if ($curr_perm->mod_id) {
        $matrice[$_user_id][$curr_perm->mod_id] = $_sub_perm;
        continue;
      }

      foreach ($matrice[$_user_id] as $_mod_id => $_matrice) {
        $matrice[$_user_id][$_mod_id] = $_sub_perm;
      }
    }
  }

  foreach ($listPermsModules as $curr_perm) {
    $_sub_perm = array(
      "text"     => $perms[$curr_perm->permission] . "/" . $views[$curr_perm->view],
      "type"     => "spécifique",
      "permIcon" => $icons[$curr_perm->permission],
      "viewIcon" => $icons[$curr_perm->view],
    );

    if ($curr_perm->mod_id) {
      $matrice[$curr_perm->user_id][$curr_perm->mod_id] = $_sub_perm;
      continue;
    }

    foreach ($matrice[$_user_id] as $_mod_id => $_matrice) {
      $matrice[$_user_id][$_mod_id] = $_sub_perm;
    }
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("listModules"  , $listModules);
$smarty->assign("listFunctions", $listFunctions);
$smarty->assign("users_ids"    , $users_id);
$smarty->assign("matrice"      , $matrice);
$smarty->assign("profiles_ids" , $profiles_ids);
$smarty->assign("profiles"     , $profiles);
$smarty->assign("matriceProfil", $matriceProfil);
$smarty->assign("only_profil"  , $only_profil);
$smarty->assign("only_user"    , $only_user);

$smarty->display("vw_all_perms");
