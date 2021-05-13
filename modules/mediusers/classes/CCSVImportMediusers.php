<?php

/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\Import\CMbCSVObjectImport;
use Ox\Core\CMbString;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Interop\Eai\CSpecialtyAsip;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CAffectationUfSecondaire;
use Ox\Mediboard\Hospi\CAffectationUniteFonctionnelle;
use Ox\Mediboard\Hospi\CUniteFonctionnelle;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Description
 */
class CCSVImportMediusers extends CMbCSVObjectImport
{
    /** @var array */
    protected $results = [];

    /** @var array */
    protected $unfound = [];

    /** @var int */
    protected $dryrun;

    /** @var int */
    protected $update;

    /** @var array*/
    protected $line;

    /** @var string[] */
    public const HEADERS = [
        'nom',
        'prenom',
        'username',
        'password',
        'type',
        'fonction',
        'profil',
        'adeli',
        'rpps',
        'spec_cpam',
        'discipline',
        'activite',
        'idex',
        'acces_local',
        'actif',
        'ufm',
        'main_user',
        'secteur',
        'pratique_tarifaire',
        'ccam_context',
        'num_astreinte',
        'num_astreinte_autre',
        'ufsecondaire',
        'code_asip',
        'astreinte',
        'commentaires',
        'cps',
        'mail_apicrypt',
        'mssante_address',
        'sexe',
        'force_change_pw',
        'initials',
        'user_mail',
        'user_phone',
        'internal_phone',
    ];

    /**
     * @inheritdoc
     */
    public function __construct(
        $file_path,
        $dryrun,
        $update,
        $start = 0,
        $step = 100,
        $profile = CCSVFile::PROFILE_EXCEL
    ) {
        parent::__construct($file_path, $start, $step, $profile);
        $this->dryrun = $dryrun;
        $this->update = $update;
    }

