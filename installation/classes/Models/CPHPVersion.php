<?php
/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Installation\Models;

/**
 * PHP version prerequisite
 */
class CPHPVersion extends CPrerequisite
{

    const VERSION_REQUIRE = '7.3';

    /**
     * Compare PHP version
     *
     * @param bool $strict Check also warnings
     *
     * @return bool
     * @see parent::check
     *
     */
    public function check($strict = true): bool
    {
        return PHP_VERSION >= $this->name;
    }

    /**
     * @return string
     */
    public function getVersionInstalled(): string
    {
        return PHP_VERSION;
    }

    /**
     * Return all instances of self
     *
     * @return self
     */
    public function getAll(): self
    {
        $php              = new self();
        $php->name        = self::VERSION_REQUIRE;
        $php->mandatory   = true;
        $php->description = "Version de PHP5.6 >= 7.1";

        return $php;
    }

}
