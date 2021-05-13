<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\PlageConsult;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard plageconsult mapper
 */
class PlageConsultMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id' => $row[$this->metadata->getIdentifier()],
      'date'        => $this->convertToDateTime($row['date']),
      'freq'        => $this->convertToDateTime($row['freq']),
      'debut'       => $this->convertToDateTime($row['debut']),
      'fin'         => $this->convertToDateTime($row['fin']),
      'libelle'     => $row['libelle'],
      'chir_id'     => $row['chir_id'],
    ];

    return PlageConsult::fromState($state);
  }
}