    /**
     * @inheritdoc
     */
    function import()
    {
        $this->openFile();
        $this->setColumnNames();

        $this->current_line = 0;
        while ($this->line = $this->readAndSanitizeLine()) {
            $this->current_line++;

            if (!$this->line['nom']) {
                CAppUI::setMsg('mediusers-import-lastname-mandatory-line%d', UI_MSG_WARNING, $this->current_line);
                continue;
            }

            $this->results[$this->current_line] = $this->line;

            $user = $this->getUser();

            $mediuser = null;
            if ($user->_id) {
                $mediuser = $user->loadRefMediuser();
            }

            $function = $this->getFunction();
            if (!$mediuser || !$mediuser->_id) {
                $mediuser = $this->getNewMediuser($user, $function);
                if (!trim($this->line['password'])) {
                    $this->results[$this->current_line]['error']
                        = CAppUI::tr('CUser-Error-Password is mandatory for a new user');
                }
            } elseif ($function && $function->_id && $this->update) {
                $mediuser->function_id = $function->_id;
            }

            if ($mediuser->_user_type !== $user->user_type && $this->update && $user->user_type) {
                $mediuser->_user_type = $user->user_type;
            }

            if (!is_numeric($mediuser->_user_type) || !array_key_exists($mediuser->_user_type, CUser::$types)) {
                $this->unfound["user_type"][$mediuser->_user_type] = true;
            }

            if (CAppUI::conf("ref_pays") != 2) {
                $mediuser->adeli
                    = (($this->update || !$mediuser->adeli) && $this->line['adeli']) ? $this->line['adeli'] : null;
                $mediuser->rpps
                    = (($this->update || !$mediuser->rpps) && $this->line['rpps']) ? $this->line['rpps'] : null;
            } else {
                $mediuser->ean
                    = (($this->update || !$mediuser->ean) && $this->line['adeli']) ? $this->line['adeli'] : null;
            }

            if ($mediuser->_id && (!$this->update || $this->dryrun)) {
                $this->results[$this->current_line]['found'] = true;
                if (!$this->dryrun) {
                    continue;
                }
            }

            $mediuser->activite
                = (isset($this->line['activite']) && $this->line['activite'] !== '') ? $this->line['activite'] : null;

            $mediuser->actif
                              = (isset($this->line['actif']) && $this->line['actif'] !== '') ? $this->line['actif'] : null;
            $mediuser->remote = (isset($this->line['acces_local']) && $this->line['acces_local'] !== '')
                ? $this->line['acces_local'] : null;

            $mediuser = $this->setMainUser($mediuser);

            // Password
            if (!$mediuser->_id) {
                if (!trim($this->line['password'])) {
                    $msg = CAppUI::tr('CUser-Error-Password is mandatory for a new user');
                    CAppUI::setMsg($this->current_line . " : " . $msg, UI_MSG_WARNING);
                    $this->results[$this->current_line]["error"] = $msg;
                    continue;
                }

                // On force la regénération du mot de passe
                if (CAppUI::conf("instance_role") == "prod" && !CAppUI::conf("admin LDAP ldap_connection")) {
                    $mediuser->_force_change_password = true;
                }

                $user_username = $mediuser->_user_username;
                $mediuser->makeUsernamePassword($this->line["prenom"], $this->line["nom"]);
                $mediuser->_user_username = $user_username;
            }

            if (trim($this->line["password"])) {
                $mediuser->_user_password = trim($this->line["password"]);
            } else {
                $mediuser->_user_password = null;
            }

            //Profil
            if ($profile_name = $this->line['profil']) {
                $mediuser = $this->checkProfile($mediuser, $profile_name);
            }

            if ($spec_cpam_code = $this->line['spec_cpam']) {
                $mediuser = $this->getSpecCPAM($mediuser, $spec_cpam_code);
            }

            if ($discipline_name = $this->line["discipline"]) {
                $mediuser = $this->getDiscipline($mediuser, $discipline_name);
            }

            if ($this->line["secteur"]) {
                $mediuser->secteur = trim($this->line['secteur']);
            }

            if ($this->line["pratique_tarifaire"]) {
                $mediuser->pratique_tarifaire = trim($this->line['pratique_tarifaire']);
            }

            if ($this->line["ccam_context"]) {
                $mediuser->ccam_context = trim($this->line['ccam_context']);
            }

            if ($this->line["num_astreinte"]) {
                $mediuser->_user_astreinte = trim($this->line["num_astreinte"]);
            }

            if ($this->line["num_astreinte_autre"]) {
                $mediuser->_user_astreinte_autre = trim($this->line["num_astreinte_autre"]);
            }

            if ($this->line["code_asip"]) {
                $asip_code       = new CSpecialtyAsip();
                $asip_code->code = trim($this->line["code_asip"]);
                $asip_code->loadMatchingObjectEsc();

                if ($asip_code->_id) {
                    $mediuser->other_specialty_id = $asip_code->_id;
                }
            }

            if ($this->line["astreinte"]) {
                $mediuser->astreinte = trim($this->line["astreinte"]);
            }

            if ($this->line["commentaires"]) {
                $mediuser->commentaires = trim($this->line["commentaires"]);
            }

            if ($this->line["cps"]) {
                $mediuser->cps = trim($this->line["cps"]);
            }

            if ($this->line["mail_apicrypt"]) {
                $mediuser->mail_apicrypt = trim($this->line["mail_apicrypt"]);
            }

            if ($this->line["mssante_address"]) {
                $mediuser->mssante_address = trim($this->line["mssante_address"]);
            }

            if ($this->line["sexe"]) {
                $mediuser->_user_sexe = trim($this->line["sexe"]);
            }

            if ($this->line["force_change_pw"]) {
                $mediuser->_force_change_password = trim($this->line["force_change_pw"]);
            }

            if ($this->line["initials"]) {
                $mediuser->initials = trim($this->line["initials"]);
            }

            if ($this->line["user_mail"]) {
                $mediuser->_user_email = trim($this->line["user_mail"]);
            }

            if ($this->line["user_phone"]) {
                $mediuser->_user_phone = trim($this->line["user_phone"]);
            }

            if ($this->line["internal_phone"]) {
                $mediuser->_internal_phone = trim($this->line["internal_phone"]);
            }

            if ($this->dryrun) {
                continue;
            }

            $mediuser->unescapeValues();

            $new = $mediuser->_id ? false : true;

            if (!$new && !$this->update) {
                continue;
            }

            if ($msg = $mediuser->store()) {
                CAppUI::setMsg($this->current_line . " : " . $msg, UI_MSG_WARNING);
                $this->results[$this->current_line]["error"] = $msg;

                continue;
            }

            $new ? CAppUI::setMsg("CMediusers-msg-create", UI_MSG_OK) : CAppUI::setMsg(
                "CMediusers-msg-modify",
                UI_MSG_OK
            );

            $mediuser->insFunctionPermission();
            $mediuser->insGroupPermission();
            $this->results[$this->current_line]["result"]   = 0;
            $this->results[$this->current_line]["username"] = $mediuser->_user_username;
            $this->results[$this->current_line]["password"] = $mediuser->_user_password;

            $group_id = CGroups::loadCurrent()->_id;

            if (isset($this->line['ufm']) && ($ufms = $this->line['ufm'])) {
                $this->getUFMs($ufms, $group_id, $mediuser);
            }

            if ($this->line['idex']) {
                $this->addIdex($mediuser, $group_id);
            }

            if ($this->line['ufsecondaire']) {
                $this->addSecondaryUf($mediuser, $group_id);
            }
        }

        $this->csv->close();
    }

