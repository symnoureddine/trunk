<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes;

use Ox\Core\CMbTemplatePlaceholder;

/**
 * Description
 */
class CAstreintesTemplatePlaceholder extends CMbTemplatePlaceholder {
  /**
   * Standard constuctor
   */
  function __construct() {
    parent::__construct("astreintes");
    $this->minitoolbar = "inc_button_astreinte_day";
  }
}
