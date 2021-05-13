<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CDevisCodage;

$object_id    = CValue::get('object_id');
$object_class = CValue::get('object_class');

/** @var CCodable $object */
$object = CMbObject::loadFromGuid("$object_class-$object_id");

CAccessMedicalData::logAccess($object);

$object->loadRefPraticien();

$devis = new CDevisCodage();
$devis->codable_class = $object->_class;
$devis->codable_id = $object->_id;
$devis->loadMatchingObject();

if (!$devis->_id) {
  $devis->event_type = $object->_class;
  $devis->patient_id = $object->loadRefPatient()->_id;
  $devis->praticien_id = $object->loadRefPraticien()->_id;
  if ($object->_class == 'CConsultation') {
    $devis->libelle = $object->motif;
    $object->loadRefPlageConsult();
    $devis->date = $object->_date;
  }
  elseif ($object->_class == 'COperation') {
    $devis->libelle = $object->libelle;
    $devis->date = $object->date;
  }
  $devis->codes_ccam = $object->codes_ccam;
}

$smarty = new CSmartyDP();
$smarty->assign('devis', $devis);
$smarty->display('inc_devis_codage.tpl');