    /**
     * @throws Exception
     */
    protected function addIdex(CMediusers $mediuser, int $group_id): void
    {
        $all_idex = explode(',', $this->line['idex']);
        foreach ($all_idex as $_idex) {
            $idex_parts = explode('|', $_idex);

            $idex               = new CIdSante400();
            $idex->object_class = $mediuser->_class;
            $idex->object_id    = $mediuser->_id;
            $idex->id400        = $idex_parts[0];

            $idex->tag = CMediusers::getTagMediusers($group_id);
            if (isset($idex_parts[1])) {
                $idex->tag = $idex_parts[1];
            }

            $idex->loadMatchingObjectEsc();

            if ($idex->_id) {
                $this->unfound["idex"][$_idex] = true;
                continue;
            } elseif ($msg = $idex->store()) {
                CAppUI::setMsg($this->current_line . " : " . $msg, UI_MSG_WARNING);
            }
        }
    }

    /**
     * @param CMediusers $mediuser
     * @param int        $group_id
     *
     * @throws Exception
     */
    protected function addSecondaryUf($mediuser, $group_id): void
    {
        $all_ufs = explode('|', $this->line['ufsecondaire']);

        foreach ($all_ufs as $_uf) {
            $uf           = new CUniteFonctionnelle();
            $uf->group_id = $group_id;
            $uf->code     = $_uf;

            $uf->loadMatchingObjectEsc();

            if (!$uf || !$uf->_id) {
                CAppUI::setMsg($this->current_line . " : L'unité fonctionnelle $_uf n'existe pas.", UI_MSG_WARNING);
                continue;
            }

            $affectation = new CAffectationUfSecondaire();
            $affectation->setObject($mediuser);
            $affectation->uf_id = $uf->_id;
            $affectation->loadMatchingObjectEsc();

            if (!$affectation->_id) {
                if ($msg = $affectation->store()) {
                    CAppUI::setMsg($this->current_line . ' : ' . $msg, UI_MSG_WARNING);
                } else {
                    CAppUI::setMsg("CAffectationUfSecondaire-msg-create", UI_MSG_OK);
                }
            } else {
                CAppUI::setMsg("CAffectationUfSecondaire-msg-found", UI_MSG_OK);
            }
        }
    }

    /**
     * @param CMediusers $mediuser Mediuser
     *
     * @return CMediusers
     */
    public function setMainUser($mediuser)
    {
        if (isset($this->line['main_user']) && $this->line['main_user']) {
            $main_user                = new CUser();
            $main_user->user_username = $this->line['main_user'];
            $main_user->loadMatchingObjectEsc();

            if ($main_user && $main_user->_id) {
                $mediuser->main_user_id = $main_user->_id;
            }
        }

        return $mediuser;
    }

    /**
     * Get a CUser from CSV
     *
     * @return CUser
     */
    public function getUser()
    {
        $user                  = new CUser();
        $user->user_last_name  = ($this->line['nom']) ?: null;
        $user->user_first_name = ($this->line['prenom']) ?: null;

        if (!$user->user_last_name && !$user->user_first_name) {
            return $user;
        }

        $user->user_username =
            $this->line['username'] ?: CMbString::lower(substr($user->user_first_name, 0, 1) . $user->user_last_name);

        $user->loadMatchingObjectEsc();

        if (!$user->user_type || ($this->update && $this->line['type'])) {
            $current_user = CUser::get();

            // Do not allow to put an admin user if current user is not admin
            if ($this->line['type'] == 1 && $current_user->user_type !== '1') {
                $user->user_type = 14;
            } else {
                $user->user_type = $this->line['type'];
            }
        }

        return $user;
    }

    /**
     * Get a new CMediusers from CSV
     *
     * @param CUser      $user     User from CSV
     * @param CFunctions $function Function of the mediuser
     *
     * @return CMediusers
     */
    public function getNewMediuser($user, $function = null)
    {
        $mediuser              = new CMediusers();
        $mediuser->function_id = ($function) ? $function->_id : null;

        if (!$user->_id) {
            $mediuser->_user_last_name  = $user->user_last_name;
            $mediuser->_user_first_name = $user->user_first_name;
            $mediuser->_user_username   = $user->user_username;
            $mediuser->_user_type       = $user->user_type;
        } else {
            $mediuser->user_id        = $user->_id;
            $mediuser->_user_username = $user->user_username;
        }

        return $mediuser;
    }

