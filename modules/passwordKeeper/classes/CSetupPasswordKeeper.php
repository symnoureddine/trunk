<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PasswordKeeper;

use Ox\Core\CSetup;

class CSetupPasswordKeeper extends CSetup {
  function __construct() {
    parent::__construct();

    $this->mod_name = "passwordKeeper";
    $this->makeRevision("0.0");

    $query = "CREATE TABLE `password_keeper` (
      `password_keeper_id` INT(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
      `keeper_name`        VARCHAR(50)  NOT NULL,
      `is_public`          BOOLEAN      DEFAULT 0,
      `iv`                 VARCHAR(255) NOT NULL,
      `sample`             VARCHAR(255) NOT NULL,
      `user_id`            INT(11)      UNSIGNED NOT NULL,
      PRIMARY KEY (`password_keeper_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $query = "CREATE TABLE `password_category` (
      `category_id`        INT(11)     UNSIGNED NOT NULL AUTO_INCREMENT,
      `category_name`      VARCHAR(50) NOT NULL,
      `password_keeper_id` INT(11)     UNSIGNED NOT NULL,
      PRIMARY KEY (`category_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $query = "CREATE TABLE `password_entry` (
      `password_id`          INT(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
      `password_description` VARCHAR(50)  NOT NULL,
      `password`             VARCHAR(255) NOT NULL,
      `password_last_change` DATETIME     NOT NULL,
      `iv`                   VARCHAR(255) NOT NULL,
      `password_comments`    TEXT,
      `category_id`          INT(11)      UNSIGNED NOT NULL,
      PRIMARY KEY (`password_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $this->makeRevision('0.01');
    $query = "DROP TABLE `password_entry`;";
    $this->addQuery($query);

    $query = "DROP TABLE `password_category`;";
    $this->addQuery($query);

    $query = "DROP TABLE `password_keeper`;";
    $this->addQuery($query);

    $this->makeRevision('0.02');
    $query = "CREATE TABLE `keychain` (
      `keychain_id` INT(11)        UNSIGNED NOT NULL AUTO_INCREMENT,
      `name`        VARCHAR(50)    NOT NULL,
      `user_id`     INT(11)        UNSIGNED NOT NULL,
      `sample`      VARCHAR(255)   NOT NULL,
      `iv`          VARCHAR(255)   NOT NULL,
      `public`      ENUM('0', '1') NOT NULL DEFAULT '0',
      PRIMARY KEY (`keychain_id`),
      KEY (`user_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $query = "CREATE TABLE `keychain_entry` (
      `password_id`  INT(11)        UNSIGNED NOT NULL AUTO_INCREMENT,
      `keychain_id`  INT(11)        UNSIGNED NOT NULL,
      `object_id`    INT(11)        UNSIGNED,
      `object_class` VARCHAR(255),
      `label`        VARCHAR(50)    NOT NULL,
      `username`     VARCHAR(255),
      `password`     VARCHAR(255)   NOT NULL,
      `iv`           VARCHAR(255)   NOT NULL,
      `public`       ENUM('0', '1') NOT NULL DEFAULT '0',
      `comment`      TEXT,
      PRIMARY KEY (`password_id`),
      KEY (`keychain_id`),
      KEY `object` (`object_class`, `object_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $this->makeRevision('0.03');
    $query = "CREATE TABLE `keychain_challenge` (
      `keychain_challenge_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `keychain_id`           INT(11) UNSIGNED NOT NULL,
      `user_id`               INT(11) UNSIGNED NOT NULL,
      `last_success_date`     DATETIME,
      PRIMARY KEY (`keychain_challenge_id`),
      KEY (`keychain_id`),
      KEY (`user_id`)) /*! ENGINE=MyISAM */;";
    $this->addQuery($query);

    $this->makeRevision('0.04');
    $query = "ALTER TABLE `keychain_challenge`
                ADD `last_modification_date` DATETIME AFTER `user_id`;";
    $this->addQuery($query);

    $this->makeRevision('0.05');
    $query = "ALTER TABLE `keychain_challenge`
                ADD `creation_date` DATETIME AFTER `user_id`;";
    $this->addQuery($query);

    $this->makeRevision("0.06");
    $this->setModuleCategory("erp", "ox");

    $this->mod_version = '0.07';
  }
}
