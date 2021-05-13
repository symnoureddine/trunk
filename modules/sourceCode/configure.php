<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceHTTP;

CCanDo::checkAdmin();

$source_http = CExchangeSource::get('gitlab_api', CSourceHTTP::TYPE, true, null, false);

$smarty = new CSmartyDP();
$smarty->assign('source_http', $source_http);
$smarty->display("configure.tpl");
