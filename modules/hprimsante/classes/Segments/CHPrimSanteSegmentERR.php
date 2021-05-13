<?php
/**
 * @package Mediboard\Hprimsante
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hprimsante\Segments;

use Ox\Interop\Hl7\CHEvent;
use Ox\Interop\Hprimsante\CExchangeHprimSante;
use Ox\Interop\Hprimsante\CHPrimSanteAcknowledgment;
use Ox\Interop\Hprimsante\CHPrimSanteError;
use Ox\Interop\Hprimsante\CHPrimSanteSegment;

/**
 * Class CHPrimSanteSegmentERR
 * ERR - Represents an HPR ERR message segment (Error)
 */

class CHPrimSanteSegmentERR extends CHPrimSanteSegment {
  public $name = "ERR";

  /** @var CHPrimSanteAcknowledgment */
  public $acknowledgment;

  /**
   * @inheritdoc
   */
  function build(CHEvent $event, $name = null) {
    parent::build($event);

    /** @var CHPrimSanteAcknowledgment $acknowledgment */
    $acknowledgment = $this->acknowledgment;
    /** @var CExchangeHprimSante $exchange_hpr */
    $exchange_hpr   = $event->_exchange_hpr;
    /** @var CHPrimSanteError $error */
    $error          = $acknowledgment->_error;
    list($segment, $rang, $identifier) = $error->address;

    $data = array();

    // ERR-1: Segment Row
    $data[] = $rang;

    // ERR-2: Filename
    $data[] = $exchange_hpr->nom_fichier;

    // ERR-3: Date / Time of receipt
    $data[] = $exchange_hpr->date_production;

    // ERR-4: Severity
    $data[] = $error->type_error;

    // ERR-5: Line number
    $data[] = null;

    // ERR-6: Error Location
    $data[] = array(array($segment, $rang, array_values($identifier)));

    // ERR-7: Field Position
    $data[] = $error->field;

    // ERR-8: Error value
    $data[] = null;

    // ERR-9: Error type
    $data[] = null;

    // ERR-10: Original Text
    $data[] = $error->getCommentError();

    $this->fill($data);
  }
}
