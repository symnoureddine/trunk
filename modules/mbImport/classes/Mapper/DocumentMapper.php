<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\ExternalReference;

/**
 * Mediboard document mapper
 */
class DocumentMapper extends FileMapper
{
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface
  {
    $document = parent::createEntity($row);

    $document->setCustomRefEntities(
      function () {
        return [
          ExternalReference::getMandatoryFor('medecin', $this->author_id),
          ExternalReference::getNotMandatoryFor('patient', $this->patient_id),
          ExternalReference::getNotMandatoryFor('consultation', $this->consultation_id),
          ExternalReference::getNotMandatoryFor('sejour', $this->sejour_id),
        ];
      }
    );

    return $document;
  }
}
