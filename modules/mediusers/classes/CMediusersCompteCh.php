<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers;
use Ox\Core\CMbObject;
use Ox\Mediboard\Cabinet\CBanque;

class CMediusersCompteCh extends CMbObject {
  public $compte_ch_id;

  // DB Fields
  public $name;
  public $user_id;
  public $rcc;
  public $adherent;
  public $debut_bvr;
  public $banque_id;

  /** @var CBanque */
  public $_ref_banque;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'compte_ch';
    $spec->key   = 'compte_ch_id';
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["name"]      = "str notNull";
    $props["user_id"]   = "ref notNull class|CMediusers back|comptes_ch";
    $props["rcc"]       = "str";
    $props["adherent"]  = "str";
    $props["debut_bvr"] = "str maxLength|10";
    $props["banque_id"] = "ref class|CBanque show|0 back|compteCh";
    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->name;
  }

  /**
   * @return CBanque
   */
  function loadRefBanque() {
    return $this->_ref_banque = $this->loadFwdRef("banque_id", true);
  }
}