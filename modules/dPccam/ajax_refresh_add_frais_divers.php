<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Ccam\CFraisDivers;

CCanDo::checkEdit();
$object_guid = CValue::get("object_guid");

/* @var CCodable $object*/
$object = CMbObject::loadFromGuid($object_guid);

CAccessMedicalData::logAccess($object);

$object->loadRefsFraisDivers();

$frais_divers = new CFraisDivers();
$frais_divers->setObject($object);
$frais_divers->quantite = 1;
$frais_divers->coefficient = 1;
$frais_divers->num_facture = 1;
if ($object->_class == "CConsultation" && $object->valide) {
  $object->loadRefFacture();
  $frais_divers->num_facture = count($object->_ref_factures)+1;
}
$frais_divers->loadListExecutants();
$frais_divers->loadExecution();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("object"      , $object);
$smarty->assign("frais_divers", $frais_divers);

$smarty->display("inc_form_add_frais_divers.tpl");
