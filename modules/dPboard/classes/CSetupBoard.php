<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Board;

use Ox\Core\CSetup;

/**
 * dPboard
 */

/**
 * Setup du module Tableau de bord
 */
class CSetupBoard extends CSetup {

  /**
   * Constructeur
   */
  function __construct() {
    parent::__construct();

    $this->mod_name = "dPboard";

    $this->makeRevision("0.0");

    $this->makeRevision("0.1");

    // user authorization to see others user in TDB
    $this->addFunctionalPermQuery("allow_other_users_board", 'write_right');

    $this->makeRevision("0.2");
    $this->setModuleCategory("circuit_patient", "metier");

    $this->mod_version = "0.3";
  }
}
