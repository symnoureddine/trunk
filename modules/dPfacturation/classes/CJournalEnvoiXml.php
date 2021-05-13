<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Facturation;

use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Mediboard\Facturation\CFactureCabinet;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Facture générique
 */
class CJournalEnvoiXml extends CMbObject {
  // DB Table key
  public $journal_envoi_xml_id;

  // DB Fields
  public $facture_id;
  public $facture_class;
  public $user_id;
  public $date_envoi;
  public $error;
  public $statut;

  // Object References
  /** @var CFactureCabinet|CFactureEtablissement */
  public $_ref_facture;
  /** @var CMediusers */
  public $_ref_user;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'journal_envoi_xml';
    $spec->key   = 'journal_envoi_xml_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["facture_id"]    = "ref class|CFacture meta|facture_class notNull back|journaux_envoi_xml";
    $props["facture_class"] = "enum list|CFactureCabinet|CFactureEtablissement notNull";
    $props["user_id"]       = "ref class|CMediusers notNull back|journal_envoi_xml";
    $props["date_envoi"]    = "dateTime notNull";
    $props["error"]         = "bool default|0";
    $props["statut"]        = "str";
    return $props;
  }

  /**
   * Chargement de la facture associée
   *
   * @return CFactureCabinet|CFactureEtablissement
   */
  function loadRefFacture() {
    return $this->_ref_user = $this->loadFwdRef("facture_id");
  }

  /**
   * @inheritdoc
   */
  function updatePlainFields() {
    parent::updatePlainFields();
    if (!$this->user_id) {
      $this->user_id = CMediusers::get()->_id;
    }
    if (!$this->date_envoi) {
      $this->date_envoi = CMbDT::datetime();
    }
  }
}