<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\CompteRendu;

use Exception;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;

/**
 * Gestion de correpondants dans les documents
 */
class CCorrespondantCourrier extends CMbObject {
  // DB Table key
  public $correspondant_courrier_id;

  // DB References
  public $compte_rendu_id;

  // Meta
  public $object_id;
  public $object_class;
  public $_ref_object;

  // DB Fields
  public $tag;
  public $quantite;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'correspondant_courrier';
    $spec->key   = 'correspondant_courrier_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["compte_rendu_id"] = "ref class|CCompteRendu notNull cascade back|correspondants_courrier";
    $props["object_id"]    = "ref notNull class|CStoredObject meta|object_class back|correspondants_courrier";
    $props["object_class"] = "enum list|CMedecin|CPatient|CCorrespondantPatient notNull";
    $props["quantite"]     = "num pos notNull min|1 default|1";
    $props["tag"]          = "str";
    return $props;
  }


  /**
   * @param CStoredObject $object
   * @deprecated
   * @todo redefine meta raf
   * @return void
   */
  public function setObject(CStoredObject $object) {
    CMbMetaObjectPolyfill::setObject($this, $object);
  }

  /**
   * @param bool $cache
   * @deprecated
   * @todo redefine meta raf
   * @return mixed
   * @throws Exception
   */
  public function loadTargetObject($cache = true) {
    return CMbMetaObjectPolyfill::loadTargetObject($this, $cache);
  }

  /**
   * @inheritDoc
   * @todo remove
   */
  function loadRefsFwd() {
    parent::loadRefsFwd();
    $this->loadTargetObject();
  }
}
