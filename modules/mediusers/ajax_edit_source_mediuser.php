<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::checkRead();
$user_id = CView::get("user_id", "ref class|CMediusers", true);
CView::checkin();

$mediuser = CMediusers::get($user_id);

// Source File system d'envoi
$fs_source_envoi = CExchangeSource::get("envoi-tarmed-$mediuser->_guid", "file_system", true, null, false);

// Source File system d'envoi
$fs_source_envoi_tp = CExchangeSource::get("envoi-tarmed-tp-$mediuser->_guid", "file_system", true, null, false);

// Source File system d'envoi de relance
$fs_source_envoi_relance = CExchangeSource::get("envoi-tarmed-relance-$mediuser->_guid", "file_system", true, null, false);

// Source File system d'envoi
$fs_source_reception = CExchangeSource::get("reception-tarmed-$mediuser->_guid", "file_system", true, null, false);

$fs_sources_tarmed = array(
  "fs_source_envoi" => array($fs_source_envoi),
  "fs_source_envoi_tp" => array($fs_source_envoi_tp),
  "fs_source_envoi_relance" => array($fs_source_envoi_relance),
  "fs_source_reception" => array($fs_source_reception),
);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("fs_sources_tarmed", $fs_sources_tarmed);
$smarty->assign("mediuser", $mediuser);

$smarty->display("sources_archive");
