<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Etablissement;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * External group class (Etablissement externe)
 */
class CEtabExterne extends CMbObject {
  public $etab_id;  

  // DB Fields
  public $nom;
  public $raison_sociale;
  public $adresse;
  public $cp;
  public $ville;
  public $tel;
  public $fax;
  public $finess;
  public $siret;
  public $ape;
  /** @var bool If true, the establishement will be displayed first in the autocomplete */
  public $priority;
  public $provenance;
  public $destination;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'etab_externe';
    $spec->key   = 'etab_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["nom"]            = "str notNull confidential seekable";
    $props["raison_sociale"] = "str maxLength|50";
    $props["adresse"]        = "text confidential";
    $props["cp"]             = "str length|5";
    $props["ville"]          = "str maxLength|50 confidential";
    $props["tel"]            = "phone";
    $props["fax"]            = "phone";
    $props["finess"]         = "str length|9 confidential mask|9xS9S99999S9";
    $props["siret"]          = "str length|14";
    $props["ape"]            = "str maxLength|6 confidential";
    $props['priority']       = 'bool default|0';
    $props["provenance"]     = "enum list|1|2|3|4|5|6|7|8|R";
    $props["destination"]    = "enum list|0|" . implode("|", CSejour::$destination_values);
    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields () {
    parent::updateFormFields();
    $this->_view = $this->nom; 
  }
}
