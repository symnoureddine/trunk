<?php
/**
 * @package Mediboard\Hprimxml
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hprimxml;

use Ox\Interop\Eai\CExchangeDataFormatConfig;

/**
 * Class CHprimXMLConfig
 */
class CHprimXMLConfig extends CExchangeDataFormatConfig {
  static $config_fields = array(
    // Encoding
    "encoding",
    "display_errors",
    
    // Digit
    "type_sej_hospi",
    "type_sej_ambu",
    "type_sej_urg",
    "type_sej_exte",
    "type_sej_scanner",
    "type_sej_chimio",
    "type_sej_dialyse",
    "type_sej_pa",
    
    // Handle
    "use_sortie_matching",
    "fully_qualified",
    "check_similar",
    "att_system",
    "insc_integrated",
    "frais_divers",
    "prestation",
    
    // Format
    "encoding", 
    
    // Purge
    "purge_idex_movements",
    
    // Repair
    "repair_patient",

    // AppFine
    "handle_appFine",

    // TAMM_SIH
    "handle_tamm_sih"
  );
  
  public $hprimxml_config_id;

  // Digit
  public $type_sej_hospi;
  public $type_sej_ambu;
  public $type_sej_urg;
  public $type_sej_exte;
  public $type_sej_scanner;
  public $type_sej_chimio;
  public $type_sej_dialyse;
  public $type_sej_pa;
 
  // Handle
  public $use_sortie_matching;
  public $fully_qualified;
  public $check_similar;
  public $att_system;
  public $insc_integrated;
  public $frais_divers;
  public $prestation;

  // Format
  public $encoding;
  public $display_errors;
  
  // Purge
  public $purge_idex_movements;
  
  // Repair
  public $repair_patient;

  // AppFine
  public $handle_appFine;

  // TAMM-SIH
  public $handle_tamm_sih;

  public $_categories = array(
    // Format
    "format" => array(
      "encoding",
      "display_errors",
    ),
    
    // Handle
    "handle" => array(
      "use_sortie_matching",
      "fully_qualified",
      "check_similar",
      "att_system",
      "insc_integrated",
      "frais_divers",
      "prestation",
    ),
    
    // Digit
    "digit" => array(
      "type_sej_hospi",
      "type_sej_ambu",
      "type_sej_urg",
      "type_sej_exte",
      "type_sej_scanner",
      "type_sej_chimio",
      "type_sej_dialyse",
      "type_sej_pa",
    ),
    
    // Purge
    "purge" => array(
      "purge_idex_movements"
    ),
    
    // Repair
    "auto-repair" => array(
      "repair_patient"
    ),

    // AppFine
    "appFine" => array(
      "handle_appFine"
    ),

    "TAMM-SIH" => array(
      "handle_tamm_sih"
    )
  );

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "hprimxml_config";
    $spec->key   = "hprimxml_config_id";
    $spec->uniques["uniques"] = array("sender_id", "sender_class");
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props['sender_id'] .= ' back|config_hprimxml';
    
    // Encoding
    $props["encoding"]        = "enum list|UTF-8|ISO-8859-1 default|UTF-8";
    $props["display_errors"] = "bool default|1";

    // Digit
    $props["type_sej_hospi"]   = "str";
    $props["type_sej_ambu"]    = "str";
    $props["type_sej_urg"]     = "str";
    $props["type_sej_exte"]    = "str";
    $props["type_sej_scanner"] = "str";
    $props["type_sej_chimio"]  = "str";
    $props["type_sej_dialyse"] = "str";
    $props["type_sej_pa"]      = "str";
    
    // Handle
    $props["use_sortie_matching"] = "bool default|1";
    $props["fully_qualified"]     = "bool default|1";
    $props["check_similar"]       = "bool default|0";
    $props["att_system"]          = "enum list|acteur|application|système|finessgeographique|finessjuridique default|système";
    $props["insc_integrated"]     = "bool default|0";
    $props["frais_divers"]        = "enum list|fd|presta default|fd";
    $props["prestation"]          = "enum list|nom|idex default|nom";

    // Repair
    $props["repair_patient"] = "bool default|1";
    
    // Purge
    $props["purge_idex_movements"] = "bool default|0";

    // AppFine
    $props["handle_appFine"] = "bool default|0";

    // TAMM-SIH
    $props["handle_tamm_sih"] = "str";

    return $props;
  }

  /**
   * Get config fields
   *
   * @return array
   */
  function getConfigFields() {
    return $this->_config_fields = self::$config_fields;
  }
}
