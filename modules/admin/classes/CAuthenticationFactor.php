<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Admin;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbSecurity;
use Ox\Core\CMbString;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\SmsProviders\CLotSms;
use Ox\Mediboard\SmsProviders\CSMS;
use Ox\Mediboard\SmsProviders\CSMSDispatcher;
use Ox\Mediboard\SmsProviders\MessageDispatchException;
use Ox\Mediboard\SmsProviders\MessageException;
use Ox\Mediboard\System\CSourceSMTP;
use Ox\Mediboard\System\CUserAuthentication;

/**
 * Description
 */
class CAuthenticationFactor extends CMbObject {
  const MAX_ATTEMPTS = 3;
  const LEASE_HOURS = 18;

  /** @var integer Primary key */
  public $authentication_factor_id;

  /** @var integer CUser ID */
  public $user_id;

  /** @var integer Authentication priority */
  public $priority;

  /** @var boolean Factor enabled */
  public $enabled;

  /** @var string Authentication factor type */
  public $type;

  /** @var string */
  public $value;

  /** @var string Validation code */
  public $validation_code;

  /** @var integer Number of attempts */
  public $attempts;

  /** @var string Validation code generation date */
  public $generation_date;

  /** @var string Code validation date */
  public $validation_date;

  /** @var string */
  public $_validation_code;

  /** @var integer Remaining attempts */
  public $_remaining_attempts;

  /** @var boolean */
  public $_is_enabling;

  /** @var boolean */
  public $_is_resending;

  // Form field according to authentication factor type
  public $_phone_prefix;
  public $_phone_number;
  public $_email;

  /** @var CUser User reference */
  public $_ref_user;

  static $allowed_controllers = array(
    'do_validate_authentication_factor',
    'do_send_validation_code',
  );

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec                           = parent::getSpec();
    $spec->table                    = 'authentication_factor';
    $spec->key                      = 'authentication_factor_id';
    $spec->uniques['user_factor']   = array('user_id', 'type');
    $spec->uniques['factor_value']  = array('type', 'value');
    $spec->uniques['user_priority'] = array('user_id', 'priority');

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                    = parent::getProps();
    $props['user_id']         = 'ref class|CUser notNull back|authentication_factors';
    $props['priority']        = 'num notNull';
    $props['enabled']         = 'bool notNull default|0';
    $props['type']            = 'enum list|' . implode('|', static::getAllowedAuthenticationFactorTypes()) . ' notNull default|email';
    $props['value']           = 'str notNull';
    $props['validation_code'] = 'password show|0 loggable|0 ' . static::getValidationCodeSpecs();
    $props['attempts']        = 'num notNull default|0';
    $props['generation_date'] = 'dateTime';
    $props['validation_date'] = 'dateTime';

    $props['_validation_code'] = 'str';
    $props['_phone_prefix']    = 'str notNull';
    $props['_phone_number']    = 'str notNull';
    $props['_email']           = 'email notNull';

