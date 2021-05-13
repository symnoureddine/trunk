<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\Sejour;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard medecin mapper
 */
class SejourMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id'   => $row[$this->metadata->getIdentifier()],
      'type'          => $row['type'],
      'entree_prevue' => $this->convertToDateTime($row['entree_prevue']),
      'entree_reelle' => $this->convertToDateTime($row['entree_reelle']),
      'sortie_prevue' => $this->convertToDateTime($row['sortie_prevue']),
      'sortie_reelle' => $this->convertToDateTime($row['sortie_reelle']),
      'libelle'       => $row['libelle'],
      'patient_id'    => $row['patient_id'],
      'praticien_id'  => $row['praticien_id'],
      'group_id'      => $row['group_id'],
    ];

    return Sejour::fromState($state);
  }
}
