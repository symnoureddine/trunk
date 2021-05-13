<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Interop\Eai\CTransformation;
use Ox\Interop\Eai\CTransformationRule;
use Ox\Interop\Eai\CInteropActor;

/**
 * Link transformations
 */
CCanDo::checkAdmin();

$actor_guid           = CValue::post("actor_guid");
$event_name           = CValue::post("event_name");
$transformation_rules = CValue::post("transformation_rules", array());

/** @var CInteropActor $actor */
$actor = CMbObject::loadFromGuid($actor_guid);

$event = new $event_name;

// Ajout des transformations à l'acteur
foreach ($transformation_rules as $_transf_rule_id) {
  $transformation_rule = new CTransformationRule();
  $transformation_rule->load($_transf_rule_id);

  $transformation = new CTransformation();
  $transformation->bindTransformationRule($transformation_rule, $actor);
  $transformation->message = $event_name;

  if ($msg = $transformation->store()) {
    CAppUI::setMsg($msg, UI_MSG_ERROR);
  }
  else {
    CAppUI::setMsg("CTransformation-msg-modify");
  }
}

echo CAppUI::getMsg();
CApp::rip();



