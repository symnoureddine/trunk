<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CHTTPClient;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\OpenData\CMedecinFileReader;
use Ox\Mediboard\OpenData\CMedecinImport;

CCanDo::checkEdit();

CView::checkin();

$files = glob(rtrim(CFile::$directory, '/\\') . '/upload/' . CMedecinImport::MEDECIN_FILE_NAME . '_*.txt');

$file_exists = true;
if (!$files) {
  CAppUI::stepAjax('CMedecinImport-no-csv-file', UI_MSG_WARNING);
  $file_exists = false;
}

$file_path = CMbArray::get($files, 0);
$file_size = filesize($file_path);

// Check which file is uptodate
if (count($files) > 1) {
  $last_modifs = array();

  foreach ($files as $_file) {
    $last_modifs[] = filemtime($_file);
  }

  $max_idx   = array_search(max($last_modifs), $last_modifs);
  $file_path = $files[$max_idx];

  // Clear the olds files
  foreach ($files as $_idx => $_file) {
    if ($_idx !== $max_idx) {
      if (file_exists($_file)) {
        unlink($_file);
      }

      foreach (CMedecinImport::$medecin_additionnal_files as $_additional_file) {
        $_file_name = str_replace(CMedecinImport::MEDECIN_FILE_NAME, $_additional_file, $_file);
        if (file($_file_name)) {
          unlink($_file_name);
        }
      }
    }
  }
}

preg_match('/(\d{4})(\d{2})(\d{2})\d{2}\d{2}/', $file_path, $matches);
$last_version = null;
if ($matches) {
  $last_version = CMbDT::date(
    sprintf('%d-%d-%d', $matches[1], $matches[2], $matches[3])
  );
}

$last_modification = null;
$nb_lines          = null;
if ($file_exists) {
  $last_modification = strftime(CMbDT::ISO_DATETIME, filemtime(reset($files)));

  $file_reader = new CMedecinFileReader($file_path);

  if (!$file_reader) {
    CAppUI::stepAjax('CMedecinImport-no-file', UI_MSG_ERROR);
  }

  $line = $file_reader->getTitles();

  if ($errors = CMedecinImport::getTitlesErrors($line)) {
    foreach ($errors as $_expected => $_real) {
      CAppUI::stepAjax('CMedecinImport-file-title-diff', UI_MSG_WARNING, $_expected, $_real);
    }
  }

  $file_reader->close();
}

try {
  $http_client = new CHTTPClient(CMedecinImport::$file_url);
  $http_client->setOption(CURLOPT_HEADER, true);
  $http_client->setOption(CURLOPT_TIMEOUT, 5);
  $http_client->setOption(CURLOPT_SSL_VERIFYPEER, false);
  $header = $http_client->head(true);
} catch (Exception $e) {
  $header = null;
}


$version = null;
if ($header) {
  preg_match('/PS_LibreAcces_(\d{4})(\d{2})(\d{2})\d{2}\d{2}/i', $header, $head_matches);
  if ($head_matches) {
    $version = CMbDT::date(
      sprintf('%d-%d-%d', $head_matches[1], $head_matches[2], $head_matches[3])
    );
  }
}

$file_error = null;
if (!$version || !$last_version) {
  $file_error = CAppUI::tr('CMedecinImport-file-unable-get-file');
}

$delta_file_update = CMbDT::daysRelative($last_modification, CMbDT::dateTime());

$last_offset = CMedecinImport::getStartOffset();

$smarty = new CSmartyDP();
$smarty->assign('file_exists', $file_exists);
$smarty->assign('last_modification', $last_modification);
$smarty->assign('actual_version', $last_version);
$smarty->assign('version', $version);
$smarty->assign('file_error', $file_error);
$smarty->assign('delta_file_update', $delta_file_update);
$smarty->assign('file_size', $file_size);
$smarty->assign('counts', CMedecinImport::$import_counts);
$smarty->assign('last_id', $last_offset);
$smarty->assign('default_cp', CAppUI::pref("medecin_cps_pref"));
$smarty->display('inc_import_medecins.tpl');