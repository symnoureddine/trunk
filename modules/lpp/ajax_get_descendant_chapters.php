<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Mediboard\Lpp\CLPPChapter;

CCanDo::checkRead();

$parent_id = CValue::get('parent_id', 0);

$chapters = CLPPChapter::loadfromParent($parent_id);

$data = array('level' => strlen($parent_id), 'chapters' => array());

foreach ($chapters as $_chapter) {
  $data['chapters'][] = array(
    'id'   => $_chapter->id,
    'view' => "$_chapter->rank - $_chapter->name",
  );
}

CApp::json($data);