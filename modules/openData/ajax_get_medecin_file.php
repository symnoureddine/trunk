<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CHTTPClient;
use Ox\Core\CView;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\OpenData\CMedecinImport;

CCanDo::checkEdit();

CView::checkin();

CApp::setTimeLimit(300);

$temp_file = tempnam('tmp', 'med_');

$fs = fopen($temp_file, 'w+');
$url = CMedecinImport::$file_url;

try {
  $http_client = new CHTTPClient($url);
  $http_client->setOption(CURLOPT_FILE, $fs);
  // Pas de vérification de certificat car problème avec le certificat de service.annuaire.sante.fr
  $http_client->setOption(CURLOPT_SSL_VERIFYPEER, false);
  $content = $http_client->get(true);
}
catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}

fclose($fs); // Fermeture du fichier

if ($content) {
  $zip = new ZipArchive();
  $zip->open($temp_file);
  $zip->extractTo(rtrim(CFile::$directory, '/\\') .'/upload/');
  CAppUI::stepAjax('CMedecinImport-file-downloaded', UI_MSG_OK);
  $zip->close();
}
else {
  CAppUI::stepAjax('CMedecinImport-file-failed', UI_MSG_WARNING);
}

unlink($temp_file);





