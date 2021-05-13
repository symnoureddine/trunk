<?php
/**
 * @package Mediboard\system
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Controllers\Legacy;


use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLegacyController;
use Ox\Core\CMbPath;
use Ox\Core\CValue;
use Ox\Core\CViewHistory;
use Ox\Core\Module\CModule;
use Ox\Core\ResourceLoaders\CCSSLoader;
use Ox\Core\ResourceLoaders\CFaviconLoader;
use Ox\Core\ResourceLoaders\CJSLoader;
use Ox\Core\SHM;
use Ox\Mediboard\Admin\CKerberosLdapIdentifier;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CMessage;
use Ox\Mediboard\System\CMessageAcquittement;
use Smarty;

class CMainController extends CLegacyController
{

    public function header()
    {
        global $g, $f, $m, $a;

        // Current user
        $user = CAppUI::$user;

        // Liste des Etablissements
        $etablissements = CMediusers::loadEtablissements(PERM_EDIT);

        // Liste des fonctions secondaires
        $secondary_functions = $user->loadRefsSecondaryFunctionsByGroup();

        // Retour à la fonction principale si non trouvée dans les fonctions secondaires de l'établissement courant
        if (isset($secondary_functions[$g])) {
            $function_found = false;
            foreach ($secondary_functions[$g] as $_secondary_function) {
                if ($_secondary_function->_id === $f) {
                    $function_found = true;
                    break;
                }
            }
            if (!$function_found) {
                $first_func = reset($secondary_functions[$g]);
                $f          = $user->_ref_function->group_id === $g ? $user->function_id : $first_func->_id;
            }
        } else {
            $f = $user->function_id;
        }

        //current Group
        $current_group = CGroups::loadCurrent();

        // Messages
        $messages = new CMessage();
        $messages = $messages->loadPublications("present", $user->_id, $m, $g);

        $messagerie = CAppUI::getMessagerieInfo();

        // Porte documents
        $porteDocuments = CAppUI::getPorteDocumentsInfo();

        // AppFine
        $appFine = CAppUI::getAppFineInfo();

        // OxChatClient (Messagerie instantanée)
        $oxChatClient = CAppUI::getOxChatClientInfo();

        // Assistance
        $assistance = CAppUI::getAssitance();

        // Creation du Template
        $uistyle = CAppUI::MEDIBOARD_EXT_THEME;

        // vars
        $tpl_vars = [
            "offline"            => false,
            "nodebug"            => true,
            "obsolete_module"    => CModule::getObsolete($m, $a),
            "localeInfo"         => CAppUI::$locale_info,
            "mediboardShortIcon" => CFaviconLoader::loadFile("style/$uistyle/images/icons/favicon.ico"),
            "mediboardStyle"     => CCSSLoader::loadAllFiles(),
            "mediboardScript"    => CJSLoader::loadAllFiles(),
            "dialog"             => CAppUI::$dialog,
            "messages"           => $messages,
            "acquittal"          => new CMessageAcquittement(),
            "messagerie"         => $messagerie,
            "porteDocuments"     => $porteDocuments,
            "appFine"            => $appFine,
            "oxChatClient"       => $oxChatClient,
            "assistance"         => $assistance,
            "uistyle"            => $uistyle,
            "cp_group"           => $current_group->cp,
            "errorMessage"       => CAppUI::getMsg(),
            "Etablissements"     => $etablissements,
            "SecondaryFunctions" => $secondary_functions,
            "applicationVersion" => CApp::getReleaseInfo(),
            "allInOne"           => CValue::get("_aio"),
            "auth_method"        => CAppUI::$instance->auth_method,
        ];

        $this->renderSmarty("header", $tpl_vars, "style/$uistyle");
    }

    public function moduleInactive()
    {
        $this->renderSmarty("module_inactive", [], "modules/system");
    }

    public function viewInfo($props, $params){
        $this->renderSmarty("view_info", [
            "props" => $props,
            "params" => $params
        ], "modules/system");
    }

    public function footer()
    {
        global $m, $action;
        $user = CAppUI::$user;
        if ($infosystem = CAppUI::pref("INFOSYSTEM")) {
            $latest_cache_key = "$user->_guid-latest_cache";
            $latest_cache     = [
                "meta"   => [
                    "module" => $m,
                    "action" => $action,
                    "user"   => $user->_view,
                ],
                "totals" => Cache::$totals,
                "hits"   => Cache::$hits,
            ];

            SHM::put($latest_cache_key, $latest_cache, true);
        }

        $tpl_vars = [
            "offline"            => false,
            "infosystem"         => $infosystem,
            "performance"        => CApp::$performance,
            "show_performance"   => CAppUI::pref("show_performance"),
            "errorMessage"       => CAppUI::getMsg(),
            "navigatory_history" => CViewHistory::getHistory(),
            "multi_tab_msg_read" => CAppUI::isMultiTabMessageRead(),
        ];

        $this->renderSmarty('footer', $tpl_vars, "style/" . CAppUI::MEDIBOARD_EXT_THEME);
    }

    public function login()
    {
        $style    = CAppUI::MEDIBOARD_EXT_THEME;
        $redirect = CValue::get("logout") ? "" : CValue::read($_SERVER, "QUERY_STRING");
        $tpl_vars = [
            "localeInfo"         => CAppUI::$locale_info,
            "mediboardShortIcon" => CFaviconLoader::loadFile("style/{$style}/images/icons/favicon.ico"),
            "mediboardStyle"     => CCSSLoader::loadAllFiles(),
            "mediboardScript"    => CJSLoader::loadAllFiles(false),
            "errorMessage"       => CAppUI::getMsg(),
            "time"               => time(),
            "redirect"           => $redirect,
            "uistyle"            => $style,
            "nodebug"            => true,
            "offline"            => false,
            "allInOne"           => CValue::get("_aio"),
            "applicationVersion" => CApp::getReleaseInfo(),
            "kerberos_button"    => CKerberosLdapIdentifier::isLoginButtonEnabled(),
        ];

        $this->renderSmarty('login', $tpl_vars, "style/{$style}");
    }

    public function ajaxErrors()
    {
        $this->renderSmarty(
            'ajax_errors',
            [
                "performance"      => CApp::$performance,
                "show_performance" => CAppUI::pref("show_performance"),
                "requestID"        => CValue::get("__requestID"),
            ],
            'modules/system'
        );
    }

    public function unlocalized()
    {
        $this->renderSmarty("inc_unlocalized_strings", [], 'modules/system');
    }

    public function tabboxOpen($tabs, $tab)
    {
        $this->renderSmarty(
            'tabbox',
            [
                'tabs'         => $tabs,
                'tab'          => $tab,
                'statics_tabs' => CModule::TABS,
            ],
            'style/' . CAppUI::MEDIBOARD_EXT_THEME
        );
    }

    public function tabboxClose()
    {
        echo '</div></div>';
    }

}
