<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir;

use Ox\Core\CSetup;

class CSetupFhir extends CSetup {
  function __construct() {
    parent::__construct();

    $this->mod_name = "fhir";
    $this->makeRevision("0.0");

    $this->makeRevision("0.01");

    $query = "CREATE TABLE `receiver_fhir` (
                `receiver_fhir_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `OID` VARCHAR (255),
                `synchronous` ENUM ('0','1') NOT NULL DEFAULT '1',
                `monitor_sources` ENUM ('0','1') NOT NULL DEFAULT '1',
                `nom` VARCHAR (255) NOT NULL,
                `libelle` VARCHAR (255),
                `group_id` INT (11) UNSIGNED NOT NULL DEFAULT '0',
                `actif` ENUM ('0','1') NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;
              ALTER TABLE `receiver_fhir`
                ADD INDEX (`nom`),
                ADD INDEX (`group_id`);";
    $this->addQuery($query);

    $query = "CREATE TABLE `exchange_fhir` (
                `exchange_fhir_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `encoding_type` ENUM ('JSON','XML'),
                `group_id` INT (11) UNSIGNED NOT NULL DEFAULT '0',
                `date_production` DATETIME NOT NULL,
                `sender_id` INT (11) UNSIGNED,
                `sender_class` ENUM ('CSenderFTP','CSenderSOAP','CSenderMLLP','CSenderFileSystem'),
                `receiver_id` INT (11) UNSIGNED,
                `type` VARCHAR (255),
                `sous_type` VARCHAR (255),
                `date_echange` DATETIME,
                `message_content_id` INT (11) UNSIGNED,
                `acquittement_content_id` INT (11) UNSIGNED,
                `statut_acquittement` VARCHAR (255),
                `message_valide` ENUM ('0','1') DEFAULT '0',
                `acquittement_valide` ENUM ('0','1') DEFAULT '0',
                `id_permanent` VARCHAR (255),
                `object_id` INT (11) UNSIGNED,
                `object_class` VARCHAR (80),
                `reprocess` MEDIUMINT (9) UNSIGNED DEFAULT '0',
                `master_idex_missing` ENUM ('0','1') DEFAULT '0'
              )/*! ENGINE=MyISAM */;
            ALTER TABLE `exchange_fhir`
                ADD INDEX (`group_id`),
                ADD INDEX (`date_production`),
                ADD INDEX (`sender_id`),
                ADD INDEX (`receiver_id`),
                ADD INDEX (`date_echange`),
                ADD INDEX (`message_content_id`),
                ADD INDEX (`acquittement_content_id`),
                ADD INDEX (`object_id`),
                ADD INDEX (`object_class`);";
    $this->addQuery($query);

    $this->makeRevision("0.02");
    $query = "ALTER TABLE `receiver_fhir`
                CHANGE `actif` `actif` ENUM ('0','1') NOT NULL DEFAULT '1',
                ADD `role` ENUM ('prod','qualif') NOT NULL DEFAULT 'prod';";
    $this->addQuery($query);

    $this->makeRevision("0.03");
    $query = "ALTER TABLE `receiver_fhir`
                ADD `exchange_format_delayed` SMALLINT (4) UNSIGNED DEFAULT '60';";
    $this->addQuery($query);

    $this->makeRevision("0.04");
    $query = "ALTER TABLE `exchange_fhir`
                ADD INDEX( `receiver_id`, `date_echange`),
                ADD INDEX( `sender_id`, `sender_class`, `date_echange`);";
    $this->addQuery($query);

    $this->makeRevision("0.05");

    $query = "ALTER TABLE `exchange_fhir`
                ADD `emptied` ENUM('0', '1') NOT NULL DEFAULT '0';";
    $this->addQuery($query);

    $query = "UPDATE `exchange_fhir`
                SET `emptied` = '1'
                WHERE `message_content_id` IS NULL
                AND `acquittement_content_id` IS NULL";
    $this->addQuery($query);

    $query = "ALTER TABLE `exchange_fhir`
                ADD INDEX `emptied_production` (`emptied`, `date_production`);";
    $this->addQuery($query);

    $this->makeRevision("0.06");

    $query = "ALTER TABLE `exchange_fhir`
                ADD `response_datetime` DATETIME;";
    $this->addQuery($query);

    $this->makeRevision("0.07");

    $query = "ALTER TABLE `exchange_fhir`
                CHANGE `date_echange` `send_datetime` DATETIME;";
    $this->addQuery($query);

    $this->makeRevision("0.08");

    $query = "ALTER TABLE `exchange_fhir`
                ADD INDEX `ots` (`object_class`, `type`, `sous_type`);";
    $this->addQuery($query);

    $this->makeRevision("0.09");

    $query = "ALTER TABLE `receiver_fhir`
                ADD `type` VARCHAR (255);";
    $this->addQuery($query);

    $this->makeRevision("0.10");

    $query = "ALTER TABLE `receiver_fhir`
                ADD `use_specific_handler` ENUM ('0','1') DEFAULT '0';";
    $this->addQuery($query);

    $this->makeRevision("0.11");

    $query = "ALTER TABLE `exchange_fhir`
                CHANGE `encoding_type` `format` VARCHAR (255)";
    $this->addQuery($query);

    $this->makeRevision("0.12");
    $this->setModuleCategory("interoperabilite", "echange");

    $this->mod_version = "0.13";
  }
}