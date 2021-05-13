<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Composer\CComposerScript;

/**
 * Class CMbObjectSpec
 */
class CMbObjectSpec
{
    public const LOGGABLE_ALWAYS       = 'always';
    public const LOGGABLE_NEVER        = 'never';
    public const LOGGABLE_BOT          = 'bot';
    public const LOGGABLE_HUMAN        = 'human';
    public const LOGGABLE_LEGACY_FALSE = false;
    public const LOGGABLE_LEGACY_TRUE  = true;

    // Specification fields
    public $incremented         = true;
    public $loggable            = self::LOGGABLE_ALWAYS;
    public $merge_type          = null;     // allow to specify a particular type of merging. null|fast|check|none
    public $nullifyEmptyStrings = true;
    public $dsn                 = "std";

    /** @var string|null Table name */
    public $table = null;
    /** @var string|null Primary key column name */
    public $key = null;
    /** @var string Defines the query syntax for current object type (like, match) */
    public $seek = 'like';
    /** @var array|null [experimental] Temporary loading restrain to a field collection when defined */
    public $columns = null;

    /** @var bool */
    public $archive = false;

    public $measureable    = false;
    public $insert_delayed = false;

    public $uniques = [];
    public $xor     = [];
    public $events  = [];

    /** @var CSQLDataSource */
    public $ds = null;

    /** @var bool */
    public $anti_csrf = false;

    /**
     * Initialize derivate fields
     *
     * @return void
     */
    public function init(): void
    {
        if (CComposerScript::$is_running) {
            $this->ds = @CSQLDataSource::get($this->dsn, $this->dsn !== "std");
        } else {
            $this->ds = CSQLDataSource::get($this->dsn, $this->dsn !== "std");
        }
    }

    /**
     * ToString method to be used in the HTML for the form className
     *
     * @return string The spec as string
     */
    public function __toString(): string
    {
        $specs = [];
        foreach ($this->xor as $xor) {
            $specs[] = "xor|" . implode("|", $xor);
        }

        return implode(" ", $specs);
    }
    /**
     * @return array
     */
    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        unset($vars["ds"]);

        return array_keys($vars);
    }

    /**
     * @return bool
     */
    public function mustUseAntiCsrf(): bool
    {
        return $this->anti_csrf;
    }
}
