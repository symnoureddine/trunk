<?php
/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\NovxtelHospitality\CSourceNovxtelHospitality;

CCanDo::checkAdmin();

$novxtel_hospitality        = new CSourceNovxtelHospitality();
$source_novxtel_hospitality = $novxtel_hospitality->getSource();

$smarty = new CSmartyDP();
$smarty->assign("source_novxtel_hospitality", $source_novxtel_hospitality);
$smarty->display("configure");
