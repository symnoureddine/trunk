<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
namespace Ox\Mediboard\System;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;

class CSourceToViewSender extends CMbObject {
  // DB Table key
  public $source_to_view_sender_id;

  // DB fields
  public $source_id;
  public $sender_id;
  public $last_datetime;
  public $last_duration;
  public $last_size;
  public $last_status;
  public $last_count;

  // Form fields
  public $_last_age;

  /** @var CViewSenderSource */
  public $_ref_sender_source;
  
  /** @var CViewSender */
  public $_ref_sender;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "source_to_view_sender";
    $spec->key   = "source_to_view_sender_id";
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["sender_id"]     = "ref class|CViewSender notNull autocomplete|name back|sources_link";
    $props["source_id"]     = "ref class|CViewSenderSource notNull autocomplete|name back|senders_link";
    $props["last_datetime"] = "dateTime loggable|0";
    $props["last_duration"] = "float loggable|0";
    $props["last_size"]     = "num min|0 loggable|0";
    $props["last_status"]   = "enum list|triggered|uploaded|checked loggable|0";
    $props["last_count"]    = "num min|0 loggable|0";

    $props["_last_age"]     = "num";
    return $props;
  }

  /**
   * Charge le sender
   *
   * @return CViewSender
   */
  function loadRefSender() {
    $sender = $this->loadFwdRef("sender_id", true);
    $this->_last_age = CMbDT::minutesRelative($this->last_datetime, CMbDT::dateTime());
    return $this->_ref_sender = $sender;
  }

  /**
   * Charge la source
   *
   * @return CViewSenderSource
   */
  function loadRefSenderSource() {
    return $this->_ref_sender_source = $this->loadFwdRef("source_id", true);
  }
}
