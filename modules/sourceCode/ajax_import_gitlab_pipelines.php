<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

CCanDo::checkAdmin();
CView::checkin();
CApp::setTimeLimit(600);

try {
    $pipelines = CGitlabManager::importAllProjectsPipelines(false);
    CAppUI::setMsg(
        "Gitlab pipelines imported : " . count($pipelines),
        UI_MSG_OK
    );
} catch (Exception $e) {
    CAppUI::setMsg($e->getMessage(), UI_MSG_ERROR);
}

echo CAppUI::getMsg();
