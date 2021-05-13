<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Entity;

use Ox\Import\Framework\Exception\ExternalReferenceException;

/**
 * Description
 */
class ExternalReference
{
    /** @var string */
    private $name;

    /** @var mixed */
    private $id;

    /** @var bool */
    private $mandatory;

    /**
     * ExternalReference constructor.
     *
     * @param string $name
     * @param mixed  $id
     * @param bool   $mandatory
     *
     * @throws ExternalReferenceException
     */
    public function __construct(string $name, $id, bool $mandatory)
    {
        //    if (!is_a($name, EntityInterface::class, true)) {
        //      throw new ExternalReferenceException('ExternalReference-error-Class %s is not an EntityInterface', $name);
        //    }

        if (is_null($id) && $mandatory) {
            throw new ExternalReferenceException('ExternalReference-error-Id is null for %s', $name);
        }

        $this->name      = $name;
        $this->id        = $id;
        $this->mandatory = $mandatory;
    }

    /**
     * @param string $name
     * @param mixed  $id
     *
     * @return self
     * @throws ExternalReferenceException
     */
    public static function getMandatoryFor(string $name, $id): self
    {
        return new self($name, $id, true);
    }

    /**
     * @param string $name
     * @param mixed  $id
     *
     * @return self
     * @throws ExternalReferenceException
     */
    public static function getNotMandatoryFor(string $name, $id): self
    {
        return new self($name, $id, false);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }
}
