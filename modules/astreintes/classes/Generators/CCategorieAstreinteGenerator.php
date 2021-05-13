<?php

/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;

/**
 * Description
 */
class CCategorieAstreinteGenerator extends CObjectGenerator
{
    /** @var string $mb_class */
    public static $mb_class = CCategorieAstreinte::class;
    /** @var array $dependances */
    public static $dependances = [];

    /** @var CCategorieAstreinte */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate(): CCategorieAstreinte
    {
        if ($this->force) {
            $obj = null;
        } else {
            $obj = $this->getRandomObject($this->getMaxCount());
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $color               = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            $this->object->name  = "CCategorieAstreinte-" . random_int(1, 1000);
            $this->object->color = $color;

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CCategorieAstreinte-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }
}
