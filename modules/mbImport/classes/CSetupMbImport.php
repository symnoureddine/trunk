<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport;

use Ox\Core\CSetup;

/**
 * MB Import Setup class
 */
class CSetupMbImport extends CSetup {
  /**
   * @inheritDoc
   */
  public function __construct() {
    parent::__construct();

    $this->mod_name = 'mbImport';
    $this->makeRevision('0.0');

    $this->setModuleCategory('import', 'echange');
    $this->addDependency('import', '0.03');

    $this->mod_version = '0.01';
  }
}
