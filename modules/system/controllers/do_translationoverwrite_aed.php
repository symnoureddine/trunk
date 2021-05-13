<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CValue;
use Ox\Core\SHM;

/**
 * AED translation overwrite
 */
$language = CValue::post("language", "fr");

$do = new CDoObjectAddEdit("CTranslationOverwrite", "translation_id");
$do->doIt();

SHM::remKeys("locales-$language-*");
