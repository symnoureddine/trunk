<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\ConsultationAnesth;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard consultation anesth mapper
 */
class ConsultationAnesthMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id' => $row[$this->metadata->getIdentifier()],
    ];

    return ConsultationAnesth::fromState($state);
  }
}
