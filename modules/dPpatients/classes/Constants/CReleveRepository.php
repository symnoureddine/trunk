<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Constants;

use Exception;

class CReleveRepository
{
    /**
     * @param $data
     */
    public function addConstants($data)
    {
        // todo
    }

    /**
     * @param CConstantFilter $filter
     * @param int|null        $offset
     *
     * @return CAbstractConstant|null
     * @throws Exception
     */
    public function loadConstant(CConstantFilter $filter, ?int $offset = null): ?CAbstractConstant
    {
        $limit = $offset !== null ? "$offset,1" : "1";
        $filter->setLimit($limit);
        $constants = $filter->getResults();

        return !$constants ? null : reset($constants);
    }

    /**
     * @param CConstantFilter $filter
     *
     * @return CAbstractConstant[]
     * @throws Exception
     */
    public function loadConstants(CConstantFilter $filter): array
    {
        return $filter->getResults();
    }

    /**
     * @param CConstantFilter $filter
     *
     * @return int
     * @throws Exception
     */
    public function countConstants(CConstantFilter $filter): int
    {
        return $filter->countResults();
    }
}
