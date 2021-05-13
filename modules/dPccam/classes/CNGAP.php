<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbObjectSpec;

/**
 * Description
 */
class CNGAP implements IShortNameAutoloadable {
  /** @var CMbObjectSpec */
  static $spec;

  /**
   * Get object spec
   *
   * @return CMbObjectSpec
   */
  static function getSpec() {
    if (self::$spec) {
      return self::$spec;
    }

    $spec = new CMbObjectSpec();
    $spec->dsn = 'ccamV2';
    $spec->init();

    return self::$spec = $spec;
  }

  /**
   * Return the current version and the next available version of the NGAP database
   *
   * @return array (string current version, string next version)
   */
  public static function getDatabaseVersions() {
    return array(
      "38387" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "code" => "= 'G'",
          ),
        )
      ),
      "39893" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "code" => "= 'CNP'",
            "tarif" => "= '39'",
          ),
        )
      ),
      "41279-41294" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "code" => "= 'APC'",
          ),
        )
      ),
      "41942" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "code" => "= 'CCX'",
          ),
        )
      ),
      "42076" => array(
        array(
          "table_name" => "associations_ngap",
          "filters" => array(
            "code" => "= 'MCX'",
            "associations" => "LIKE '%C|%'",
          ),
        )
      ),
      "42640" => array(
        array(
          "table_name" => "codes_ngap",
          "filters" => array(
            "code" => "= 'MCU'",
          ),
        )
      ),
      "43241" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "tarif_ngap" => "= 'AMY'",
            "coef_max" => "= 30.50'",
          ),
        )
      ),
      "43490" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "tarif_ngap" => "= 'U45'",
          ),
        )
      ),
      "43596" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "tarif_ngap" => "= 'IC'",
          ),
        )
      ),
      "45331" => array(
        array(
          "table_name" => "specialite_to_tarif_ngap",
          "filters" => array(
            "tarif_id" => "= 718",
            "specialite" => "= 32",
          ),
        )
      ),
      "46181" => array(
        array(
          "table_name" => "specialite_to_tarif_ngap",
          "filters" => array(
            "tarif_id" => "= 54",
            "specialite" => "= 21",
          ),
        )
      ),
      "46863" => array(
        array(
          "table_name" => "tarif_ngap",
          "filters" => array(
            "code" => "= 'APC'",
            "complement_ferie" => "= 1",
          ),
        )
      ),
      "46925" => array(
        array(
          "table_name" => "specialite_to_tarif_ngap",
          "filters" => array(
            "tarif_ngap.code" => "= 'MA'",
            "specialite_to_tarif_ngap.specialite" => "= 2",
          ),
          "ljoin" => array(
            "tarif_ngap" => "tarif_ngap.`tarif_ngap_id` = specialite_to_tarif_ngap.`tarif_id`"
          )
        )
      ),
      "46926" => array(
        array(
          "table_name" => "codes_ngap",
          "filters" => array(
            "code" => "= 'MSF'",
          )
        )
      ),
      "46927" => array(
        array(
          "table_name" => "codes_ngap",
          "filters" => array(
            "code" => "= 'AKI'",
          )
        )
      ),
    );
  }
}
