<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Forms;

use Ox\Core\CSetup;

class CSetupForms extends CSetup {
  function __construct() {
    parent::__construct();

    $this->mod_name = "forms";
    $this->makeRevision("0.0");

    $this->makeRevision("0.01");
    $this->setModuleCategory("parametrage", "metier");

    $this->makeRevision('0.02');
    $query = 'CREATE TABLE `ex_class_field_tag_item` (
                `ex_class_field_tag_item_id` INT(11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ex_class_field_id`          INT(11) UNSIGNED NOT NULL,
                `tag`                        VARCHAR(255)     NOT NULL,
                INDEX (`ex_class_field_id`)
              )/*! ENGINE=MyISAM */;';
    $this->addQuery($query);

    $this->mod_version = '0.03';
  }
}
