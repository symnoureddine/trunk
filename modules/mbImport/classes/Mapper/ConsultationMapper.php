<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\Consultation;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard consultation mapper
 */
class ConsultationMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id'      => $row[$this->metadata->getIdentifier()],
      'heure'            => $this->convertToDateTime($row['heure']),
      'duree'            => $row['duree'],
      'motif'            => $row['motif'],
      'rques'            => $row['rques'],
      'examen'           => $row['examen'],
      'traitement'       => $row['traitement'],
      'histoire_maladie' => $row['histoire_maladie'],
      'conclusion'       => $row['conclusion'],
      'resultats'        => $row['resultats'],
      'plageconsult_id'  => $row['plageconsult_id'],
      'patient_id'       => $row['patient_id'],
    ];

    return Consultation::fromState($state);
  }
}
