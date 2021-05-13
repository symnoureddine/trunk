<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Module;

/**
 * Periodical task interface
 */
interface IModuleCache {
  /**
   * Clears cache keys from patterns
   *
   * @return void
   */
  function clear(): void;

  /**
   * Specific actions to run
   *
   * @return void
   */
  function clearSpecialActions(): void;
}
