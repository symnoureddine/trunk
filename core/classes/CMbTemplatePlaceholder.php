<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Autoload\IShortNameAutoloadable;

/**
 * Class CMbTemplatePlaceholder 
 * @abstract Template placeholder class for module extensibility of main style templates
 */
abstract class CMbTemplatePlaceholder implements IShortNameAutoloadable {
  public $module;
  public $minitoolbar;
  public $minitoolbar_cabinet;

  function __construct($module) {
    $this->module = $module;
  }

  function getTemplate() {
    return ((CAppUI::isCabinet() || CAppUI::isGroup()) && $this->minitoolbar_cabinet) ? $this->minitoolbar_cabinet : $this->minitoolbar;
  }
}
