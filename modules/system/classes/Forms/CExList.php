<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Forms;

/**
 * ExList class
 * 
 * Définit une liste avec son nom, codée ou non, qui contiendra des élements de liste
 */
class CExList extends CExListItemsOwner {
  public $ex_list_id;
  
  public $name;
  public $coded;
  public $multiple;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "ex_list";
    $spec->key   = "ex_list_id";
    $spec->uniques["name"] = array("name");
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["name"]  = "str notNull seekable";
    $props["coded"] = "bool notNull default|0";
    $props["multiple"] = "bool default|0 show|0";
    return $props;
  }

  /**
   * @inheritdoc
   */
  function loadView(){
    parent::loadView();
    $this->loadBackRefs("concepts");
  }

  /**
   * @inheritdoc
   */
  function updateFormFields(){
    parent::updateFormFields();
    $this->_view  = $this->coded ? "# " : "";
    $this->_view .= $this->name;
  }
}
