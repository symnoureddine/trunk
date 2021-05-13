<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Ox\Core\CMbTemplatePlaceholder;

/**
 * Description
 */
class CKeychainTemplatePlaceholder extends CMbTemplatePlaceholder {
  /**
   * @inheritdoc
   */
  function __construct() {
    parent::__construct('passwordKeeper');

    $this->minitoolbar = 'inc_minitoolbar';
  }
}
