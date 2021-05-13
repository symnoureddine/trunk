<?php
/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\NovxtelHospitality\CSourceNovxtelHospitality;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::check();
$sejour_id = CView::get('sejour_id', 'ref class|CSejour');
CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

$patient = $sejour->loadRefPatient();
$ipp     = $patient->loadIPP();

$source = new CSourceNovxtelHospitality();
$url = $source->getUrl($patient->_IPP);

header("Location: $url");
CApp::rip();
