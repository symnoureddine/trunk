<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\CCdaTools;
use Ox\Mediboard\CompteRendu\CCompteRendu;

CCanDo::checkAdmin();

$cr  = new CCompteRendu();
$cr->load($cr->getRandomValue("compte_rendu_id", true));

$factory = CCDAFactory::factory($cr);
$message = $factory->generateCDA();

$treecda = CCdaTools::parse($message);
$xml     = CCdaTools::showxml($message);

$smarty = new CSmartyDP();

$smarty->assign("message", $message);
$smarty->assign("treecda", $treecda);
$smarty->assign("xml"    , $xml);

$smarty->display("inc_highlightcda.tpl");