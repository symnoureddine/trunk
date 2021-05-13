<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bloc\CSalle;
use Ox\Mediboard\Bloc\Generators\CSalleGenerator;
use Ox\Mediboard\CompteRendu\Generators\CCompteRenduGenerator;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Description
 */
class COperationGenerator extends CObjectGenerator {
  const TWO_HOURS = 7200;
  const FIFTEEN_MIN = 900;

  static $mb_class = COperation::class;
  static $dependances = array(CMediusers::class, CSejour::class);
  static $ds = array(
    "ccamV2" => array("codes_ccam", "examen")
  );

  /** @var CSejour */
  protected $sejour;
  /** @var COperation */
  protected $object;

  /** @var CSalle */
  protected $salle;

  /** @var bool */
  protected $add_doc = false;

  /**
   * @inheritdoc
   * @throws Exception
   */
  function generate() {
    if ($this->force || !$this->sejour) {
      $this->sejour = (new CSejourGenerator())->generate();
    }

    $this->object->sejour_id = $this->sejour->_id;

    $praticien          = (new CMediusersGenerator())->setGroup($this->sejour->group_id)->generate();
    $this->object->chir_id = $praticien->_id;
    $this->object->date    = CMbDT::getRandomDate($this->sejour->entree, $this->sejour->sortie, "Y-m-d");

    $codes                     = $this->getRandomCCAMCodes(2);
    $this->object->codes_ccam     = implode("|", array_keys($codes));
    $this->object->examen         = implode("\n", $codes);
    $this->object->temp_operation = CMbDT::getRandomDate(static::FIFTEEN_MIN, static::TWO_HOURS, "H:i:s");

    $this->object->salle_id = $this->salle ? $this->salle->_id : (new CSalleGenerator())->generate()->_id;

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::setMsg("COperation-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE, $this->object);
    }

    if ($this->object->_id && $this->add_doc) {
      (new CCompteRenduGenerator())->init($this->object)->generate("cro");
    }

    return $this->object;
  }

  /**
   * Init the generator
   *
   * @param CSejour $sejour Sejour to link operation to
   *
   * @return static
   */
  function init($sejour) {
    $this->sejour = $sejour;

    return $this;
  }

  /**
   * Get random CCAM codes
   *
   * @param int $count Count of codes to return
   *
   * @return array
   */
  protected function getRandomCCAMCodes($count = 2) {
    $ds = CSQLDataSource::get('ccamV2');

    $query = new CRequest();
    $query->addTable('p_acte');
    try {
      $total = $ds->loadResult($query->makeSelectCount());
    }
    catch (Exception $e) {
      CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

      return array();
    }


    $codes = array();

    for ($i = 0; $i < $count; $i++) {
      $limit = rand(0, $total - 1);
      $query = new CRequest();
      $query->addSelect(array("CODE", "LIBELLELONG"));
      $query->addTable('p_acte');
      $query->setLimit("$limit, 1");

      try {
        $code = $ds->loadHash($query->makeSelect());
      }
      catch (Exception $e) {
        CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);
        continue;
      }

      $codes[$code['CODE']] = $code['LIBELLELONG'];
    }

    return $codes;
  }

  /**
   * @param CSalle $salle
   *
   * @return $this
   */
  public function setSalle(CSalle $salle): self {
    $this->salle = $salle;
    return $this;
  }

  /**
   * @param bool $add_doc
   *
   * @return $this
   */
  public function setAddDoc(bool $add_doc) {
    $this->add_doc = $add_doc;
    return $this;
  }
}
