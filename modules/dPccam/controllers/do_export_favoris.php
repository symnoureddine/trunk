<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Ccam\CFavoriCCAM;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkAdmin();

$user_id = CView::post('user_id', 'ref class|CMediusers');
$function_id = CView::post('function_id', 'ref class|CFunctions');

CView::checkin();

$favori = new CFavoriCCAM();

$type = '';
$class = '';
$id = '';
$name = '';
if ($user_id) {
  $favori->favoris_user = $user_id;
  $type = 'Utilisateur';
  $class = 'CMediusers';
  $id = $user_id;
  $user = CMediusers::get($user_id);
  $name = "$user->_user_last_name $user->_user_first_name";
}
elseif ($function_id) {
  $favori->favoris_function = $function_id;
  $type = 'Fonction';
  $class = 'CFunctions';
  $id = $function_id;
  /** @var CFunctions $function */
  $function = CMbObject::loadFromGuid("CFunctions-$function_id");
  $name = $function->text;
}

/** @var CFavoriCCAM[] $favoris */
$favoris = $favori->loadMatchingList();
$tag_items = CMbObject::massLoadBackRefs($favoris, 'tag_items');
CMbObject::massLoadFwdRef($tag_items, 'tag_id');

$file = new CCSVFile();

$file->writeLine(
  array(
    'Type',
    'Propriétaire',
    'Tag',
    'Rang',
    'Code',
    'Type objet'
  )
);

foreach ($favoris as $favori) {
  $favori->loadRefsTagItems();

  $tags = array();
  foreach ($favori->_ref_tag_items as $tag) {
    $tags[] = $tag->_view;
  }

  $file->writeLine(
    array(
      $class,
      $id,
      implode('|', $tags),
      $favori->rang,
      $favori->favoris_code,
      $favori->object_class
    )
  );
}

$file->stream('favoris_ccam_' . str_replace(' ', '_', $name));
CApp::rip();