<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// init
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CError;
use Ox\Core\CView;
use Ox\Core\Mutex\CMbMutex;

CCanDo::checkEdit();
$buffer_file = CView::get("buffer_file", "str");
CView::checkin();

$root_dir        = CAppUI::conf("root_dir");
$path_tmp_buffer = CError::PATH_TMP_BUFFER;

// ressources
if ($buffer_file === 'all') {
  $files = CError::globWaitingBuffer();
}
else {
  $file  = $root_dir . $path_tmp_buffer . $buffer_file;
  $files = array($file);
}
$nb_file = count($files);

// lock
$lock = new CMbMutex("CError-file-buffer");
if (!$lock->lock(60)) {
  CAppUI::stepAjax("Verrou présent");

  return;
}

// store
foreach ($files as $_key => $_file) {
  if (file_exists($_file) && is_writable($_file)) {
    $retour = CError::storeBuffer($_file, false);
    if ($retour) {

      unset($files[$_key]);
    }
  }
}

$lock->release();

// noty
$nb_file_after = count($files);
if ($nb_file_after === 0) {
  CAppUI::displayAjaxMsg("{$nb_file} fichier(s) traités(s).", UI_MSG_OK);
}
else {
  CAppUI::displayAjaxMsg("Echec lors du traitement de {$nb_file_after} fichier(s).", UI_MSG_ERROR);
}

CAppUI::callbackAjax('Control.Modal.refresh');

CApp::rip();

