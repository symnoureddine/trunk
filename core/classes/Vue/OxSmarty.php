<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Vue;

use Ox\Core\CAppUI;
use Ox\Core\CMbPath;
use Smarty;

class OxSmarty extends Smarty
{

    public function __construct(string $root_dir = null)
    {
        parent::__construct();

        $this->left_delimiter  = '{{';
        $this->right_delimiter = '}}';

        if (!$root_dir) {
            $root_dir = dirname(__DIR__, 2);
        }

        $this->compile_dir  = "$root_dir/tmp/templates_c/";
        $this->template_dir = "$root_dir/style/" . CAppUI::MEDIBOARD_EXT_THEME . "/templates/";
        // Check if the cache dir is writeable
        if (!is_dir($this->compile_dir)) {
            CMbPath::forceDir($this->compile_dir);
        }
    }
}