    /**
     * Set the discipline field to a mediuser
     *
     * @param CMediusers $mediuser        Mediuser to set discipline
     * @param string     $discipline_name Discipline to set
     *
     * @return CMediusers
     */
    public function getDiscipline($mediuser, $discipline_name)
    {
        $discipline       = new CDiscipline();
        $discipline->text = strtoupper($discipline_name);
        $discipline->loadMatchingObject();
        if ($discipline->_id) {
            $mediuser->discipline_id = $discipline->_id;
        } else {
            $this->unfound["discipline_name"][$discipline_name] = true;
        }

        return $mediuser;
    }

    /**
     * Import CAffectationUniteFonctionnelle
     *
     * @param string     $ufms     CUniteFonctionnelle-code separated by |
     * @param int        $group_id Group id
     * @param CMediusers $mediuser Mediuser to link to the ufm
     *
     * @return void
     */
    public function getUFMs($ufms, $group_id, $mediuser)
    {
        if (!$ufms) {
            return;
        }

        $_ufms = explode('|', $ufms);
        foreach ($_ufms as $_ufm) {
            $ufm           = new CUniteFonctionnelle();
            $ufm->type     = 'medicale';
            $ufm->group_id = $group_id;
            $ufm->code     = $_ufm;
            $ufm->loadMatchingObjectEsc();

            if (!$ufm->_id) {
                $this->unfound["ufm"][$_ufm] = true;
                CAppUI::stepAjax('mediusers-import-ufm-not-exists%s', UI_MSG_WARNING, $_ufm);
                continue;
            }

            $ufm_link        = new CAffectationUniteFonctionnelle();
            $ufm_link->uf_id = $ufm->_id;
            $ufm_link->setObject($mediuser);
            $ufm_link->loadMatchingObjectEsc();

            if (!$ufm_link->_id) {
                if ($msg = $ufm_link->store()) {
                    CAppUI::stepAjax($msg, UI_MSG_ERROR);
                } else {
                    CAppUI::stepAjax('CAffectationUniteFonctionnelle-msg-create', UI_MSG_OK);
                }
            }
        }
    }

    /**
     * Set the spec CPAM to the mediuser
     *
     * @param CMediusers $mediuser       Mediuser to set spec CPAM
     * @param string     $spec_cpam_code Code of the spec CPAM
     *
     * @return CMediusers
     */
    public function getSpecCPAM($mediuser, $spec_cpam_code)
    {
        $spec_cpam = CSpecCPAM::get($spec_cpam_code);
        if ($spec_cpam->_id) {
            $mediuser->spec_cpam_id = $spec_cpam->_id;
        } else {
            $this->unfound["spec_cpam_code"][$spec_cpam_code] = true;
        }

        return $mediuser;
    }

    /**
     * Get a function (create it if necessary) and return it
     *
     * @return CFunctions|null
     */
    public function getFunction()
    {
        $group_id = CGroups::loadCurrent()->_id;
        // Fonction
        $function           = new CFunctions();
        $function->group_id = $group_id;
        $function->text     = $this->line["fonction"];
        $function->loadMatchingObject();
        if (!$function->_id) {
            if (in_array($this->line["type"], ["3", "4", "13"])) {
                $function->type = "cabinet";
            } else {
                $function->type = "administratif";
            }
            $function->color              = "ffffff";
            $function->compta_partagee    = 0;
            $function->consults_partagees = 1;
            $function->unescapeValues();
            $msg = $function->store();
            if ($msg) {
                CAppUI::stepAjax($msg, UI_MSG_ERROR);
                $this->results[$this->current_line]["error"]    = $msg;
                $this->results[$this->current_line]["username"] = "";
                $this->results[$this->current_line]["password"] = "";

                return null;
            }
        }

        return $function;
    }

    /**
     * Check if a profile exists and set the user profile to it
     *
     * @param CMediusers $mediuser     Mediusers to import
     * @param string     $profile_name Name of the profile to search
     *
     * @return CMediusers
     */
    public function checkProfile($mediuser, $profile_name)
    {
        $profile                = new CUser();
        $profile->user_username = $profile_name;
        $profile->loadMatchingObject();
        if ($profile->_id) {
            $mediuser->_profile_id = $profile->_id;
        } else {
            $this->unfound['profile_name'][$profile_name] = true;
        }

        return $mediuser;
    }

    /**
     * @inheritdoc
     */
    function sanitizeLine($line)
    {
        if (!$line) {
            return '';
        }

        $line = array_map('addslashes', array_map('trim', parent::sanitizeLine($line)));
        foreach (static::HEADERS as $_info) {
            if (!array_key_exists($_info, $line)) {
                $line[$_info] = '';
            }
        }

        return $line;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return array
     */
    public function getUnfound()
    {
        return $this->unfound;
    }
}
