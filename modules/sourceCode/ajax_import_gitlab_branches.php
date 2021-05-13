<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;

CCanDo::checkAdmin();
CView::checkin();

try {
  $branches = CGitlabManager::importAllProjectBranches(false);
  CAppUI::setMsg("Gitlab branches imported : ".count($branches), UI_MSG_OK);
} catch (Exception $e) {
  CAppUI::setMsg($e->getMessage(), UI_MSG_ERROR);
}

echo CAppUI::getMsg();

