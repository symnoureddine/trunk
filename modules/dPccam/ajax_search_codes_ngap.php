<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCodeNGAP;
use Ox\Mediboard\Mediusers\CSpecCPAM;

CCanDo::checkRead();

$spec_id = CView::get('spec', 'num default|1');
$date = CView::get('date', array('date', 'default' => CMbDT::date()));
$zone = CView::get('zone', 'enum list|metro|antilles|mayotte|guyane-reunion default|metro');

CView::checkin();

$spec = CSpecCPAM::get($spec_id);

$codes = CCodeNGAP::getForSpeciality($spec, $date, $zone);

$smarty = new CSmartyDP();
$smarty->assign('spec', $spec);
$smarty->assign('date', $date);
$smarty->assign('codes', $codes);
$smarty->display('inc_list_codes_ngap.tpl');