<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Urgences;

use Ox\Core\CMbObject;

/**
 * Class CRPUPassage
 */
class CRPUPassage extends CMbObject
{
    // DB Table key
    public $rpu_passage_id;

    // DB Fields
    public $rpu_id;
    public $extract_passages_id;

    /**
     * @see parent::getSpec()
     */
    function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = 'rpu_passage';
        $spec->key      = 'rpu_passage_id';
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    function getProps()
    {
        $specs                        = parent::getProps();
        $specs["rpu_id"]              = "ref notNull class|CRPU back|passages";
        $specs["extract_passages_id"] = "ref notNull class|CExtractPassages back|passages_rpu";

        return $specs;
    }
}
