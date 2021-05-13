<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbSecurity;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CAbonnement;

/**
 * Manage a password keeper
 */
class CKeychain extends CMbObject {
  const SAMPLE = "Actuellement, la réflexologie de l'orthodoxisation suffit à gérer la nucléarité off-shore, bonnes fêtes.";

  /** @var integer Keychain ID */
  public $keychain_id;

  /** @var string Keychain's name */
  public $name;

  /** @var integer Keychain owner */
  public $user_id;

  /** @var bool Is the keychain public? */
  public $public;

  /** @var string Random initialisation vector */
  public $iv;

  /** @var string Sample string for testing passphrase */
  public $sample;

  /** @var string Passphrase, needed for testing sample string */
  public $_passphrase;

  /** @var bool */
  public $_renew;

  /** @var CKeychainEntry[] All the related CKeychainEntry[] */
  public $_ref_all_keychain_entries;

  /** @var CKeychainChallenge[] */
  public $_ref_challenges;

  /** @var CKeychainChallenge */
  public $_ref_user_challenge;

  /** @var CAbonnement */
  public $_ref_abonnement;

  /** @var bool Do we have to update challenge */
  static $update_challenge;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();

    $spec->table = 'keychain';
    $spec->key   = 'keychain_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props['name']        = 'str notNull maxLength|50';
    $props['user_id']     = 'ref class|CMediusers back|keychains notNull back|keychains';
    $props['public']      = 'enum list|0|1 notNull default|0';
    $props['iv']          = 'str notNull show|0 loggable|0';
    $props['sample']      = 'password notNull show|0 loggable|0';
    $props['_passphrase'] = 'password notNull show|0 loggable|0';

    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_view = $this->name;
  }

  /**
   * @return null|string
   * @see parent::store()
   *
   */
  function store() {
    if ($this->_id && !$this->isMyKeychain()) {
      return 'CKeychain-error-You cannot edit this object because of you are not the owner';
    }

    if (!$this->_id || ($this->_id && $this->_renew && $this->_passphrase)) {
      $this->generateIV();
      $this->sample = $this->encrypt();
    }

    if (!$this->user_id) {
      $this->user_id = CMediusers::get()->_id;
    }

    $msg = parent::store();

    if ($this->_renew && $this->_passphrase) {
      $this->renew();
    }

    return $msg;
  }

  /**
   * @inheritdoc
   */
  function canDeleteEx() {
    if (!$this->isMyKeychain()) {
      return 'CKeychain-error-You cannot delete this object because you are not the owner';
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

    switch ($permType) {
      case CPermObject::READ:
        return ($this->public || $this->isMyKeychain());
        break;

      case CPermObject::EDIT:
        return $this->isMyKeychain();
        break;

      default:
        return parent::getPerm($permType);
    }
  }

  /**
   * Tells if current user is keychain's owner
   *
   * @return bool
   */
  function isMyKeychain() {
    return ($this->user_id === CMediusers::get()->_id);
  }

  /**
   * Tells if keychain is public
   *
   * @return bool
   */
  function isPublic() {
    return ($this->public == '1');
  }

  /**
   * Initialisation vector generation
   *
   * @return void
   */
  function generateIV() {
    $this->iv = CMbSecurity::generateIV();
  }

  /**
   * Sample string encryption
   *
   * @return string
   */
  function encrypt() {
    return CMbSecurity::encrypt(CMbSecurity::AES, CMbSecurity::CTR, $this->_passphrase, self::SAMPLE, $this->iv);
  }

  /**
   * Sample string string checking
   *
   * @param string $passphrase Given passphrase
   *
   * @return bool
   */
  function testSample($passphrase) {
    $decrypted = CMbSecurity::decrypt(CMbSecurity::AES, CMbSecurity::CTR, $passphrase, $this->sample, $this->iv);

    return ($decrypted === self::SAMPLE);
  }

  /**
   * Checks if a passphrase is corresponding to keychain
   *
   * @param string $passphrase Given passphrase
   *
   * @return void
   */
  function checkKeychain($passphrase) {
    if (!$this->checkPassphrase($passphrase)) {
      CAppUI::stepAjax('passwordKeeper-error-Wrong passphrase', UI_MSG_ERROR);
    }
  }

  /**
   * Checks if a passphrase is corresponding to keychain
   *
   * @param string $passphrase Given passphrase
   *
   * @return bool
   */
  function checkPassphrase($passphrase) {
    $test = $this->testSample($passphrase);

    if ($test && static::$update_challenge) {
      $this->updateChallenge();
    }

    return $test;
  }

  /**
   * Checks if HTTPS in use
   *
   * @return void
   */
  static function checkHTTPS() {
    if (empty($_SERVER["HTTPS"])) {
      CAppUI::stepAjax('passwordKeeper-error-HTTPS-required', UI_MSG_ERROR);
    }
  }

  /**
   * Try to get passphrase
   *
   * @return mixed
   */
  static function getPassphrase() {
    $_session_passphrase = CValue::sessionAbs('_passphrase');
    $_passphrase         = CValue::postOrSessionAbs('_passphrase');

    if (!$_passphrase) {
      CAppUI::stepAjax('common-error-Missing parameter: %s', UI_MSG_ERROR, CAppUI::tr('CKeychain-_passphrase-desc'));
    }

    if (!$_session_passphrase || ($_session_passphrase !== $_passphrase)) {
      static::$update_challenge = true;
    }

    return $_passphrase;
  }

  /**
   * Load all the related entries
   *
   * @return CKeychainEntry[]
   */
  function loadAllKeychainEntries() {
    return $this->_ref_all_keychain_entries = $this->loadBackRefs('all_keychain_entries');
  }

  /**
   * Loads keychain entries by perm
   *
   * @return CKeychainEntry[]
   */
  function loadVisibleKeychainEntries() {
    /** @var CKeychainEntry[] $keychain_entries */
    $keychain_entries = $this->loadBackRefs('all_keychain_entries');

    $entries = [];
    foreach ($keychain_entries as $_entry) {
      if ($this->isMyKeychain() || $_entry->public) {
        $entries[$_entry->_id] = $_entry;
      }
    }

    return $this->_ref_available_keychain_entries = $entries;
  }

  /**
   * Renouvellement de chaque mot de passe du trousseau
   *
   * @return void
   */
  function renew() {
    if (!$this->_passphrase) {
      return;
    }

    $this->loadAllKeychainEntries();

    foreach ($this->_ref_all_keychain_entries as $_entry) {
      $_entry->renew($this->_passphrase);
    }

    // Le phrase de passe a été modifiée : mise à jour des challenges associés
    $user_ids = CAbonnement::getSubscribers($this);

    foreach ($user_ids as $_user_id) {
      $this->updateUserChallenge($_user_id);
    }
  }

  /**
   * Récupère les challenges d'un trousseau
   *
   * @return CStoredObject[]|null
   */
  function loadChallenges() {
    return $this->_ref_challenges = $this->loadBackRefs('keychain_challenges');
  }

  /**
   * Load current user challenge
   *
   * @param integer $user_id CMediusers ID
   *
   * @return CKeychainChallenge|null
   */
  function loadUserChallenge($user_id = null) {
    $user_id = ($user_id) ?: CMediusers::get()->_id;

    $challenge              = new CKeychainChallenge();
    $challenge->keychain_id = $this->_id;
    $challenge->user_id     = $user_id;

    if ($challenge->loadMatchingObject()) {
      return $this->_ref_user_challenge = $challenge;
    }

    if ($msg = $challenge->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);

      return null;
    }

    return $this->_ref_user_challenge = $challenge;
  }

  /**
   * Met à jour la date de dernière mise à jour
   *
   * @param integer $user_id CMediusers ID
   *
   * @return null
   */
  function updateUserChallenge($user_id = null) {
    $user_id = ($user_id) ?: CMediusers::get()->_id;

    $challenge                         = $this->loadUserChallenge($user_id);
    $challenge->last_modification_date = CMbDT::dateTime();

    if ($msg = $challenge->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);

      return null;
    }

    return null;
  }

  /**
   * Update CKeychainChallenge
   *
   * @param integer $user_id CMediusers ID
   *
   * @return null|string
   */
  function updateChallenge($user_id = null) {
    static::$update_challenge = false;

    $challenge                    = $this->loadUserChallenge($user_id);
    $challenge->last_success_date = CMbDT::dateTime();

    if ($msg = $challenge->store()) {
      return $msg;
    }

    return null;
  }

  /**
   * Charge l'abonnement d'un utilisateur
   *
   * @param null $user_id CMediusers ID
   *
   * @return CAbonnement|null
   */
  function loadAbonnement($user_id = null) {
    if (!$this->_id) {
      return null;
    }

    $abonnement          = new CAbonnement();
    $abonnement->user_id = ($user_id) ?: CMediusers::get()->_id;
    $abonnement->setObject($this);
    $abonnement->loadMatchingObject();

    return $this->_ref_abonnement = $abonnement;
  }

  /**
   * Vérifie si un utilisateur est abonné
   *
   * @param null $user_id CMediusers ID
   *
   * @return int
   */
  function checkAbonnement($user_id = null) {
    return $this->loadAbonnement($user_id)->_id;
  }
}
