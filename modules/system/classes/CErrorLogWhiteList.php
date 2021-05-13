<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CStoredObject;

/**
 * Error log
 */
class CErrorLogWhiteList extends CStoredObject {

  public $error_log_whitelist_id;
  public $user_id;
  public $datetime;
  public $hash;
  public $text;
  public $type;
  public $file_name;
  public $line_number;
  public $count;

  /**
   * @var CStoredObject|null
   */
  private $_ref_user;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec           = parent::getSpec();
    $spec->table    = "error_log_whitelist";
    $spec->key      = "error_log_whitelist_id";
    $spec->loggable = true;

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                = parent::getProps();
    $props["user_id"]     = "ref class|CUser unlink back|logs_whitelist";
    $props["datetime"]    = "dateTime notNull";
    $props["hash"]        = "str notNull";
    $props["text"]        = "text notNull";
    $props["type"]        = "text notNull";
    $props["file_name"]   = "str";
    $props["line_number"] = "num";
    $props["count"]       = "num notNull";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();
  }

  /**
   * Load the user who did the change
   *
   * @param bool $cache Use object cache
   *
   * @return CStoredObject
   * @throws \Exception
   */
  public function loadRefUser($cache = true) {
    return $this->_ref_user = $this->loadFwdRef('user_id', $cache);
  }
}
