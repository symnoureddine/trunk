<?php
/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CView;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::check();

// Check params
$exchange_source_name = CView::get("exchange_source_name", "str");
CView::checkin();

if ($exchange_source_name == null) {
  CAppUI::stepAjax("CSourceFTP-no-source", UI_MSG_ERROR, $exchange_source_name);
}

/** @var CSourceSFTP $exchange_source */
$exchange_source = CExchangeSource::get($exchange_source_name, CSourceSFTP::TYPE, false, null, false);
if (!$exchange_source->_id) {
  CAppUI::stepAjax("CExchangeSource-no-source", UI_MSG_ERROR, $exchange_source_name);
}

try {
  $exchange_source->isReachableSource();
  CAppUI::stepAjax("CSFTP-success-connection", E_USER_NOTICE, $exchange_source->host, $exchange_source->port);

  $exchange_source->isAuthentificate();
  CAppUI::stepAjax("CSFTP-success-authentification", E_USER_NOTICE, $exchange_source->user);

  $sent_file = CAppUI::conf('root_dir')."/ping.php";
  $remote_file = $exchange_source->fileprefix . "test.txt";

  $exchange_source->addFile($remote_file, $sent_file, null);
  CAppUI::stepAjax("CSFTP-success-transfer_out", E_USER_NOTICE, $sent_file, $remote_file);

  $get_file = "tmp/ping.php";
  $exchange_source->getFile($remote_file, $get_file, null);
  CAppUI::stepAjax("CSFTP-success-transfer_in", E_USER_NOTICE, $remote_file, $get_file);

  $exchange_source->delFile($remote_file);
  CAppUI::stepAjax("CFTP-success-deletion", E_USER_NOTICE, $remote_file);
} 
catch (CMbException $e) {
  $e->stepAjax();
  if (isset($exchange_source->_sftp->connexion->sftp_errors)) {
      CAppUI::stepAjax(CMbArray::get($exchange_source->_sftp->connexion->sftp_errors, 0), UI_MSG_WARNING);
  }
}

CApp::rip();

