<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Core\CView;

/**
 * Envoi de fichiers par yoplet
 */

CCanDo::checkRead();

$file_name = CView::post("checksum", "str");

CView::checkin();

$path = CValue::first(CAppUI::conf("dPfiles yoplet_upload_path"), "tmp");

file_put_contents("$path/$file_name", file_get_contents($_FILES["file"]["tmp_name"]));