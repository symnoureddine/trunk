<?php
/**
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Context;

use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Patients\IPatientRelated;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Contextual integration class, to integrate another page view inside Mediboard
 */
class CContextualIntegration extends CMbObject {
  static $patterns = array(
    "user",
    "ip",
    "ipp",
    "nda",
  );

  public $contextual_integration_id;

  public $active;
  public $group_id;
  public $url;
  public $title;
  public $description;
  public $icon_url;
  public $display_mode;

  public $_url;

  /** @var CContextualIntegrationLocation[] */
  public $_ref_locations;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "contextual_integration";
    $spec->key   = "contextual_integration_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                 = parent::getProps();
    $props["active"]       = "bool default|0";
    $props["group_id"]     = "ref class|CGroups notNull back|contextual_integrations";
    $props["url"]          = "url notNull";
    $props["title"]        = "str notNull";
    $props["description"]  = "text";
    $props["icon_url"]     = "str";
    $props["display_mode"] = "enum list|modal|popup|current_tab|new_tab"; //"enum list|modal|popup|tooltip|current_tab|new_tab|none"

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_view = $this->title;
  }

  /**
   * @inheritdoc
   */
  function store() {
    if (!$this->_id) {
      if (!$this->group_id) {
        $this->group_id = CGroups::loadCurrent()->_id;
      }
    }

    return parent::store();
  }

  /**
   * @return CContextualIntegrationLocation[]
   */
  function loadRefsLocations() {
    return $this->_ref_locations = $this->loadBackRefs("integration_locations");
  }

  /**
   * Makes the URL from variopus informations
   *
   * @param CMbObject $object Object to make the URL for
   *
   * @return string
   */
  function makeURL(CMbObject $object) {
    if ($this->_url) {
      return $this->_url;
    }

    $values = array_fill_keys(self::$patterns, null);

    $values["user"] = CUser::get()->user_username;
    $values["ip"]   = CAppUI::$instance->ip;

    if ($object instanceof IPatientRelated) {
      $patient = $object->loadRelPatient();
      $patient->loadIPP();
      $values["ipp"] = $patient->_IPP;
    }

    if ($object instanceof CSejour) {
      $object->loadNDA();
      $values["nda"] = $object->_NDA;
    }

    $url = $this->url;
    foreach ($values as $_from => $_value) {
      $url = str_replace("%$_from%", urlencode($_value), $url);
    }

    return $this->_url = $url;
  }
}