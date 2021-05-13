<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;

CCanDo::checkEdit();

CView::checkin();

$cache = new Cache('CMedecinImport', 'doublons_import', Cache::OUTER | Cache::DISTR);
$cache->rem();

CAppUI::stepAjax('CMedecin-doublons-import-empty', UI_MSG_OK);

CApp::rip();