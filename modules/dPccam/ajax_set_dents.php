<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Ccam\CDentCCAM;
use Ox\Mediboard\SalleOp\CActeCCAM;

CCanDo::checkRead();

$view = CView::get('acte_view', 'str');
$code = CView::get('code', 'str');
$activite = CView::get('activite', 'str');
$phase = CView::get('phase', 'str');
$date = CView::get('date', array('dateTime', 'default' =>  CMbDT::dateTime()));
$nullable = CView::get('nullable', 'bool default|0');

CView::checkin();

$acte = new CActeCCAM();
$acte->code_acte = $code;
$acte->code_activite = $activite;
$acte->code_phase;
$acte->execution = $date;
$acte->loadRefCodeCCAM();

$code = CDatedCodeCCAM::get($code, $date);
$activite = $code->activites[$activite];
$phase    = $activite->phases[$phase];

$dents = CDentCCAM::loadList();
$liste_dents = reset($dents);

$smarty = new CSmartyDP();
$smarty->assign('acte_view', $view);
$smarty->assign('acte', $acte);
$smarty->assign('phase', $phase);
$smarty->assign('liste_dents', $liste_dents);
$smarty->assign('nullable', $nullable);
$smarty->display('inc_set_dents.tpl');