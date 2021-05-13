<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Description
 */
class ConstantConfigsPage extends HomePage {
  protected $module_name = "patients";
  protected $tab_name    = "configure";

  /**
   * Renseigne les seuils d'alert pour une constant donnée
   *
   * @param string $constant The name of the constant
   * @param int    $lower    The lower threshold
   * @param int    $upper    The upper threshold
   *
   * @return void
   */
  public function setWarningLevel($constant = 'pouls', $lower = 50, $upper = 130) {
    $this->accessControlTab('CConstantesMedicales');

    $this->driver->byId('cb_pouls')->click();
    $this->driver->byId('alerts_pouls')->click();
    $this->driver->byId('constant_alert_config__lower_threshold')->clear();
    $this->driver->byId('constant_alert_config__lower_threshold')->sendKeys($lower);
    $this->driver->byId('constant_alert_config__upper_threshold')->clear();
    $this->driver->byId('constant_alert_config__upper_threshold')->sendKeys($upper);
    $this->driver->byCss('button.save')->click();

  }
}
