<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbString;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSpecCPAM;

/**
 * Description
 */
class CMediusersGenerator extends CObjectGenerator {
  static $mb_class = CMediusers::class;
  static $dependances = array(CFunctions::class);

  /** @var CMediusers */
  protected $object;

  /**
   * Generate a CMediusers
   *
   * @param string $type CMediusers type
   *
   * @return CMediusers
   * @throws Exception
   */
  function generate($type = 'Médecin', ?int $spec_cpam_id = null) {
    $type_id = array_keys(CUser::$types, $type);
    $type_id = reset($type_id);

    $function = (new CFunctionsGenerator())->setGroup($this->group_id)->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        'user_id'     => "IN (SELECT user_id FROM `users` WHERE user_type = '{$type_id}')",
        'function_id' => "= '$function->_id'",
      );

      if ($spec_cpam_id) {
        $where['spec_cpam_id'] = " = {$spec_cpam_id}";
      }

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->function_id = $function->_id;

      $names = $this->getRandomNames(2);

      $this->object->_user_first_name = $names[0]->firstname;
      $this->object->_user_last_name  = $names[1]->firstname;
      $this->object->_user_username   = $this->object->_user_first_name[0] . $this->object->_user_last_name;
      $this->object->_user_sexe       = $names[0]->sex;

      $user                = new CUser();
      $user->user_username = $this->object->_user_username;
      if ($match = $user->loadMatchingObjectEsc()) {
        $this->object->_user_username .= uniqid('', true);
      }

      $this->object->_user_type = $type_id;

      $this->object->spec_cpam_id = $spec_cpam_id ? $spec_cpam_id : $this->getRandomSpecCPAM()->_id;
      $this->object->rpps         = $this->getRandomRPPS();
      $this->object->titres       = "Ancien interne en " . $this->getRandomSpecCPAM()->text;

      if ($msg = $this->object->store()) {
        CAppUI::stepAjax($msg, UI_MSG_ERROR);
      }
      else {
        CAppUI::setMsg("CMediusers-msg-create", UI_MSG_OK);
        $this->trace(static::TRACE_STORE);
      }
    }


    return $this->object;
  }

  /**
   * Get a random RPPS number
   *
   * @return string
   */
  function getRandomRPPS() {
    $number = '';
    for ($i = 0; $i < 10; $i++) {
      $number .= rand(0, 9);
    }

    return CMbString::createLuhn($number);
  }

  /**
   * Get a random CPAM speciality
   *
   * @return CSpecCPAM
   */
  function getRandomSpecCPAM() {
    $list = CSpecCPAM::getList();
    shuffle($list);

    $spec_cpam = reset($list);

    return $spec_cpam;
  }
}
