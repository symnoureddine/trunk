<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CDiscipline;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSpecCPAM;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CSourceSMTP;

/**
 * Edit mediuser
 */
CCanDo::checkRead();

$user_id    = CValue::getOrSession("user_id");
$medecin_id = CValue::get('medecin_id');

// Récupération des fonctions
$group = CGroups::loadCurrent();
if ($group->_id) {
    $functions = $group->loadFunctions();

    // Liste des Etablissements
    $groups = CMediusers::loadEtablissements(PERM_READ);
} else {
    // Cas du admin qui n'a pas de mediuser, et donc pas de group_id
    $function = new CFunctions();

    $where     = [
        "actif" => "='1'",
    ];
    $functions = $function->loadListWithPerms(PERM_READ, $where);

    // Liste des Etablissements
    $group  = new CGroups();
    $groups = $group->loadList();
}

$functions = [];
foreach ($groups as $_group) {
    $functions[$_group->_id] = $_group->loadFunctions();
}

// Récupération du user à ajouter/editer
$object = new CMediusers();

if (CValue::get("no_association")) {
    $object->user_id = $user_id;
    $object->updateFormFields();
    $object->_user_id     = $user_id;
    $object->_id          = null;
    $object->actif        = CValue::get("ldap_user_actif", 1);
    $object->deb_activite = CValue::get("ldap_user_deb_activite");
    $object->fin_activite = CValue::get("ldap_user_fin_activite");
} else {
    $object->load($user_id);
    $object->loadRefFunction();
    $object->loadRefProfile();
}

if (!$object->_id && $medecin_id) {
    $medecin = new CMedecin();
    $medecin->load($medecin_id);
    if ($medecin->_id) {
        $split_name               = explode(' ', $medecin->nom);
        $object->_user_username   = substr($medecin->prenom, 0, 1) . $split_name[0];
        $object->_user_last_name  = $medecin->nom;
        $object->_user_first_name = $medecin->prenom;
        $object->_user_sexe       = $medecin->sexe;
        $object->function_id      = $medecin->function_id;
        $object->_user_email      = $medecin->email;
        $object->_user_phone      = $medecin->tel;
        $object->rpps             = $medecin->rpps;
        $object->mail_apicrypt    = $medecin->email_apicrypt;
        $object->mssante_address  = $medecin->mssante_address;
    }
}

$object->loadNamedFile("identite.jpg");
$object->loadNamedFile("signature.jpg");

$medecins_back = $object->loadBackRefs('medecin', 'nom, prenom, cp', 1);

// Savoir s'il est relié au LDAP
if (isset($object->_ref_user)) {
    $object->_ref_user->isLDAPLinked();
}

$object->loadRefMainUser();
$object->loadRefsSecondaryUsers();

// Récupération des disciplines
$discipline  = new CDiscipline();
$disciplines = $discipline->loadList();

// Récupération des profils
$profile           = new CUser();
$profile->template = 1;
/** @var CUser[] $profiles */
$profiles = $profile->loadMatchingList();

// Creation du tableau de profil en fonction du type
$tabProfil = [];
foreach ($profiles as $profil) {
    $tabProfil[$profil->user_type][] = $profil->_id;
}

$tag = false;
if ($object->_id) {
    $tag = CIdSante400::getMatch($object->_class, CMediusers::getTagSoftware(), null, $object->_id)->id400;
}

$password_spec = $object->_specs['_user_password'];
$description   = $password_spec->getLitteralDescription();
$description   = str_replace("'_user_username'", $object->_user_username, $description);
$description   = explode('. ', $description);
array_shift($description);
$description = array_filter($description);

$exchange_source         = new CSourceSMTP();
$exchange_source->name   = 'system-message';
$exchange_source->active = 1;
$exchange_source->loadMatchingObject();
$exchange_source = $exchange_source->_id;

CMbArray::naturalSort(CUser::$types);

$password_spec_builder  = $object->getPasswordSpecBuilder();
$weak_prop              = $password_spec_builder->getWeakSpec()->getProp();
$strong_prop            = $password_spec_builder->getStrongSpec()->getProp();
$ldap_prop              = $password_spec_builder->getLDAPSpec()->getProp();
$admin_prop             = $password_spec_builder->getAdminSpec()->getProp();
$password_configuration = $password_spec_builder->getConfiguration();

$smarty = new CSmartyDP();
$smarty->assign('weak_prop', $weak_prop);
$smarty->assign('strong_prop', $strong_prop);
$smarty->assign('ldap_prop', $ldap_prop);
$smarty->assign('admin_prop', $admin_prop);
$smarty->assign('password_configuration', $password_configuration);
$smarty->assign("tabProfil", $tabProfil);
$smarty->assign("utypes", CUser::$types);
$smarty->assign("ps_types", CUser::$ps_types);
$smarty->assign("object", $object);
$smarty->assign("profiles", $profiles);
$smarty->assign("disciplines", $disciplines);
$smarty->assign("spec_cpam", CSpecCPAM::getList());
$smarty->assign("tag_mediuser", CMediusers::getTagMediusers($group->_id));
$smarty->assign("is_admin", (CAppUI::$user->isAdmin() || CUser::get(CAppUI::$instance->user_id)->isSuperAdmin()));
$smarty->assign("is_admin_module", CModule::getCanDo("admin")->admin);
$smarty->assign("is_robot", $object->isRobot());
$smarty->assign("tag", $tag);
$smarty->assign("groups", $groups);
$smarty->assign("functions", $functions);
$smarty->assign("description", $description);
$smarty->assign("exchange_source", $exchange_source);
$smarty->assign("medecin_id", $medecin_id);
$smarty->assign("medecins_back", $medecins_back);
$smarty->display("inc_edit_mediuser");
