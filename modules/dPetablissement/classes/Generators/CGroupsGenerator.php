<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Etablissement\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * Description
 */
class CGroupsGenerator extends CObjectGenerator {
  static $scientists;
  static $mb_class = CGroups::class;
  static $ds = array(
    "INSEE" => array("cp", "ville", "code")
  );

  /** @var CGroups */
  protected $object;

  /**
   * @inheritdoc
   * @throws Exception
   */
  function generate() {
    // If not force try to reuse a CGroups
    if (!$this->force && ($group = $this->getRandomObject($this->getMaxCount()))) {
      $this->object = $group;
      $this->trace(static::TRACE_LOAD);
    }
    else {
      $this->object->raison_sociale = $this->getRS();
      $this->object->_name          = $this->object->raison_sociale;

      $commune             = $this->getCommune();
      $this->object->cp    = $commune['code_postal'];
      $this->object->ville = $commune['commune'];
      $this->object->code  = $commune['commune'][0];
      $this->object->text  = "Auto generated";

      $this->object->adresse = $this->getAdresse();

      if ($msg = $this->object->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
      }
      else {
        CAppUI::setMsg("CGroups-msg-create", UI_MSG_OK);
        $this->trace(static::TRACE_STORE);
      }
    }

    return $this->object;
  }

  /**
   * @return string
   */
  protected function getRS() {
    if (!static::$scientists) {
      $json = file_get_contents(rtrim(CAppUI::conf('root_dir'), '\\/') . '/modules/populate/resources/informaticiens.json');

      static::$scientists = json_decode($json);
    }

    return "Établissement " . utf8_decode(trim(static::$scientists[array_rand(static::$scientists)]));
  }

  /**
   * Get a random adresse
   *
   * @return string
   */
  protected function getAdresse() {
    // TODO implement method
    return "";
  }
}
