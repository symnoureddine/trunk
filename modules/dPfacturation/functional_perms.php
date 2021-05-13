<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// Pr�f�rences par Module
use Ox\Core\CAppUI;
use Ox\Mediboard\System\CPreferences;

if (CAppUI::conf("ref_pays") == 2) {
  CPreferences::$modules["dPfacturation"] = array (
    "send_bill_unity"
  );
}