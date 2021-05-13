<?php

/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers\Controllers\Legacy;

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CLegacyController;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CCSVImportMediusers;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CMediusersExportCsv;
use Ox\Mediboard\Mediusers\CMediusersExportXml;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Description
 */
class CMediusersLegacyController extends CLegacyController
{
    public function vw_export_mediusers_xml()
    {
        $this->checkPermAdmin();

        CView::checkin();

        $cabinet       = CAppUI::isCabinet();
        $current_group = CGroups::loadCurrent();
        $functions     = [];
        if ($cabinet) {
            $functions = $current_group->loadFunctions(PERM_READ);
        }

        $user        = new CUser();
        $user_fields = $user->getExportableFields(true);

        $mediuser        = new CMediusers();
        $mediuser_fields = $mediuser->getExportableFields(true);

        $fields = array_merge($user_fields, $mediuser_fields);

        $etablissements = CGroups::loadGroups(PERM_READ);

        $this->renderSmarty(
            'vw_export_mediusers_xml',
            [
                'etabs'     => $etablissements,
                'functions' => $functions,
                'cabinet'   => $cabinet,
                'group'     => $current_group,
                'fields'    => $fields,
            ]
        );
    }

    public function ajax_export_mediusers_xml()
    {
        $this->checkPermAdmin();

        $etab_id           = CView::get('etablissement', 'ref class|CGroups notNull');
        $function_id       = CView::get('function', 'ref class|CFunctions');
        $profile           = CView::get('profile', 'bool default|0');
        $perms             = CView::get('perms', 'bool default|0');
        $prefs             = CView::get('prefs', 'bool default|0');
        $default_prefs     = CView::get('default_prefs', 'bool default|0');
        $perms_functionnal = CView::get('perms_functionnal', 'bool default|0');
        $tarification      = CView::get('tarification', 'bool default|0');
        $planning          = CView::get('planning', 'bool default|0');

        CView::checkin();
        CView::enforceSlave();

        $mediuser_export = new CMediusersExportXml(
            $etab_id,
            $function_id,
            $profile,
            $perms,
            $prefs,
            $default_prefs,
            $perms_functionnal,
            $tarification,
            $planning
        );


        CStoredObject::$useObjectCache = false;

        $mediuser_export->exportMediusers();
    }

    public function vw_export_mediusers_csv()
    {
        $this->checkPermAdmin();

        CView::checkin();

        $this->renderSmarty(
            'vw_export_mediusers_csv',
            [
                'fields'     => CCSVImportMediusers::HEADERS,
                'fields_opt' => CMediusersExportCsv::OPTIONNAL_FIELDS,
            ]
        );
    }

    public function ajax_export_mediusers_csv()
    {
        $this->checkPermAdmin();

        $ldap      = CView::get('ldap', 'bool default|0');
        $last_auth = CView::get('last_auth', 'bool default|0');

        CView::enforceSlave();

        $export = new CMediusersExportCsv();

        if ($ldap) {
            $export->addField('ldap', $ldap);
        }

        if ($last_auth) {
            $export->addField('last_auth', $last_auth);
        }

        $export->export();
    }

    public function ajax_import_mediusers_csv()
    {
        $this->checkPermAdmin();

        $file   = CValue::files("formfile");
        $dryrun = CView::post("dryrun", "bool default|0");
        $update = CView::post("update", "bool default|0");

        CView::checkin();

        if (!$file || !$file['tmp_name']) {
            CAppUI::stepAjax("CFile-not-exists", UI_MSG_ERROR, $file);
        }

        $import = new CCSVImportMediusers($file['tmp_name'][0], $dryrun, $update);
        $import->import();

        $results = $import->getResults();
        $unfound = $import->getUnfound();

        echo CAppUI::getMsg();

        $this->renderSmarty(
            'inc_import_mediusers_csv',
            [
                'results' => $results,
                'unfound' => $unfound,
                'dryrun'  => $dryrun,
            ]
        );
    }
}
