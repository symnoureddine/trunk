<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Constants;

use Ox\Core\CMbObject;

/**
 * Description
 */
class CReleveComment extends CMbObject
{
    // db field
    /** @var int Primary key */
    public $releve_comment_id;
    /** @var int */
    public $releve_id;
    /** @var int */
    public $value_id;
    /** @var string */
    public $value_class;
    /** @var string */
    public $comment;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "releve_comment";
        $spec->key   = "releve_comment_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $constant_classes = CConstantSpec::getConstantClasses();

        $props                = parent::getProps();
        $props["releve_id"]   = "ref class|CConstantReleve notNull back|comments_releve";
        $props["value_id"]    = "ref meta|value_class back|comments_value";
        $props["value_class"] = "enum list|" . implode("|", $constant_classes);
        $props["comment"]     = "text";

        return $props;
    }
}
