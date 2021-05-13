<?php

/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite\Generators;

use Ox\Core\CAppUI;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Maternite\CExamenNouveauNe;
use Ox\Mediboard\Maternite\CGrossesse;
use Ox\Mediboard\Maternite\CNaissance;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;

/**
 * Générateur d'examen d'un nouveau né
 */
class CExamenNouveauNeGenerator extends CObjectGenerator
{
    /** @var CExamenNouveauNe $mb_class */
    public static $mb_class    = CExamenNouveauNe::class;
    /** @var array $dependances */
    public static $dependances = [CGrossesse::class, CMediusers::class, CNaissance::class];

    /** @var CExamenNouveauNe $object */
    protected $object;

    /**
     * @inheritdoc
     */
    public function generate(): CExamenNouveauNe
    {
        $examinateur = (new CMediusersGenerator())->setForce($this->force)->generate();
        $naissance   = (new CNaissanceGenerator())->setForce($this->force)->generate();
        $grossesse   = $naissance->loadRefGrossesse();

        if ($this->force) {
            $obj = null;
        } else {
            $where = [
                "grossesse_id" => "= '$grossesse->_id'",
            ];

            $obj = $this->getRandomObject($this->getMaxCount(), $where);
        }

        if ($obj && $obj->_id) {
            $this->object = $obj;
            $this->trace(static::TRACE_LOAD);
        } else {
            $this->object->grossesse_id   = $grossesse->_id;
            $this->object->examinateur_id = $examinateur->_id;
            $this->object->naissance_id   = $naissance->_id;
            $this->object->poids          = mt_rand(2650, 4210);
            $this->object->taille         = mt_rand(47, 54);
            $this->object->pc             = mt_rand(31, 37);

            if ($msg = $this->object->store()) {
                CAppUI::setMsg($msg, UI_MSG_WARNING);
            } else {
                CAppUI::setMsg("CExamenNouveauNe-msg-create", UI_MSG_OK);
            }
        }

        return $this->object;
    }
}
