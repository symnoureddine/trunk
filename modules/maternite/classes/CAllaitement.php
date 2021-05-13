<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;

/**
 * Périodes d'allaitement
 */
class CAllaitement extends CMbObject {
  /**
   * @var integer Primary key
   */
  public $allaitement_id;

  // DB Fields
  public $patient_id;
  public $grossesse_id;
  public $date_debut;
  public $date_fin;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "allaitement";
    $spec->key   = "allaitement_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                 = parent::getProps();
    $props["patient_id"]   = "ref notNull class|CPatient back|allaitements";
    $props["grossesse_id"] = "ref class|CGrossesse back|allaitements";
    $props["date_debut"]   = "dateTime notNull";
    $props["date_fin"]     = "dateTime moreEquals|date_debut";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_view = "Allaitement du " . CMbDT::transform($this->date_debut, null, CAppUI::conf("date")) . " à " . CMbDT::transform($this->date_debut, null, CAppUI::conf("time"));

    if ($this->date_fin) {
      $this->_view .= " au " . CMbDT::transform($this->date_fin, null, CAppUI::conf("date")) . " à " . CMbDT::transform($this->date_fin, null, CAppUI::conf("time"));
    }
  }
}
