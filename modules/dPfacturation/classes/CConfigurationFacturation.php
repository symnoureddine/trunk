<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Facturation;

use Ox\Core\CAppUI;
use Ox\Mediboard\System\AbstractConfigurationRegister;
use Ox\Mediboard\System\CConfiguration;

/**
 * Class CConfigurationFacturation
 */
class CConfigurationFacturation extends AbstractConfigurationRegister {

  /**
   * @return mixed
   */
  public function register() {
    $configurations_group = array(
      "CFactureEtablissement" => array(
        "use_temporary_bill"  => "bool default|0",
        "use_auto_cloture"    => "bool default|0",
        "view_bill"           => "bool default|1",
      ),
      "CFactureCabinet" => array(
        "use_auto_cloture"  => "bool default|1",
        "view_bill"         => "bool default|1",
      ),
      "CReglement" => array(
        "use_debiteur"                => "bool default|0",
        "add_pay_not_close"           => "bool default|0",
        "use_lock_acquittement"       => "bool default|0",
        "use_mode_default"            => "enum list|none|cheque|CB|especes|virement|BVR|autre default|none localize",
        "use_echeancier"              => "bool default|0",
        "echeancier_default_nb_month" => "num default|5 min|1",
        "echeancier_default_interest" => "numchar default|5",
      ),
      "CRetrocession" => array(
        "use_retrocessions" => "bool default|0"
      ),
      "CJournalBill" => array(
        "use_journaux"  => "bool default|0",
      ),
      "Other" => array(
        "use_strict_cloture"    => "bool default|0",
      ),
      "CRelance" => array(
        "use_relances"            => "bool default|0",
        "nb_days_first_relance"   => "num default|30 min|1",
        "nb_days_second_relance"  => "num default|60 min|1",
        "nb_days_third_relance"   => "num default|90 min|1",
        "add_first_relance"       => "num default|0",
        "add_second_relance"      => "num default|0",
        "add_third_relance"       => "num default|0",
        "nb_generate_pdf_relance" => "num default|20",
        "message_relance1_patient" => "text",
        "message_relance2_patient" => "text",
        "message_relance3_patient" => "text"
      )
    );

    if (CAppUI::conf("ref_pays") == "2") {
      $configurations_group["CFactureCabinet"]["can_deny_invoice"]       = "bool default|0";
      $configurations_group["CFactureEtablissement"]["can_deny_invoice"] = "bool default|0";
      $configurations_group["CEditPdf"] = array(
        "auteur_facture"              => "enum list|praticien|cabinet|etablissement default|etablissement",
        "fournisseur_presta"          => "enum list|praticien|cabinet|etablissement default|praticien",
        "auteur_facture_bvr"          => "enum list|praticien|cabinet|etablissement default|etablissement",
        "fournisseur_presta_bvr"      => "enum list|praticien|cabinet|etablissement default|praticien",
        "see_fct_bvr"                 => "bool default|0",
        "etab_adresse1"               => "str",
        "etab_adresse2"               => "str",
        "use_date_consult_traitement" => "bool default|0",
        "see_diag_justificatif"       => "bool default|1",
        "canton"                      => "enum list|AG|AI|AR|BE|BL|BS|FR|GE|GL|GR|JU|LU|NE|NW|OW|SG|SH|SO|SZ|TI|TG|UR|VD|VS|ZG|ZH|LI|A|D|F|I default|GE localize",
        "separate_by_prat"            => "bool default|0",
        "see_mandataire_justif"       => "bool default|1",
      );
      $configurations_group["CEditBill"] = array(
        "store_envoi_xml" => "bool default|0",
        "delay_send_xml"  => "num min|0 max|24",
        "ean_centre_impression" => "str default|2099999999998",
      );
      $configurations_group["Other"]["see_reject_xml"]          = "bool default|0";
      $configurations_group["Other"]["delfile_read_reject"]     = "bool default|1";
      $configurations_group["Other"]["autorise_excess_amount"]  = "bool default|1";
      $configurations_group["Other"]["use_coeff_bill"]          = "bool default|0";
      $configurations_group["Other"]["tag_EAN_fct"]             = "str default|EAN";
      $configurations_group["Other"]["tag_RCC_fct"]             = "str default|RCC";
      $configurations_group["Other"]["tag_RSS_praticien"]       = "str default|RSSCH";
      $configurations_group["Other"]["select_diagnostic_ambu"]  = "bool default|0";
      $configurations_group["CRelance"]["message_relance1_assur"] = "text";
      $configurations_group["CRelance"]["message_relance2_assur"] = "text";
      $configurations_group["CRelance"]["message_relance3_assur"] = "text";
    }

    CConfiguration::register(
      array(
        "CGroups" => array(
          "dPfacturation" => $configurations_group
        ),
        'CFunctions CGroups.group_id' => array(
          "dPfacturation" => array (
            "CFactureCategory" => array(
              "use_category_bill" => "enum list|hide|optionnal|obligatory default|hide localize",
              "decalage_right_num_bvr" => "num min|-10 max|10",
            )
          )
        ),
      )
    );
  }
}