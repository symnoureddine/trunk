<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Configuration;

use ArrayAccess;

/**
 * Description
 */
class Configuration implements ArrayAccess
{
    private $keys   = [];
    private $values = [];

    /**
     * Configuration constructor.
     *
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $_key => $_value) {
            $this->offsetSet($_key, $_value);
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return (isset($this->keys[$offset]));
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->values[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->keys[$offset]   = true;
        $this->values[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->keys[$offset], $this->values[$offset]);
        }
    }
}
