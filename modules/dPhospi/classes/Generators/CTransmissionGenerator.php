<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Hospi\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Hospi\CCible;
use Ox\Mediboard\Hospi\CTransmissionMedicale;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;

/**
 * Description
 */
class CTransmissionGenerator extends CObjectGenerator {
  static $mb_class = CTransmissionMedicale::class;
  static $dependances = array(CCible::class, CMediusers::class, CSejour::class);

  protected static $transmissions;

  /** @var CSejour */
  protected $sejour;
  /** @var CCible */
  protected $cible;
  protected $transmission_types = array("data", "action", "result");

  /**
   * @inheritdoc
   */
  function generate() {
    $template = $this->getRandomTransmission();

    $cible_generator = (new CCibleGenerator())->setForce($this->force);
    $cible_generator->init($this->sejour, $template['cible']);
    $this->cible = $cible_generator->generate();
    // TODO Cible ne semble pas s'afficher

    if ($this->force || !$this->sejour) {
      $this->sejour = (new CSejourGenerator())->generate();
    }

    try {
      $praticien = (new CMediusersGenerator())->setGroup($this->sejour->group_id)->generate('Infirmière');
    }
    catch (Exception $e) {
        dump($e->getMessage());
      CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

      return null;
    }

    $datetime = CMbDT::getRandomDate($this->sejour->entree, $this->sejour->sortie);

    foreach ($this->transmission_types as $_type) {
      if ($template[$_type]) {
        $transmission = $this->generateTransmission($praticien, $datetime, $template[$_type], $_type);
      }
    }

    return $transmission;
  }

  /**
   * Generate a CTransmission
   *
   * @param CMediusers|CUser $user     User responsible of the CTransmission
   * @param string           $datetime Date time of the object
   * @param string           $content  Content of the object
   * @param string           $type     Type of the CTransmission
   *
   * @return CTransmissionMedicale
   */
  protected function generateTransmission($user, $datetime, $content, $type) {
    $transmission            = new CTransmissionMedicale();
    $transmission->sejour_id = $this->sejour->_id;
    $transmission->user_id   = $user->_id;
    $transmission->date      = $datetime;
    $transmission->degre     = (rand(0, 1)) ? 'low' : 'high';
    $transmission->text      = $content;
    $transmission->type      = $type;
    $transmission->cible_id  = ($this->cible && $this->cible->_id) ? $this->cible->_id : null;

    if ($msg = $transmission->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::setMsg("CTransmissionMedicale-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE, $transmission);
    }

    return $transmission;
  }

  /**
   * Init the generator
   *
   * @param CSejour $sejour Sejour to generate transmissions for
   *
   * @return static
   */
  function init($sejour) {
    $this->sejour = $sejour;

    return $this;
  }

  /**
   * @return array
   */
  protected function getRandomTransmission() {
    if (!static::$transmissions) {
      $json          = file_get_contents(rtrim(CAppUI::conf('root_dir'), '\\/') . '/modules/populate/resources/transmissions.json');
      $transmissions = json_decode($json, true);
      foreach ($transmissions as &$_trans) {
        $_trans = array_map("utf8_decode", $_trans);
      }

      static::$transmissions = $transmissions;
    }

    return static::$transmissions[array_rand(static::$transmissions)];
  }
}
