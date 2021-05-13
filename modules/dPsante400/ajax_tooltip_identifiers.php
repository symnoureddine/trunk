<?php
/**
 * @package Mediboard\Sante400
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Sante400\CIdSante400;

CCanDo::checkRead();

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");

CView::checkin();

if (!$object->getPerm(PERM_READ)) {
    CAppUI::accessDenied();
}

/** @var CIdSante400[] $identifiers */
$identifiers = $object->loadBackRefs("identifiants", "tag ASC, last_update DESC");

if ($identifiers) {
  foreach ($identifiers as $_idex) {
    $_idex->getSpecialType();
  }
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("identifiers", $identifiers);
$smarty->display("ajax_tooltip_identifiers.tpl");
