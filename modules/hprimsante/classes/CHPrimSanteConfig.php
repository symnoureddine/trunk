<?php
/**
 * @package Mediboard\Hprimsante
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hprimsante;

use Ox\Core\CMbFieldSpec;
use Ox\Interop\Eai\CExchangeDataFormatConfig;

/**
 * Config hprim sante
 */
class CHPrimSanteConfig extends CExchangeDataFormatConfig {

  static $config_fields = array(
    // Format
    "encoding",
    "strict_segment_terminator",
    "segment_terminator",

    //handle
    "action",
    "notifier_entree_reelle",
  );

  /** @var integer Primary key */
  public $hprimsante_config_id;

  // Format
  public $encoding;
  public $strict_segment_terminator;
  public $segment_terminator;

  public $action;
  public $notifier_entree_reelle;

  /**
   * @var array Categories
   */
  public $_categories = array(
    "format" => array(
      "encoding",
      "strict_segment_terminator",
      "segment_terminator",
    ),
    "handle" => array(
      "action",
      "notifier_entree_reelle",
    ),
  );

  /**
   * Initialize the class specifications
   *
   * @return CMbFieldSpec
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table  = "hprimsante_config";
    $spec->key    = "hprimsante_config_id";
    $spec->uniques["uniques"] = array("sender_id", "sender_class");
    return $spec;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();

    $props['sender_id'] .= ' back|config_hprimsante';

    // Encoding
    $props["encoding"]                  = "enum list|UTF-8|ISO-8859-1 default|UTF-8";
    $props["strict_segment_terminator"] = "bool default|0";
    $props["segment_terminator"]        = "enum list|CR|LF|CRLF";

    //handle
    $props["action"]                    = "enum list|IPP_NDA|Patient|Sejour|Patient_Sejour default|IPP_NDA";
    $props["notifier_entree_reelle"]     = "bool default|1";

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
