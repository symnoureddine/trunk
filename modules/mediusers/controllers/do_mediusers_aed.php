<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CMbException;
use Ox\Core\CValue;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\System\CSourceSMTP;
use Ox\Mediboard\System\CUserAuthentication;

/**
 * Mediuser
 */
// we don't allow anybody to change his user type or profile
if (!CAppUI::$user->isAdmin() && !CModule::getCanDo("admin")->admin) {
    if ($_POST['_user_type'] == 1) {
        $_POST['_user_type'] = 14;
        unset($_POST['_profile_id']);
    }
    if ($_POST['user_id']) {
        unset($_POST['_profile_id']);
    }
}

// if user not itself, must have admin rights on module and edit rights on object
if (isset($_POST['user_id']) && $_POST['user_id']) {
    $mb_user = CMediusers::findOrFail($_POST['user_id']);

    if (CMediusers::get()->_id != $mb_user->_id) {
        CCanDo::checkAdmin();
        $mb_user->needsEdit();
    }
}

/**
 * Class CDoMediuserAddEdit
 */
class CDoMediuserAddEdit extends CDoObjectAddEdit
{
    /**
     * @inheritdoc
     */
    function __construct()
    {
        parent::__construct("CMediusers", "user_id");
    }

    /**
     * @inheritdoc
     */
    function doStore()
    {
        // keep track of former values for fieldModified below
        $obj = $this->_obj;
        $old = $obj->loadOldObject();

        $function_id_modified = $obj->fieldModified("function_id");

        if ($msg = $obj->store()) {
            CAppUI::setMsg($msg, UI_MSG_ERROR);
            if ($this->redirectError) {
                CAppUI::redirect($this->redirectError);
            }
        } else {
            // Keep trace for redirections
            CValue::setSession($this->objectKey, $obj->_id);

            // Reactivation
            if ($old->fin_activite && !$obj->fin_activite) {
                $auth                 = new CUserAuthentication();
                $auth->user_id        = $obj->_id;
                $auth->auth_method    = CUserAuthentication::AUTH_METHOD_REACTIVE;
                $auth->datetime_login = "now";
                //Obligatoire
                $auth->ip_address = $_SERVER['REMOTE_ADDR'];
                $auth->session_id = session_id();

                if ($msg = $auth->store()) {
                    CAppUI::setMsg($msg, UI_MSG_WARNING);
                }
            }

            // Insert new group and function permission
            if ($function_id_modified || !$old->_id) {
                if ($old->_id) {
                  $old->delFunctionPermission();
                  $old->delGroupPermission();
                }
                $obj->insFunctionPermission();
                $obj->insGroupPermission();
            }

            if (isset($this->request['_medecin_id']) && $this->request['_medecin_id']) {
                $medecin = new CMedecin();
                $medecin->load($this->request['_medecin_id']);
                $medecin->user_id = $obj->_id;
                if ($msg = $medecin->store()) {
                    CAppUI::setMsg($msg, UI_MSG_WARNING);
                }
            }

            // Message
            CAppUI::setMsg($old->_id ? $this->modifyMsg : $this->createMsg, UI_MSG_OK);

            $password = CValue::post('_user_password');
            if ($password && CValue::post('_send_notification')) {
                $obj->notifyAccount($password, CValue::post('_user_username'), CValue::post('_user_email'));
            }

            $duplicate       = CValue::post('_duplicate');
            $duplicate_login = CValue::post('_duplicate_username');
            if ($duplicate && $duplicate_login) {
                /** @var CMediusers $obj */
                $user = $obj->loadRefUser();

                if ($user && $user->_id) {
                    $user->_duplicate          = true;
                    $user->_duplicate_username = $duplicate_login;

                    if ($msg = $user->store()) {
                        CAppUI::setMsg($msg, UI_MSG_WARNING);
                    }
                }
            }

            $_secondary_user_id    = CValue::post('_secondary_user_id', []);
            $_secondary_user_adeli = CValue::post('_secondary_user_adeli', []);
            if (!empty($_secondary_user_id) && count($_secondary_user_id) == count($_secondary_user_adeli)) {
                foreach ($_secondary_user_id as $_index => $_user_id) {
                    $_user = CMediusers::get($_user_id);
                    if ($_user->_id && array_key_exists($_index, $_secondary_user_adeli)) {
                        $_user->adeli = $_secondary_user_adeli[$_index];

                        if ($msg = $_user->store()) {
                            CAppUI::setMsg($msg, UI_MSG_WARNING);
                        }
                    }
                }
            }

            // Redirection
            if ($this->redirectStore) {
                CAppUI::redirect($this->redirectStore);
            }
        }

        if (CValue::post('_send_token')) {
            $user = $this->_obj->loadRefUser();
            $url  = $user->generateActivationToken();

            /** @var $exchange_source CSourceSMTP */
            $exchange_source         = new CSourceSMTP();
            $exchange_source->name   = 'system-message';
            $exchange_source->active = 1;
            $exchange_source->loadMatchingObject();

            if (!$exchange_source->_id) {
                CAppUI::stepAjax("CExchangeSource.none", UI_MSG_ERROR);
            }

            try {
                $exchange_source->init();

                $exchange_source->addTo($user->user_email);
                $exchange_source->setSubject("Activation de votre compte");
                $exchange_source->setBody(
                    CAppUI::tr(
                        'CMediusers-msg-Hello To activate your account please click on the link below or copy / paste in your internet browser. %s This is an automatic email, please do not answer it.',
                        $url
                    )
                );

                $exchange_source->send();
                CAppUI::setMsg('common-msg-Notification send', UI_MSG_OK);
            } catch (phpmailerException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            } catch (CMbException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
    }
}

$do = new CDoMediuserAddEdit();
$do->doIt();
