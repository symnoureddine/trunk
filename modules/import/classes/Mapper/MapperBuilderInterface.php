<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Mapper;

use Exception;

/**
 * Description
 */
interface MapperBuilderInterface {
  /**
   * @param string $name Resource name
   *
   * @return MapperInterface
   * @throws Exception
   */
  public function build(string $name): MapperInterface;
}
