<?php
/**
 * @package Mediboard\Import
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Spec;

use Ox\Core\Specification\AndX;
use Ox\Core\Specification\Enum;
use Ox\Core\Specification\InstanceOfX;
use Ox\Core\Specification\IsNull;
use Ox\Core\Specification\NotNull;
use Ox\Core\Specification\OrX;
use Ox\Core\Specification\SpecificationInterface;

/**
 * Description
 */
class EvenementPatientSpecBuilder
{
    private const FIELD_ID        = 'external_id';
    private const FIELD_PATIENT   = 'patient_id';
    private const FIELD_PRATICIEN = 'praticien_id';
    private const FIELD_LIBELLE   = 'libelle';
    private const FIELD_TYPE      = 'type';
    private const FIELD_DATE      = 'date';

    public function build(): SpecificationInterface
    {
        return new AndX(
            ...[
                   NotNull::is(self::FIELD_ID),
                   NotNull::is(self::FIELD_PATIENT),
                   NotNull::is(self::FIELD_PRATICIEN),
                   NotNull::is(self::FIELD_LIBELLE),
                   $this->getDateSpec(),
                   $this->getTypeSpec(),
               ]
        );
    }

    private function getDateSpec(): SpecificationInterface
    {
        return new AndX(
            NotNull::is(self::FIELD_DATE),
            InstanceOfX::is(self::FIELD_DATE, \DateTime::class)
        );
    }

    private function getTypeSpec(): SpecificationInterface
    {
        return new OrX(
            IsNull::is(self::FIELD_TYPE),
            Enum::is(self::FIELD_TYPE, ['sejour', 'intervention', 'evt']),

        );
    }
}
