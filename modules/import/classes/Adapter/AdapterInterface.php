<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Adapter;

use Generator;

/**
 * Description
 */
interface AdapterInterface {
  /**
   * @param string $collection The collection's name (table or file for example)
   * @param string $identifier
   * @param mixed  $id
   *
   * @return array|null
   */
  public function retrieve(string $collection, string $identifier, $id, array $conditions = [], array $select = []);

  /**
   * Get the n data from an optional Id
   *
   * @param string     $collection The collection's name (table or file for example)
   * @param int        $count
   * @param array|null $conditions
   *
   * @return Generator|null
   */
  public function get(string $collection, int $count = 1, int $offset = 0, ?array $conditions = [], ?array $select = []): ?Generator;

  public function count(string $collection, ?array $conditions = [], array $select = []): int;
}
