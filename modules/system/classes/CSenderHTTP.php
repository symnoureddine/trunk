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
 * Interoperability Sender HTTP
 */
class CSenderHTTP extends CInteropSender {
  // DB Table key
  public $sender_http_id;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'sender_http';
    $spec->key   = 'sender_http_id';

    return $spec;
  }

  /**
   * @inheritDoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["group_id"] .= " back|senders_http";
    $props["user_id"]  .= " back|expediteur_http";
    return $props;
  }

  /**
   * @inheritdoc
   */
  function loadRefsExchangesSources($put_all_sources = false) {
    $source_http = CExchangeSource::get("$this->_guid", CSourceHTTP::TYPE, true, $this->_type_echange, false, $put_all_sources);
    $this->_ref_exchanges_sources[$source_http->_guid] = $source_http;
  }
}
