<?php
/**
 * @package Mediboard\System\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CMbPath;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::checkAdmin();

// Check params
if (null == $exchange_source_name = CValue::get("exchange_source_name")) {
  CAppUI::stepAjax("Aucun nom de source spécifié", UI_MSG_ERROR);
}

if (null == $type_action = CValue::get("type_action")) {
  CAppUI::stepAjax("Aucun type de test spécifié", UI_MSG_ERROR);
}

$exchange_source = CExchangeSource::get($exchange_source_name, "file_system", true, null, false);

// Connexion
if ($type_action == "connexion") {
  try {
    $exchange_source->init();
  } catch (CMbException $e) {
    $e->stepAjax(UI_MSG_ERROR);
  }

  
  CAppUI::stepAjax("CSourceFileSystem-host-is-a-dir", UI_MSG_OK, $exchange_source->host);
}

// Envoi d'un fichier
else if ($type_action == "sendFile") {
  try {
    $exchange_source->setData("Test source file system in Mediboard", false);
    $exchange_source->send();
  } catch (CMbException $e) {
    $e->stepAjax(UI_MSG_ERROR);
  }

  CAppUI::stepAjax("Le fichier 'testSendFile$exchange_source->fileextension' a été copié dans le dossier '$exchange_source->host'");
}  
// Récupération des fichiers
else if ($type_action == "getFiles") {
  $directory = $exchange_source->getCurrentDirectory();
  $files     = $exchange_source->getListFilesDetails($directory);

  $count_files = CMbPath::countFiles($exchange_source->host);
  
  CAppUI::stepAjax("Le dossier '$exchange_source->host' contient : $count_files fichier(s)");
  
  if ($count_files > 1000) {
    CAppUI::stepAjax("Le dossier '$exchange_source->host' contient trop de fichiers pour être listé", UI_MSG_WARNING);
  }

  // Création du template
  $smarty = new CSmartyDP();

  $smarty->assign("current_directory", $exchange_source->getCurrentDirectory());
  $smarty->assign("exchange_source"  , $exchange_source);
  $smarty->assign("files"            , $files);
  
  $smarty->display("inc_fs_files.tpl");
}
