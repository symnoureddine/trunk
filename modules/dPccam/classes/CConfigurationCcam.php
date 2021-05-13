<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationCcam
 */
class CConfigurationCcam extends AbstractConfigurationRegister {

  /**
   * @return mixed
   */
  public function register() {
    CConfiguration::register(
      array(
        'CGroups' => array(
          'dPccam' => array(
            'codage'       => array(
              "use_cotation_ccam"             => "bool default|1",
              'rights'                        => 'enum list|user_rights|self localize default|user_rights',
              'lock_codage_ccam'              => 'enum list|open|password localize default|open',
              'display_order'                 => 'enum list|price|alpha localize default|price',
              'display_ald_cmu'               => 'bool default|0',
              'display_act_anesth'            => 'bool default|1',
              'display_act_anesth_exceptions' => 'custom tpl|inc_config_codes_ccam',
              'pmsi_extension_mandatory'      => 'bool default|0',
              'doc_extension_mandatory'       => 'bool default|0',
              'export_on_codage_lock'         => 'bool default|0',
              'block_incoherent_modifiers'    => 'bool default|0',
              'block_with_real_sejour_dates'  => 'bool default|0',
              'delay_auto_relock'             => 'num default|0',
              'use_getMaxCodagesActes'        => 'bool default|1',
              'add_acte_comp_anesth_auto'     => 'bool default|0',
              'allow_ccam_cotation_sejour'    => 'bool default|1',
              'allow_ngap_cotation_sejour'    => 'bool default|1',
              'precheck_modifiers_k_t'        => 'bool default|1',
            ),
            'ngap'         => array(
              'prefill_prescriptor' => 'bool default|1',
            ),
            'frais_divers' => array(
              'use_frais_divers_CConsultation'     => 'bool default|0',
              'use_frais_divers_COperation'        => 'bool default|0',
              'use_frais_divers_CSejour'           => 'bool default|0',
              'use_frais_divers_CEvenementPatient' => 'bool default|0',
            ),
            'motifs'       => array(
              'depassement_autorise' => 'bool default|0',
            ),
            'associations' => array(
              'mode'  => 'enum list|auto|manual localize default|auto',
              'rules' => array(
                'M'   => 'bool default|1',
                'G'   => 'bool default|1',
                'EA'  => 'bool default|1',
                'EB'  => 'bool default|1',
                'EC'  => 'bool default|1',
                'ED'  => 'bool default|1',
                'EE'  => 'bool default|1',
                'EF'  => 'bool default|1',
                'EG1' => 'bool default|1',
                'EG2' => 'bool default|1',
                'EG3' => 'bool default|1',
                'EG4' => 'bool default|1',
                'EG5' => 'bool default|1',
                'EG6' => 'bool default|1',
                'EG7' => 'bool default|1',
                'EH'  => 'bool default|1',
                'EI'  => 'bool default|1',
              ),
            ),
          ),
        ),
      )
    );
  }
}
