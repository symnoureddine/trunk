<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Soins;

use Ox\Core\Handlers\HandlerParameterBag;
use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

class CConfigurationSoins extends AbstractConfigurationRegister {
  /**
   * @inheritDoc
   */
  public function register() {
    CConfiguration::register(
      array(
        "CGroups" => array(
          "soins" => array(
            "dossier_soins"        => array(
              'display_filter_functions_discipline' => 'bool default|0',
              'display_operation_codage'            => 'bool default|0',
              'display_scoring_forms'               => 'bool default|1',
              'display_mandatory_forms'             => 'bool default|0',
              "feuille_trans_sejour_estimated"      => "bool default|0",
              "nb_days_hide_op"                     => "num min|0 default|0",
              "tab_prescription_med"                => "bool default|0",
              "manage_consult_reeducation"          => "bool default|0",
            ),
            "bilan"                => array(
              "hour_before" => "num min|0 default|12",
              "hour_after"  => "num min|0 default|24",
            ),
            "offline_sejour"       => array(
              "period" => "num min|0 default|72",
            ),
            "synthese"             => array(
              "transmission_date_limit" => "bool default|0",
              "show_technique_rea"      => "bool default|0",
              "can_edit_technique_rea"  => "enum list|all|praticien default|praticien localize",
              "show_prescription"       => "bool default|1",
              "show_directives"         => "bool default|0",
              "can_edit_directives"     => "enum list|all|praticien default|praticien localize",
            ),
            "suivi"                => array(
              "obs_chir"                      => "bool default|1",
              "obs_anesth"                    => "bool default|1",
              "obs_med"                       => "bool default|1",
              "obs_infirmiere"                => "bool default|0",
              "obs_reeducateur"               => "bool default|0",
              "obs_sagefemme"                 => "bool default|0",
              "obs_dentiste"                  => "bool default|1",
              "obs_dieteticien"               => "bool default|0",
            ),
            "CLit"                 => array(
              "align_right" => "bool default|1",
            ),
            "Transmissions"        => array(
              "cible_mandatory_trans" => "bool default|0",
              "trans_compact"         => "bool default|0",
              "period_modif_for_all"  => "num min|0 default|0",
              "see_old_trans"         => "bool default|1",
              "new_group"             => "bool default|0",
              "blocking_hour"         => "bool default|1",
              "email_send_modifs"     => "str",
              "show_priority_hight"   => "bool default|0",
            ),
            "Observations"         => array(
              "manual_alerts" => "bool default|0",
              "period_modif"  => "num min|0 default|0",
            ),
            "Sejour"               => array(
              "refresh_vw_sejours_frequency" => "enum localize list|disabled|600|1200|1800 default|disabled",
              "see_initiale_infirmiere"      => "bool default|0",
              "select_services_ids"          => "bool default|0",
            ),
            "Other"                => array(
              "show_charge_soins"            => "bool default|0",
              "max_time_modif_suivi_soins"   => "num min|0 max|23 default|12",
              "show_only_lit_bilan"          => "bool default|0",
              "ignore_allergies"             => "str default|aucun|ras|r.a.s.|0",
              "vue_condensee_dossier_soins"  => "bool default|0",
              "condensee_trans_limit_sejour" => "bool default|0",
              "default_motif_observation"    => "str",
              "see_volet_diet"               => "bool default|0",
            ),
            "UserSejour"           => array(
              "can_edit_user_sejour"     => "bool default|0",
              "type_affectation"         => "enum localize list|complet|segment default|complet",
              "see_global_users"         => "bool default|0",
              "soin_refresh_user_sejour" => "num min|0 default|10",
              "elts_colonne_regime"      => "custom tpl|inc_config_elts_regime",
              "elts_colonne_jeun"        => "custom tpl|inc_config_elts_jeun",
            ),
            "Commandes"            => array(
              "calcul_auto_dotation"       => "bool default|0",
              "calcul_auto_qty_order"      => "bool default|1",
              "status_only_service_stocks" => "bool default|1",
              "select_first_endowment"     => "bool default|1",
            )
          ),
        )
      )
    );
  }

  /**
   * @inheritDoc
   */
  public function getObjectHandlers(HandlerParameterBag $parameter_bag): void {
      $parameter_bag
          ->register(CObservationEmailHandler::class, false);
  }
}

