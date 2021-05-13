<?php
/**
 * @package Mediboard\Maidis
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Maidis\Mapper;

use DateTime;
use Ox\Import\Framework\Mapper\AbstractMapper;

/**
 * Description
 */
abstract class AbstractMaidisMapper extends AbstractMapper
{
    protected function convertDate(?string $date): ?DateTime
    {
        if ($date) {
            return (DateTime::createFromFormat('Ymd', $date)) ?: null;
        }

        return null;
    }

    protected function concertDateTime(?string $datetime): ?DateTime
    {
        if ($datetime) {
            if (preg_match('/^\d{8}\s(\d{2}:?){2}$/', $datetime)) {
                return (DateTime::createFromFormat('Ymd H:i', $datetime)) ?: null;
            }
            elseif (preg_match('/^\d{8}\s(\d{2}:?){3}$/', $datetime)) {
                return (DateTime::createFromFormat('Ymd H:i:s', $datetime)) ?: null;
            }
        }

        return null;
    }

    protected function buildInfosFromMultipleFields(...$remarques): ?string
    {
        $remarques = array_filter($remarques);

        return implode("\n", $remarques);
    }

    protected function sanitizeLine(?string $line): ?string
    {
        return str_replace('$', ' ', $line);
    }
}
