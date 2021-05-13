<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\CMbSecurity;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\System\Forms\CExObject;

/**
 * Manage a password entry
 */
class CKeychainEntry extends CMbObject {
  /** @var integer Password ID */
  public $password_id;

  /** @var integer Keychain ID */
  public $keychain_id;

  /** @var string Entry's name */
  public $label;

  /** @var string Username */
  public $username;

  /** @var string Password */
  public $password;

  /** @var string Random initialisation vector */
  public $iv;

  /** @var boolean Is the password entry public? */
  public $public;

  /** @var string Password comment */
  public $comment;

  public $object_class;
  public $object_id;
  public $_ref_object;

  /** @var CKeychain Keychain */
  public $_ref_keychain;

  /** @var string Passphrase to use */
  public $_passphrase;

  /** @var bool */
  public $_renew;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();

    $spec->table = 'keychain_entry';
    $spec->key   = 'password_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props['keychain_id']  = 'ref class|CKeychain back|all_keychain_entries notNull';
    $props['label']        = 'str notNull maxLength|50';
    $props['username']     = 'str';
    $props['password']     = 'password notNull show|0 loggable|0 randomizable';
    $props['iv']           = 'str notNull show|0 loggable|0';
    $props['public']       = 'enum list|0|1 notNull default|0';
    $props['comment']      = 'text';
    $props['object_id']    = 'ref class|CMbObject meta|object_class cascade back|keychain_entries';
    $props['object_class'] = 'str class show|0';


    $props['_passphrase'] = 'password notNull show|0 loggable|0';

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_view = $this->label;
  }

  /**
   * @see parent::store()
   */
  function store() {
    if ($this->password === '') {
      $this->password = null;
    }

    if ((!$this->_id || $this->fieldModified('password')) && !$this->_renew) {
      $this->generateIV();

      $this->_passphrase = ($this->_passphrase) ?: CValue::sessionAbs('_passphrase');

      if (!$this->_passphrase) {
        return CAppUI::tr('common-error-Missing parameter: %s', CAppUI::tr('CKeychain-_passphrase-desc'));
      }

      // Because of encrypt() error if empty string provided
      if (!$this->password) {
        return CAppUI::tr('common-error-Missing parameter: %s', CAppUI::tr('CKeychain-password-desc'));
      }

      $this->password = $this->encrypt($this->_passphrase);
    }

    return parent::store();
  }

  /**
   * @inheritdoc
   */
  function canDeleteEx() {
    if (!$this->canDo()->edit) {
      return 'CKeychainEntry-error-You cannot delete this object because you are not the CKeychain owner';
    }

    return parent::canDeleteEx();
  }

  /**
   * @see parent::getPerm()
   */
  function getPerm($permType) {
    if (!$this->_id) {
      return parent::getPerm($permType);
    }

    $keychain = $this->loadKeychain();

    switch ($permType) {
      case CPermObject::READ:
      case CPermObject::EDIT:
        return ($this->public || $keychain->isMyKeychain());
        break;

      default:
        return parent::getPerm($permType);
    }
  }

  /**
   * Loads related keychain
   *
   * @return CKeychain
   */
  function loadKeychain() {
    return $this->_ref_keychain = $this->loadFwdRef('keychain_id', true);
  }

  /**
   * Génération d'un vecteur d'initialisation
   *
   * @return void
   */
  function generateIV() {
    $this->iv = CMbSecurity::generateIV();
  }

  /**
   * Chiffrement d'un mot de passe
   *
   * @param string $passphrase Phrase de passe à appliquer
   *
   * @return string
   */
  function encrypt($passphrase = null) {
    if (!$passphrase) {
      $passphrase = CValue::sessionAbs('_passphrase');
    }

    $keychain = new CKeychain();
    $keychain->load($this->keychain_id);

    if (!$keychain || !$keychain->_id) {
      return false;
    }

    $keychain->checkKeychain($passphrase);

    return CMbSecurity::encrypt(CMbSecurity::AES, CMbSecurity::CTR, $passphrase, $this->password, $this->iv);
  }

  /**
   * Déchiffrement d'un mot de passe
   *
   * @param string $passphrase Phrase de passe à appliquer
   *
   * @return string
   */
  function getPassword($passphrase = null) {
    $passphrase = ($passphrase) ?: CValue::sessionAbs('_passphrase');

    return CMbSecurity::decrypt(CMbSecurity::AES, CMbSecurity::CTR, $passphrase, $this->password, $this->iv);
  }

  /**
   * Renouvellement d'un mot de passe selon une nouvelle phrase de passe
   *
   * @param string $passphrase Phrase de passe à appliquer
   *
   * @return null|string
   */
  function renew($passphrase) {
    if (!$this->_id || !$passphrase) {
      return null;
    }

    $this->password = $this->getPassword();

    $this->generateIV();
    $this->password = $this->encrypt($passphrase);
    $this->_renew   = true;

    $msg = $this->store();

    $this->_renew = false;

    return $msg;
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
   * @return bool|CStoredObject|CExObject|null
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
