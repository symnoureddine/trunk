<?php
/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CSmartyDP;
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
$exchange_source = CExchangeSource::get($exchange_source_name, CSourceSFTP::TYPE, true, null, false);
if (!$exchange_source->_id) {
  CAppUI::stepAjax("CExchangeSource-no-source", UI_MSG_ERROR, $exchange_source_name);
}

try {
  $exchange_source->isAuthentificate();
  CAppUI::stepAjax("CSFTP-success-connection", UI_MSG_OK, $exchange_source->host, $exchange_source->user);

  $files = $exchange_source->getListFilesDetails($exchange_source->fileprefix, true);
}
catch (CMbException $e) {
  $e->stepAjax();
  CAppUI::stepAjax($exchange_source->getError());
  return;
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("exchange_source", $exchange_source);
$smarty->assign("files"          , $files);
$smarty->display("inc_ftp_files.tpl");
