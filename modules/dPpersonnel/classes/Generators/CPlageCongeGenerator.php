<?php

/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel\Generators;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\Personnel\CPlageConge;

/**
 * Description
 */
class CPlageCongeGenerator extends CObjectGenerator
{
    /** @var string $mb_class */
    public static $mb_class = CPlageConge::class;
    /** @var array $dependances */
    public static $dependances = [CMediusers::class];

    /** @var CPlageConge */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate(): CPlageConge
    {
        $user = (new CMediusersGenerator())->generate();

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "user_id" => "= '$user->_id'",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $this->object->user_id    = $user->_id;
            $this->object->date_debut = CMbDT::dateTime("- 3 days");
            $this->object->date_fin   = CMbDT::dateTime("+ 2 days");
            $this->object->libelle    = "Congé pour " . $user->_view;

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CPlageConge-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }
}
