<?php
/**
 * @package Mediboard\Bloc
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Bloc\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bloc\CBlocOperatoire;
use Ox\Mediboard\Bloc\CSalle;

/**
 * Description
 */
class CSalleGenerator extends CObjectGenerator {
  static $mb_class = CSalle::class;
  static $dependances = array(CBlocOperatoire::class);
  static $ds = array();

  /** @var CSalle */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $bloc = (new CBlocOperatoireGenerator())->generate();

    if ($this->force) {
      $obj = null;
    }
    else {
      $where = array(
        "bloc_id" => "= '$bloc->_id'",
      );

      $obj = $this->getRandomObject($this->getMaxCount(), $where);
    }

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->bloc_id = $bloc->_id;
      $this->object->nom = "CSalle-" . $bloc->_id;

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CSalle-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}