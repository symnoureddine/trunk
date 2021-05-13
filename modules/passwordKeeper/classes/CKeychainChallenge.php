<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Description
 */
class CKeychainChallenge extends CMbObject {
  /** @var integer Primary key */
  public $keychain_challenge_id;

  /** @var integer CMediusers ID */
  public $user_id;

  /** @var integer CKeychain ID */
  public $keychain_id;

  /** @var string Creation date */
  public $creation_date;

  /** @var string Last modification date */
  public $last_modification_date;

  /** @var string Last success date */
  public $last_success_date;

  /** @var CMediusers */
  public $_ref_user;

  /** @var CKeychain */
  public $_ref_keychain;

  /** @var string */
  public $_rule;

  /** @var array */
  public $_triggered_rules = array();

  /** @var string */
  public $_date;

  /** @var string */
  public $_reference_date;

  // Order is important here; we perform a procedural check
  static $rules = array(
    'NoSuccess',
    'Weekly',
    'Daily',
  );

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec                       = parent::getSpec();
    $spec->table                = 'keychain_challenge';
    $spec->key                  = 'keychain_challenge_id';
    $spec->uniques['challenge'] = array('user_id', 'keychain_id');

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                           = parent::getProps();
    $props['user_id']                = 'ref class|CMediusers back|keychain_challenges notNull back|keychain_challenges';
    $props['keychain_id']            = 'ref class|CKeychain back|keychain_challenges cascade notNull';
    $props['creation_date']          = 'dateTime notNull';
    $props['last_modification_date'] = 'dateTime';
    $props['last_success_date']      = 'dateTime';

    $props['_rule']           = 'enum list|' . implode('|', static::$rules);
    $props['_date']           = 'date';
    $props['_reference_date'] = 'date';

    return $props;
  }

  /**
   * @inheritdoc
   */
  function store() {
    if (!$this->_id) {
      $this->creation_date = ($this->creation_date) ?: CMbDT::dateTime();
    }

    return parent::store();
  }

  /**
   * Charge l'utilisateur associé au challenge
   *
   * @param bool $cached Récupère l'objet en cache si possible
   *
   * @return CStoredObject|null
   */
  function loadRefUser($cached = true) {
    return $this->_ref_user = $this->loadFwdRef('user_id', $cached);
  }

  /**
   * Charge le trousseau associé au challenge
   *
   * @param bool $cached Récupère l'objet en cache si possible
   *
   * @return CStoredObject|null
   */
  function loadRefKeychain($cached = true) {
    return $this->_ref_keychain = $this->loadFwdRef('keychain_id', $cached);
  }

  /**
   * Vérifie si le challenge doit être envoyé à l'utilisateur
   *
   * @param string $date Date de référence
   *
   * @return bool
   */
  function checkToNotify($date = null) {
    $this->_date           = ($date) ?: CMbDT::date();
    $reference_date        = ($this->last_modification_date) ?: $this->creation_date;
    $this->_reference_date = CMbDT::date($reference_date);

    $rules = $this->checkRules();

    foreach ($rules as $_rule => $_match) {
      if ($this->inTriggeredRules($_rule)) {
        $this->_rule = $_rule;

        return $_match;
      }
    }

    return false;
  }

  /**
   * Checks implemented rules
   *
   * @return array
   */
  private function checkRules() {
    $rules = array();

    foreach (static::$rules as $_rule) {
      $rules[$_rule] = (is_callable(array($this, "checkRule{$_rule}"))) ? call_user_func(array($this, "checkRule{$_rule}"), $_rule) : false;
    }

    return $rules;
  }

  /**
   * Set a rule, meaning that it has been triggered
   *
   * @param string $rule Rule to trigger
   *
   * @return void
   */
  private function triggerRule($rule) {
    $this->_triggered_rules[$rule] = true;
  }

  /**
   * Checks if given rule triggered
   *
   * @param string $rule Rule to check
   *
   * @return bool
   */
  private function inTriggeredRules($rule) {
    return ($rule && isset($this->_triggered_rules[$rule]) && $this->_triggered_rules[$rule]);
  }

  /**
   * "No success" rule checking
   *
   * @param string $rule Rule
   *
   * @return bool
   */
  private function checkRuleNoSuccess($rule) {
    // Jamais utilisé à partir de l'abonnement
    if (!$this->last_success_date) {
      $this->triggerRule($rule);

      return true;
    }

    return false;
  }

  /**
   * "Weekly" rule checking
   *
   * @param string $rule Rule
   *
   * @return bool
   */
  private function checkRuleWeekly($rule) {
    $week_after = CMbDT::date('+1 week', $this->_reference_date);

    // Si le challenge a été créé ou modifié depuis au moins une semaine
    if (CMbDT::daysRelative($week_after, $this->_date) >= 7) {
      $this->triggerRule($rule);

      // Si le dernier succès date d'au moins sept jours
      return (CMbDT::daysRelative(CMbDT::date($this->last_success_date), $this->_date) >= 7);
    }

    return false;
  }

  /**
   * "Daily" rule checking
   *
   * @param string $rule Rule
   *
   * @return bool
   */
  private function checkRuleDaily($rule) {
    $day_after = CMbDT::date('+1 day', $this->_reference_date);

    // Si le challenge a été créé ou modifié depuis au moins un jour
    if (CMbDT::daysRelative($day_after, $this->_date) >= 1) {
      $this->triggerRule($rule);

      // Si le dernier succès date d'au moins un jour
      return (CMbDT::daysRelative(CMbDT::date($this->last_success_date), $this->_date) >= 1);
    }

    return false;
  }
}
