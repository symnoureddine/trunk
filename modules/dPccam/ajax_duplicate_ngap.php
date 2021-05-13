<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Ccam\CCodable;

CCanDo::checkEdit();

$codable_guid = CView::get('codable_guid', 'guid class|CCodable');
$acte_guid    = CView::get('acte_guid', 'guid class|CActeNGAP');

CView::checkin();

/** @var CCodable $codable */
$codable = CMbObject::loadFromGuid($codable_guid);

if ($codable->_id) {
  $acte = CActeNGAP::loadFromGuid($acte_guid);

  $smarty = new CSmartyDP();
  $smarty->assign('codable', $codable);
  $smarty->assign('acte', $acte);
  $smarty->display('inc_duplicate_ngap.tpl');
}
