<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
namespace Ox\Mediboard\System;

use Ox\Interop\Eai\CInteropSender;

/**
 * Class CSenderFileSystem
 * Interoperability Sender File System
 */
class CSenderFileSystem extends CInteropSender {
  // DB Table key
  public $sender_file_system_id;

  public $after_processing_action;

  /** @var bool */
  public $_delete_file = true;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'sender_file_system';
    $spec->key   = 'sender_file_system_id';

    return $spec;
  }

  /**
   * @inheritDoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["group_id"] .= " back|senders_fs";
    $props["user_id"]  .= " back|expediteur_fs";

    $props["after_processing_action"] = "enum list|none|move|delete default|none";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function loadRefsExchangesSources($put_all_sources = false) {
    $source_fs                                       = CExchangeSource::get("$this->_guid", "file_system", true, $this->_type_echange, false, $put_all_sources);
    $this->_ref_exchanges_sources[$source_fs->_guid] = $source_fs;
  }

  /**
   * @inheritdoc
   */
  function read() {
    $this->loadRefsExchangesSources();
  }
}
