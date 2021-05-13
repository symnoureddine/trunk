<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\User;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Mediboard user mapper
 */
class UserMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {
    $state = [
      'external_id' => $row[$this->metadata->getIdentifier()],
      'username'    => $row['user_username'],
      'first_name'  => $row['user_first_name'],
      'last_name'   => $row['user_last_name'],
      'gender'      => $row['user_sexe'],
      'birthday'    => $this->convertToDateTime($row['user_birthday']),
      'email'       => $row['user_email'],
      'phone'       => $row['user_phone'],
      'mobile'      => $row['user_mobile'],
      'address'     => $row['user_address1'],
      'zip'         => $row['user_zip'],
      'city'        => $row['user_city'],
      'country'     => $row['user_country'],
    ];

    return User::fromState($state);
  }
}
