<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;

use Ox\Core\CSetup;

/**
 * Class CSetupcda
 */
class CSetupCda extends CSetup {
  /**
   * @see parent::__construct
   */
  function __construct() {
    parent::__construct();

    $this->mod_name = "cda";
    $this->makeRevision("0.0");

    $this->makeRevision("0.01");

    $query = "CREATE TABLE `exchange_cda` (
                `exchange_cda_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `identifiant_emetteur` VARCHAR (255),
                `initiateur_id` INT (11) UNSIGNED,
                `group_id` INT (11) UNSIGNED NOT NULL,
                `date_production` DATETIME NOT NULL,
                `sender_id` INT (11) UNSIGNED,
                `sender_class` ENUM ('CSenderFTP','CSenderSOAP','CSenderFileSystem'),
                `receiver_id` INT (11) UNSIGNED,
                `type` VARCHAR (255),
                `sous_type` VARCHAR (255),
                `send_datetime` DATETIME,
                `response_datetime` DATETIME,
                `message_content_id` INT (11) UNSIGNED,
                `acquittement_content_id` INT (11) UNSIGNED,
                `statut_acquittement` VARCHAR (255),
                `message_valide` ENUM ('0','1') DEFAULT '0',
                `acquittement_valide` ENUM ('0','1') DEFAULT '0',
                `id_permanent` VARCHAR (255),
                `object_id` INT (11) UNSIGNED,
                `object_class` ENUM ('CSejour','CPatient','CConsultation','CCompteRendu','CFile'),
                `reprocess` TINYINT (4) UNSIGNED DEFAULT '0',
                `master_idex_missing` ENUM ('0','1') DEFAULT '0',
                `emptied` ENUM ('0','1') DEFAULT '0'
              )/*! ENGINE=MyISAM */;
              ALTER TABLE `exchange_cda` 
                ADD INDEX (`initiateur_id`),
                ADD INDEX (`group_id`),
                ADD INDEX (`date_production`),
                ADD INDEX sender (sender_class, sender_id),
                ADD INDEX (`receiver_id`),
                ADD INDEX (`send_datetime`),
                ADD INDEX (`response_datetime`),
                ADD INDEX (`message_content_id`),
                ADD INDEX (`acquittement_content_id`),
                ADD INDEX object (object_class, object_id);";
    $this->addQuery($query);

    $query = "CREATE TABLE `receiver_cda` (
                `receiver_cda_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `OID` VARCHAR (255),
                `synchronous` ENUM ('0','1') NOT NULL DEFAULT '1',
                `monitor_sources` ENUM ('0','1') NOT NULL DEFAULT '1',
                `nom` VARCHAR (255) NOT NULL,
                `libelle` VARCHAR (255),
                `group_id` INT (11) UNSIGNED NOT NULL,
                `actif` ENUM ('0','1') NOT NULL DEFAULT '1',
                `role` ENUM ('prod','qualif') NOT NULL DEFAULT 'prod',
                `exchange_format_delayed` INT (11) UNSIGNED DEFAULT '60'
              )/*! ENGINE=MyISAM */;
              ALTER TABLE `receiver_cda` 
                ADD INDEX (`nom`),
                ADD INDEX (`group_id`);";
    $this->addQuery($query);

    $this->makeRevision("0.02");

    $query = "ALTER TABLE `receiver_cda`
                ADD `type` VARCHAR (255);";
    $this->addQuery($query);

    $this->makeRevision("0.03");

    $query = "ALTER TABLE `receiver_cda`
                ADD `use_specific_handler` ENUM ('0','1') DEFAULT '0';";
    $this->addQuery($query);

    $this->makeRevision("0.04");
    $this->setModuleCategory("interoperabilite", "echange");

    $this->mod_version = "0.05";
  }
}
