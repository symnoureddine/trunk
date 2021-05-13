<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Mediboard\Admin\CPermModule;
use Ox\Mediboard\Admin\CPermObject;

/**
 * CanDo class
 *
 * Allow to check permissions on a module with redirect helpers
 */
class CCanDo {
  /** @var bool */
  public $read;

  /** @var bool */
  public $edit;

  /** @var bool */
  public $view;

  /** @var bool */
  public $admin;

  /** @var string */
  public $context;

  /** @var string|array Should not be used, find another redirection behavioural session mangagement */
  public $setValues;

  /**
   * Access denied, will stop current request and send an HTTP 403
   *
   * @return void
   */
  function denied() {
    if ($this->setValues) {
      if (is_scalar($this->setValues)) {
        CValue::setSession($this->setValues);
      }
      else {
        foreach ($this->setValues as $key => $value) {
          CValue::setSession($key, $value);
        }
      }
    }

    CAppUI::accessDenied($this->context);
  }

  /**
   * Check if the connected user has READ rights on the current page
   *
   * @param mixed $setValues Values to set in session
   *
   * @return void
   */
  function needsRead($setValues = null) {
    $this->setValues = $setValues;
    if (!$this->read) {
      $this->context .= " read permission";
      $this->denied();
    }
  }

  /**
   * Check if the connected user has EDIT rights on the current page
   *
   * @param mixed $setValues Values to set in session
   *
   * @return void
   */
  function needsEdit($setValues = null) {
    $this->setValues = $setValues;

    if (!$this->edit) {
      $this->context .= " edit permission";
      $this->denied();
    }
  }

  /**
   * Check if the connected user has ADMIN rights on the current page
   *
   * @param mixed $setValues Values to set in session
   *
   * @return void
   */
  function needsAdmin($setValues = null) {
    $this->setValues = $setValues;

    if (!$this->admin) {
      $this->context .= " admin permission";
      $this->denied();
    }
  }

  /**
   * Check if the object exists
   *
   * @param CMbObject $object    Object to check
   * @param mixed     $setValues Values to set in session
   *
   * @return void
   */
  function needsObject(CMbObject $object, $setValues = null) {
    $this->setValues = $setValues;

    if (!$object->_id) {
      CAppUI::notFound($object->_guid);
    }
  }


  /**
   * Check if the object exists
   *
   * @param CMbObject $object    Object to check
   * @param mixed     $setValues Values to set in session
   *
   * @return void
   */
  static function checkObject(CMbObject $object, $setValues = null) {
    global $can;
    $can->needsObject($object, $setValues);
  }

  /**
   * Check if the connected user has READ rights on the current page
   *
   * @return void
   */
  static function checkRead() {
    global $can;
    $can->needsRead();
  }

  /**
   * Return the global READ permission
   *
   * @return bool
   */
  static function read() {
    global $can;

    return $can->read;
  }

  /**
   * Check if the connected user has EDIT rights on the current page
   *
   * @return void
   */
  static function checkEdit() {
    global $can;
    $can->needsEdit();
  }

  /**
   * Return the global EDIT permission
   *
   * @return bool
   */
  static function edit() {
    global $can;

    return $can->edit;
  }

  /**
   * Check if the connected user has ADMIN rights on the current page
   *
   * @return void
   */
  static function checkAdmin() {
    global $can;
    $can->needsAdmin();
  }

  /**
   * Return the global ADMIN permission
   *
   * @return bool
   */
  static function admin() {
    global $can;

    return $can->admin;
  }

  /**
   * Dummy check method with no control
   * Enables differenciation between no-check and undefined-check views
   *
   * @return void
   */
  static function check() {
  }

  /**
   * Get permissions as array
   *
   * @param CPermModule|CPermObject $perm Permission level
   *
   * @return array
   */
  static function getPerms($perm) {
    $perms = array(
      'read' => false,
      'edit' => false,
      'deny' => false,
    );

    if ($perm->permission === '0') {
      $perms['deny'] = true;

      return $perms;
    }

    $perms['read'] = ($perm->permission >= '1');
    $perms['edit'] = ($perm->permission === '2');

    return $perms;
  }
}
