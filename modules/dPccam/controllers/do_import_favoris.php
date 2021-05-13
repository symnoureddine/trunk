<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Ccam\CCodeCCAM;
use Ox\Mediboard\Ccam\CFavoriCCAM;
use Ox\Mediboard\System\CTag;
use Ox\Mediboard\System\CTagItem;

CCanDo::checkAdmin();

$user_id = CView::post('user_id', 'ref class|CMediusers');
$function_id = CView::post('function_id', 'ref class|CFunctions');
$file = CValue::files('formfile');

CView::checkin();

if (!array_key_exists('tmp_name', $file) || $file['tmp_name'][0] == '') {
  CAppUI::setMsg('Aucun fichier sélectionné', UI_MSG_ERROR);
  CAppUI::getMsg();
  CApp::rip();
}

$file = new CCSVFile($file['tmp_name'][0]);

$imported = 0;
$errors = 0;
if ($file->countLines()) {
  $file->setColumnNames(array ('owner_type', 'owner_id', 'tags', 'rank', 'code', 'object_class'));

  $file->jumpLine(1);

  while ($line = $file->readLine(true, true)) {
    $favori = new CFavoriCCAM();

    if ($user_id) {
      $favori->favoris_user = $user_id;
    }
    elseif ($function_id) {
      $favori->favoris_user = $function_id;
    }
    elseif (in_array(strtolower($line['owner_type']), array('user', 'utilisateur', 'cuser'))) {
      $favori->favoris_user = $line['owner_id'];
    }
    elseif (in_array(strtolower($line['owner_type']), array('fonction', 'function', 'cfunctions'))) {
      $favori->favoris_user = $line['owner_id'];
    }

    if ($line['rank']) {
      $favori->rang = $line['rank'];
    }

    $code = CCodeCCAM::get(trim($line['code']));
    if ($code->code == '-') {
      $errors++;
      continue;
    }

    $favori->favoris_code = $code->code;

    if ($line['object_class']) {
      if (in_array(strtolower($line['object_class']), array('consultation', 'consult', 'cconsultation'))) {
        $favori->object_class = 'CConsultation';
      }
      if (in_array(strtolower($line['object_class']), array('sejour', 'csejour'))) {
        $favori->object_class = 'CSejour';
      }
      else {
        $favori->object_class = 'COperation';
      }
    }

    $favori->loadMatchingObject();

    if ($msg = $favori->store()) {
      $errors++;
      continue;
    }

    if ($line['tags']) {
      $tags = explode('|', $line['tags']);

      foreach ($tags as $tag_name) {
        $parent = false;
        /* Gestion des tags parents */
        if (strpos($tag_name, '&raquo;')) {
          list($parent_name, $tag_name) = explode(' &raquo; ', $tag_name);
          $parent = new CTag();
          $parent->name = $parent_name;
          $parent->object_class = $favori->_class;
          $parent->loadMatchingObject();
          if ($msg = $parent->store()) {
            continue;
          }
        }

        $tag = new CTag();
        $tag->name = $tag_name;
        $tag->object_class = $favori->_class;

        if ($parent) {
          $tag->parent_id = $parent->_id;
        }

        $tag->loadMatchingObject();
        if ($msg = $tag->store()) {
          continue;
        }

        $tag_item = new CTagItem();
        $tag_item->tag_id = $tag->_id;
        $tag_item->object_id = $favori->_id;
        $tag_item->object_class = $favori->_class;

        $tag_item->loadMatchingObject();
        $tag_item->store();
      }
    }

    $imported++;
  }
}

$file->close();

if ($errors) {
  CAppUI::setMsg("$errors favoris en erreur", UI_MSG_ERROR);
}

if ($imported) {
  CAppUI::setMsg("$imported favoris importés", UI_MSG_OK);
}

echo CAppUI::getMsg();