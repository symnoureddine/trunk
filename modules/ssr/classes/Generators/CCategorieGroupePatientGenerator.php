<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Ssr\CCategorieGroupePatient;

/**
 * Description
 */
class CCategorieGroupePatientGenerator extends CObjectGenerator {
  static $mb_class = CCategorieGroupePatient::class;

  /** @var CCategorieGroupePatient */
  protected $object;

  /**
   * @inheritdoc
   */
  function generate() {
    $obj = $this->getRandomObject($this->getMaxCount());

    if ($obj && $obj->_id) {
      $this->object = $obj;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->nom      = "CCategorieGroupePatient-" . random_int(1, 1000);
      $this->object->group_id = CGroups::loadCurrent()->_id;
      $this->object->type     = "ssr";

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_WARNING);
      }
      else {
        CAppUI::setMsg("CCategorieGroupePatient-msg-create", UI_MSG_OK);
      }
    }

    return $this->object;
  }
}