    return $props;
  }

  /**
   * Get allowed authentication factor types
   *
   * @return array
   */
  static function getAllowedAuthenticationFactorTypes() {
    // Email must not been disabled

    $types = array(
      'email',
    );

    if (CModule::getActive('smsProviders')) {
      $types[] = 'sms';
    }

    return $types;
  }

  /**
   * Tells if the "authentication_factor" table exists
   *
   * @return bool
   */
  static function isReady() {
    $that = new self();
    return $that->isInstalled();
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->formatFromType();

    $this->_view               = CAppUI::tr("{$this->_class}.type.{$this->type}") . " {$this->getObfuscatedValue()}";
    $this->_remaining_attempts = (static::MAX_ATTEMPTS - $this->attempts);
  }

  /**
   * Format value according to authentication factor type
   *
   * @return void
   */
  function formatFromType() {
    switch ($this->type) {
      case 'email':
        $this->_email = $this->value;
        break;

      case 'sms':
        list($this->_phone_prefix, $this->_phone_number) = explode('|', $this->value);
        break;

      default:
    }
  }

  /**
   * Format value according to authentication factor type
   *
   * @return void
   */
  function formatToType() {
    switch ($this->type) {
      case 'email':
        $this->value = $this->_email;
        break;

      case 'sms':
        $this->value = "{$this->_phone_prefix}|" . preg_replace('/[^0-9]/', '', $this->_phone_number);
        break;

      default:
    }
  }

  /**
   * Get validation code specifications
   *
   * @return string
   */
  static function getValidationCodeSpecs() {
    return 'alphaUpChars numChars minLength|6';
  }

  /**
   * @inheritdoc
   */
  function store() {
    if (!$this->_id) {
      // Force only own user creation
      $this->user_id = CUser::get()->_id;
      $this->setNextPriority();
    }

    if ($this->fieldModified('user_id') && ($this->user_id != CUser::get()->_id)) {
      return 'common-error-An error occurred';
    }

    $this->formatToType();

    if ($this->fieldModified('value') || $this->fieldModified('type')) {
      $this->enabled = '0';
    }

    return parent::store();
  }

  /**
   * @inheritdoc
   */
  function getPerm($permType) {
    $this->completeField('user_id');

    return (!$this->user_id || ($this->user_id == CUser::get()->_id));
  }

  /**
   * Sets next higher priority
   *
   * @return int|mixed
   */
  function setNextPriority() {
    if (!$this->user_id) {
      return $this->priority = 1;
    }

    $factors = $this->loadRefUser()->loadRefAuthenticationFactors();

    if (!$factors) {
      return $this->priority = 1;
    }

    $priorities = CMbArray::pluck($factors, 'priority');

    return $this->priority = max($priorities) + 1;
  }

  /**
   * Is the authentication factor enabled?
   *
   * @return bool
   */
  function isEnabled() {
    return ($this->enabled);
  }

  /**
   * Is the authentication factor being enabled?
   *
   * @return bool
   */
  function isEnabling() {
    return ($this->_is_enabling);
  }

  /**
   * Is the authentication factor being resending?
   *
   * @return bool
   */
  function isResending() {
    return ($this->_is_resending);
  }

  /**
   * Checks whether an authentication factor validation code has expired
   *
   * @return bool
   */
  function hasExpired() {
    $validation_period = CAppUI::conf('admin CAuthenticationFactor validation_period');
    $validation_period = ($validation_period > 0) ? $validation_period : 60;

    return ($this->generation_date && (CMbDT::minutesRelative($this->generation_date, CMbDT::dateTime()) >= $validation_period));
  }

  /**
   * Sends validation code according to authentication factor type
   *
   * @throws CMbException
   * @return void
   */
  function sendValidationCode() {
    if (!$this->_id) {
      throw new CMbException('CAuthenticationFactor-error-No authentication factor provided');
    }

    if (!$this->isEnabled() && !$this->isEnabling()) {
      throw new CMbException('CAuthenticationFactor-error-Authentication factor is not enabled');
    }

    if (!$this->isEnabling()) {
      if (!$this->checkAttempts()) {
        throw new CMbException('CAuthenticationFactor-error-Max attempt reached');
      }

      if ($this->validation_code && !$this->hasExpired() && !$this->isResending()) {
        throw new CMbException('CAuthenticationFactor-error-A validation code have already been transmitted');
      }
    }

    if ($msg = $this->setValidationCode()) {
      throw new CMbException($msg);
    }

    switch ($this->type) {
      case 'email':
        try {
          $this->sendValidationEmail();
        }
        catch (CMbException $e) {
          throw $e;
        }
        break;

      case 'sms':
        try {
          $this->sendValidationSMS();
        }
        catch (CMbException $e) {
          throw $e;
        }
        break;

      default:
        throw new CMbException('CAuthenticationFactor-error-No authentication type provided');
    }

    return;
  }

  /**
   * Sends a validation email
   *
   * @throws CMbException
   * @return void
   */
  function sendValidationEmail() {
    $subject = CAppUI::tr('CAuthenticationFactor-msg-Here is your validation code');

    $smarty = new CSmartyDP('modules/admin');
    $body   = $smarty->fetch('strong_authentication_mail.tpl');

    $patterns = array(
      '[url_mb]' => CAppUI::conf('external_url'),
      '[code]'   => $this->validation_code,
    );

    foreach ($patterns as $_search => $_replace) {
      $body = str_replace($_search, $_replace, $body);
    }

    $body = CMbString::purifyHTML($body);

    try {
      $source               = static::getSMTPSource();
      $source->_skip_buffer = true;

      CApp::sendEmail($subject, $body, null, null, null, $this->_email, $source);
    }
    catch (phpmailerException $e) {
      throw new CMbException($e->getMessage());
    }
    catch (CMbException $e) {
      throw $e;
    }

    return;
  }

  /**
   * Sends a validation SMS
   *
   * @return void
   * @throws CMbException
   */
  function sendValidationSMS() {
    try {
      $message = new CSMS();
      $message->setPhoneNumber($this->_phone_number, $this->_phone_prefix);
      $message->setMessage(CAppUI::tr('CAuthenticationFactor-msg-Here is your validation code: %s', $this->validation_code));

      $group = CGroups::loadCurrent();
      if (CLotSms::countAvailableSMSFor($group)) {
        $response = CSMSDispatcher::send($message);

        if ($response->getStatusCode() === 100) {
          CLotSms::incrementSMSFor($group, $response->getSMSNumber());
        }
      }

    }
    catch (MessageException | MessageDispatchException $e) {
      throw new CMbException($e->getMessage());
    }
    catch (CMbException $e) {
      throw $e;
    }

    return;
  }

  /**
   * Get authentication factor SMTP source
   *
   * @return CSourceSMTP|null
   */
  static function getSMTPSource() {
    return CSourceSMTP::getSystemSource();
  }

  /**
   * Force authentication validation
   *
   * @param integer $factor_id CAuthenticationFactor ID
   * @param boolean $rip       Stop script
   *
   * @return void
   */
  static function requireValidation($factor_id, $rip = true) {
    $content = CApp::fetch('admin', 'vw_validate_authentication_factor', array('factor_id' => $factor_id));

    echo $content;

    header('HTTP/1.0 403 Forbidden', true, 403);

    if ($rip) {
      CApp::rip();
    }
  }

  /**
   * Initialize a validation code to send
   *
   * @return mixed
   */
  function setValidationCode() {
    $this->validation_code = $this->generateValidationCode();
    $this->generation_date = CMbDT::dateTime();

    if ($this->hasValidationDateField()) {
      $this->validation_date = '';
    }

    if (!$this->isResending()) {
      $this->attempts = 0;
    }

    return $this->store();
  }

  /**
   * Tell if validation_field exists
   *
   * @return string
   */
  function hasValidationDateField() {
    return $this->isFieldInstalled('validation_date');
  }

  /**
   * Get used validation date
   *
   * @return string
   */
  function getValidationDate() {
    return ($this->hasValidationDateField()) ? $this->validation_date : $this->generation_date;
  }

  /**
   * Set validation date
   *
   * @return null|string
   */
  function setValidationDate() {
    if ($this->hasValidationDateField()) {
      $this->validation_date = CMbDT::dateTime();

      return $this->store();
    }

    return null;
  }

  /**
   * Enable factor
   *
   * @return null|string
   */
  function enableAuthenticationFactor() {
    $this->enabled = '1';

    return $this->store();
  }

  /**
   * Generates a temporary validation code
   *
   * @return bool|string
   */
  function generateValidationCode() {
    return CMbSecurity::getRandomPassword($this->_specs['validation_code'], true);
  }

  /**
   * Validates a validation code
   *
   * @param string $code Given code
   *
   * @throws CMbException
   * @return bool
   */
  function validateCode($code) {
    if (!$this->isEnabled() && !$this->isEnabling()) {
      throw new CMbException('CAuthenticationFactor-error-Authentication factor is not enabled');
    }

    if (!$this->checkAttempts()) {
      throw new CMbException('CAuthenticationFactor-error-Max attempt reached');
    }

    if ($this->hasExpired()) {
      $this->resetAuthenticationFactor(true);
      throw new CMbException('CAuthenticationFactor-error-Validation code has expired');
    }

    if (!$this->validation_code) {
      throw new CMbException('CAuthenticationFactor-error-No code to validate');
    }

    if ($this->checkValidationCode($code)) {
      if (!$this->isEnabling()) {
        $this->setValidationDate();
      }

      $this->resetAuthenticationFactor(true);

      return true;
    }

    return false;
  }

  /**
   * Resets authentication factor
   *
   * @param bool $reset_attempts Reset also number of attempts
   *
   * @return null|string
   */
  function resetAuthenticationFactor($reset_attempts = false) {
    $this->validation_code = '';

    if ($reset_attempts) {
      $this->attempts = 0;
    }

    return $this->store();
  }

  /**
   * Checks if a given validation code is correct
   *
   * @param string $code Given code
   *
   * @return bool
   */
  function checkValidationCode($code) {
    if (!$code) {
      return false;
    }

    return ($code === $this->validation_code);
  }

  /**
   * Checks if authentication factor is always valid
   *
   * @return bool
   */
  function checkAttempts() {
    $this->completeField('attempts');

    return ($this->attempts < static::MAX_ATTEMPTS);
  }

  /**
   * Increment number of attempts
   *
   * @return null|string
   */
  function incrementAttempts() {
    if ($this->isEnabling()) {
      return null;
    }

    $this->attempts++;

    return $this->store();
  }

  /**
   * Checks if authentication is unlocked
   *
   * @return bool
   */
  function checkLease() {
    if (!$this->isEnabled() || !$this->getValidationDate() || $this->validation_code) {
      return false;
    }

    return ($this->getValidationDate() >= static::getMinLeaseDate());
  }

  /**
   * Gets minimal lease start date
   *
   * @return string
   */
  static function getMinLeaseDate() {
    $hours = static::LEASE_HOURS;

    return CMbDT::dateTime("-{$hours} hours");
  }

  /**
   * Checks last authentication with factor within lease period
   *
   * @param CUser $user User
   *
   * @return bool|null
   */
  static function checkLastAuthenticationWithFactor(CUser $user) {
    if (!$user->_id) {
      return null;
    }

    $auth = new CUserAuthentication();
    $ds   = CSQLDataSource::get('std');

    $where = array(
      'user_id'                  => $ds->prepare('= ?', $user->_id),
      'authentication_factor_id' => 'IS NOT NULL',
      'datetime_login'           => $ds->prepare('>= ?', static::getMinLeaseDate()),
      'session_id'               => $ds->prepare('= ?', session_id()),
    );

    if (!$auth->loadObject($where, 'datetime_login DESC')) {
      return false;
    }

    return $auth->loadRefAuthenticationFactor()->checkLease();
  }

  /**
   * Loads CUser reference
   *
   * @return CUser|null
   */
  function loadRefUser() {
    return $this->_ref_user = $this->loadFwdRef('user_id', true);
  }

  /**
   * Format validation code message
   *
   * @return string
   */
  function getSentValidationCodeMessage() {
    switch ($this->type) {
      case 'email':
        return CAppUI::tr(
          'CAuthenticationFactor-msg-A validation code have been sent to address %s. Please, type it.',
          $this->getObfuscatedValue()
        );

      case 'sms':
        return CAppUI::tr(
          'CAuthenticationFactor-msg-A validation code have been sent to %s phone number. Please, type it.',
          $this->getObfuscatedValue()
        );

      default:
        return CAppUI::tr('CAuthenticationFactor-msg-A validation code have been sent. Please, type it.');
    }
  }

  /**
   * Get obfuscated contact value
   *
   * @return bool|string
   */
  function getObfuscatedValue() {
    switch ($this->type) {
      case 'email':
        list($user, $mail) = explode('@', $this->_email);

        return substr($user, 0, 2) . "...@{$mail}";

      case 'sms':
        return substr($this->_phone_number, 0, 4) . '...';

      default:
        return $this->value;
    }
  }
}
