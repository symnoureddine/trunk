<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\Patient;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard patient mapper
 */
class PatientMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id'      => $row[$this->metadata->getIdentifier()],
      'nom'              => $row['nom'],
      'prenom'           => $row['prenom'],
      'naissance'        => $this->convertToDateTime($row['naissance']),
      'nom_jeune_fille'  => $row['nom_jeune_fille'],
      'profession'       => $row['profession'],
      'email'            => $row['email'],
      'tel'              => $row['tel'],
      'tel2'             => $row['tel2'],
      'tel_autre'        => $row['tel_autre'],
      'adresse'          => $row['adresse'],
      'cp'               => $row['cp'],
      'ville'            => $row['ville'],
      'pays'             => $row['pays'],
      'matricule'        => $row['matricule'],
      'sexe'             => $row['sexe'],
      'civilite'         => $row['civilite'],
      'medecin_traitant' => $row['medecin_traitant'],
    ];

    return Patient::fromState($state);
  }
}
