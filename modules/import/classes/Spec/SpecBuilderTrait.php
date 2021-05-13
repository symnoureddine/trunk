<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Spec;

use DateTime;
use Ox\Core\CAppUI;
use Ox\Core\Specification\AndX;
use Ox\Core\Specification\GreaterThanOrEqual;
use Ox\Core\Specification\InstanceOfX;
use Ox\Core\Specification\IsNull;
use Ox\Core\Specification\LessThanOrEqual;
use Ox\Core\Specification\Match;
use Ox\Core\Specification\MaxLength;
use Ox\Core\Specification\MinLength;
use Ox\Core\Specification\NotNull;
use Ox\Core\Specification\OrX;
use Ox\Core\Specification\SpecificationInterface;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 */
trait SpecBuilderTrait
{
    public function getEmailSpec(string $field_name): SpecificationInterface
    {
        return new OrX(
            IsNull::is($field_name),
            new AndX(
                Match::is($field_name, '/^[-a-z0-9\._\+]+@[-a-z0-9\.]+\.[a-z]{2,4}$/i'),
                MaxLength::is($field_name, 255)
            )
        );
    }

    public function getTelSpec(string $field_name, bool $conf = false): SpecificationInterface
    {
        $tel_spec = Match::is($field_name, '/^\d?(\d{2}[\s\.\-]?){5}$/');

        if ($conf) {
            return new AndX(NotNull::is($field_name), $tel_spec);
        }

        return new OrX(isNull::is($field_name), $tel_spec);
    }

    /**
     * @return SpecificationInterface
     */
    private function getCpSpec(string $field_name, bool $conf = false): SpecificationInterface
    {
        //        [$min_cp, $max_cp] = CPatient::getLimitCharCP();
        //
        //        // Do not check num because of 2A and 2B
        //        $spec_max = MaxLength::is($field_name, $max_cp);
        //        $spec_min = MinLength::is($field_name, $min_cp);
        //
        //        // Check conf for cp mandatory
        //        $spec_not_null = null;
        //        if ($conf) {
        //            return new AndX($spec_max, $spec_min, NotNull::is($field_name));
        //        }
        //
        //        return new OrX(IsNull::is($field_name), new AndX($spec_max, $spec_min));
        if ($conf) {
            return new AndX(
                MaxLength::is($field_name, 5),
                MinLength::is($field_name, 5),
                NotNull::is($field_name)
            );
        }

        return new OrX(
            new AndX(
                MaxLength::is($field_name, 5),
                MinLength::is($field_name, 5)
            ),
            IsNull::is($field_name)
        );
    }

    private function getNaissanceSpec(string $spec_name, bool $not_null = false): SpecificationInterface
    {
        if ($not_null) {
            return new AndX(
                NotNull::is($spec_name),
                LessThanOrEqual::is($spec_name, new DateTime()),
                GreaterThanOrEqual::is($spec_name, new DateTime('1850-01-01')),
                InstanceOfX::is($spec_name, DateTime::class)
            );
        }

        return new OrX(
            new AndX(
                LessThanOrEqual::is($spec_name, new DateTime()),
                GreaterThanOrEqual::is($spec_name, new DateTime('1850-01-01')),
                InstanceOfX::is($spec_name, DateTime::class)
            ),
            IsNull::is($spec_name)
        );
    }
}
