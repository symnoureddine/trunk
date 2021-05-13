<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\Medecin;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard medecin mapper
 */
class MedecinMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id' => $row[$this->metadata->getIdentifier()],
      'nom'         => $row['nom'],
      'prenom'      => $row['prenom'],
      'sexe'        => $row['sexe'],
      'titre'       => $row['titre'],
      'email'       => $row['email'],
      'disciplines' => $row['disciplines'],
      'tel'         => $row['tel'],
      'tel_autre'   => $row['tel_autre'],
      'adresse'     => $row['adresse'],
      'cp'          => $row['cp'],
      'ville'       => $row['ville'],
      'rpps'        => $row['rpps'],
      'adeli'       => $row['adeli'],
    ];

    return Medecin::fromState($state);
  }
}
