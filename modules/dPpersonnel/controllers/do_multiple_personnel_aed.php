<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Personnel\CPersonnel;

CCanDo::checkAdmin();

$user_ids = CView::post('user_id', 'str notNull');
$types    = CView::post('emplacement', 'str notNull');
$actif    = CView::post('actif', 'bool default|1');

CView::checkin();

$group    = CGroups::loadCurrent();
$created  = 0;
$modified = 0;
$errors   = 0;
$ds       = (new CPermObject())->getDS();

foreach ($user_ids as $user_id) {
    // Permissions sur les établissements de l'utilisateur courant
    $permObject  = new CPermObject();
    $orderObject = "object_class, object_id";

    $whereUser = [
        "user_id"      => $ds->prepare("= ?", $user_id),
        "object_class" => $ds->prepare("= ?", $group->_class),
        "object_id"    => $ds->prepare("= ?", $group->_id),
        "permission = '1' OR permission = '2'",
    ];

    if ($permObject->countList($whereUser, $orderObject)) {
        foreach ($types as $type) {
            $personnel              = new CPersonnel();
            $personnel->user_id     = $user_id;
            $personnel->emplacement = $type;
            $personnel->loadMatchingObjectEsc();

            $personnel->actif = $actif;

            if ($msg = $personnel->store()) {
                $errors++;
            } elseif ((bool)$personnel->_id) {
                $created++;
            } else {
                $modified++;
            }
        }
    }
}

if ($created) {
    CAppUI::setMsg(CAppUI::tr('CPersonnel-msg-create-multiple', $created), UI_MSG_OK);
}
if ($modified) {
    CAppUI::setMsg(CAppUI::tr('CPersonnel-msg-modify-multiple', $modified), UI_MSG_OK);
}
if ($errors) {
    CAppUI::setMsg(CAppUI::tr('CPersonnel-msg-errors-multiple', $errors), UI_MSG_ERROR);
}

echo CAppUI::getMsg();
