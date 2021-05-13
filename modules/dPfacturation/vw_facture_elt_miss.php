<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Facturation\CFactureEtablissement;
use Ox\Mediboard\Tarmed\CEditBill;

$facture_guid = CView::get("facture_guid", "str");
CView::checkin();

/* @var CFactureEtablissement $facture*/
$facture = CMbObject::loadFromGuid($facture_guid);

$validation_xml = new CEditBill();
$validation_xml->_facture = $facture;
$validation_xml->checkElementsBillsForXML();

// Creation du template
$smarty = new CSmartyDP();
$smarty->assign("validation_xml", $validation_xml);
$smarty->assign("facture"       , $facture);
$smarty->display("vw_facture_elt_miss");
