<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;

CCanDo::checkAdmin();

$directory = CValue::get("directory");

if (!$directory) {
  return;
}

if (!is_readable($directory) || !is_dir($directory)) {
  CAppUI::stepAjax("Répertoire invalide", UI_MSG_ERROR);
}

$iterator         = new DirectoryIterator($directory);
$count_dirs       = 0;
$count_valid_dirs = 0;
$count_files      = 0;

foreach ($iterator as $_fileinfo) {
  if ($_fileinfo->isDot()) {
    continue;
  }

  if ($_fileinfo->isFile()) {
    $count_files++;
    continue;
  }

  if ($_fileinfo->isDir()) {
    $count_dirs++;

    if (strpos($_fileinfo->getFilename(), "CPatient-") === 0) {
      $count_valid_dirs++;
    }
  }
}

CAppUI::stepAjax("Contient %d fichiers", UI_MSG_OK, $count_files);
CAppUI::stepAjax("Contient %d dossiers, dont %d valides", UI_MSG_OK, $count_dirs, $count_valid_dirs);
