<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbPath;

CCanDo::checkAdmin();

$tmp_filename = $_FILES["import"]["tmp_name"];

$dom = new DOMDocument();
$dom->load($tmp_filename);

$xpath = new DOMXPath($dom);
if ($xpath->query("/mediboard-export")->length == 0) {
  CAppUI::js("window.parent.Group.uploadError()");
  CApp::rip();
}

$temp = CAppUI::getTmpPath("group_import");
$uid = preg_replace('/[^\d]/', '', uniqid("", true));
$filename = "$temp/$uid";
CMbPath::forceDir($temp);

move_uploaded_file($tmp_filename, $filename);

// Cleanup old files (more than 4 hours old)
$other_files = glob("$temp/*");
$now = time();
foreach ($other_files as $_other_file) {
  if (filemtime($_other_file) < $now - 3600 * 4) {
    unlink($_other_file);
  }
}

CAppUI::js("window.parent.Group.uploadSaveUID('$uid')");
CApp::rip();