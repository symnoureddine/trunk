<?php
/**
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Context\CContextualIntegration;

CCanDo::checkRead();

$integration_id = CView::get("integration_id", "ref class|CContextualIntegration");

CView::checkin();

$integration = new CContextualIntegration();
$integration->load($integration_id);

if ($integration->_id) {
  $integration->loadRefsLocations();
}

// Icons
$fa_file = __DIR__ . "/../../style/mediboard_ext/vendor/fonts/font-awesome/css/font-awesome.min.css";
$fa_css  = file_get_contents($fa_file);

$icons = array();
if (preg_match_all('/\.fa-([\w-]+):before[^\{]+\{\s*content\s*:\s*([^\s]+)/', $fa_css, $matches)) {
  foreach ($matches[1] as $_i => $_key) {
    $icons[$_key] = trim($matches[2][$_i], '"\\');
  }

  ksort($icons);
}

$smarty = new CSmartyDP();
$smarty->assign("integration", $integration);
$smarty->assign("icons", $icons);
$smarty->display("inc_edit_integration.tpl");
