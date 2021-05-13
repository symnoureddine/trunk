<?php
/**
 * @package Mediboard\Stock
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Stock;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

class CConfigurationStock extends AbstractConfigurationRegister {

  /**
   * @return mixed
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "dPstock" => array(
            "CProductStock"        => array(
              "advanced_bargraph"        => "bool default|0",
              "hide_bargraph"            => "bool default|0",
              "allow_quantity_fractions" => "bool default|0",
            ),
            "CProductStockGroup"   => array(
              "infinite_quantity"  => "bool default|0",
              "pagination_size"    => "enum list|10|15|20|25|30|50|100|200 default|30",
              "negative_allowed"   => "bool default|1",
              "use_validation_mvt" => "bool default|0",
            ),
            "CProductStockService" => array(
              "infinite_quantity" => "bool default|0",
              "pagination_size"   => "enum list|10|15|20|25|30|50|100|200 default|30",
            ),
            "CProductReference"    => array(
              "show_cond_price" => "bool default|1",
              "pagination_size" => "enum list|10|15|20|25|30|50|100|200 default|15",
            ),
            "CProduct"             => array(
              "pagination_size" => "enum list|10|15|20|25|30|50|100|200 default|15",
              "use_renewable"   => "bool default|1",
            ),
            "CProductDelivery"     => array(
              "auto_dispensation" => "bool default|0",
            ),
          )
        )
      )
    );
  }
}