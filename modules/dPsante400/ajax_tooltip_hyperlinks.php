<?php
/**
 * @package Mediboard\Sante400
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Sante400\CHyperTextLink;

CCanDo::checkRead();

$object = mbGetObjectFromGet('object_class', 'object_id', 'object_guid');

CView::checkin();

/** @var CHyperTextLink[] $hyperlinks */
$hyperlinks = $object->loadBackRefs('hypertext_links', 'name ASC');

$smarty = new CSmartyDP();
$smarty->assign('hyperlinks', $hyperlinks);
$smarty->display('vw_tooltip_hyperlinks.tpl');