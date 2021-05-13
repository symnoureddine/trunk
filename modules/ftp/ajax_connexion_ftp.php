<?php
/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CFTP;
use Ox\Core\CMbException;
use Ox\Core\CView;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::check();

// Check params
$exchange_source_name = CView::get("exchange_source_name", "str");

CView::checkin();

if ($exchange_source_name == null) {
  CAppUI::stepAjax("Aucun nom de source d'échange spécifié", UI_MSG_ERROR);
}

$exchange_source = CExchangeSource::get($exchange_source_name, CSourceFTP::TYPE, false, null, false);

$ftp = new CFTP();
$ftp->init($exchange_source);

try {
  $ftp->testSocket();
  CAppUI::stepAjax("CFTP-success-connection", E_USER_NOTICE, $ftp->hostname, $ftp->port);

  $ftp->connect();
  CAppUI::stepAjax("CFTP-success-authentification", E_USER_NOTICE, $ftp->username);

  if ($ftp->passif_mode) {
    CAppUI::stepAjax("CFTP-msg-passive_mode"); 
  }
  
  $sent_file = CAppUI::conf('root_dir')."/ping.php";
  $remote_file = $ftp->fileprefix . "test.txt";

  $ftp->sendFile($sent_file, $remote_file);
  CAppUI::stepAjax("CFTP-success-transfer_out", E_USER_NOTICE, $sent_file, $remote_file);

  $get_file = "tmp/ping.php";
  $ftp->getFile($remote_file, $get_file);
  CAppUI::stepAjax("CFTP-success-transfer_in", E_USER_NOTICE, $remote_file, $get_file);
  
  $ftp->delFile($remote_file);
  CAppUI::stepAjax("CFTP-success-deletion", E_USER_NOTICE, $remote_file);
} 
catch (CMbException $e) {
  $e->stepAjax();
}

