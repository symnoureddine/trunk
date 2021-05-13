<?php
/**
 * @package Mediboard\Cim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbPath;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;

CCanDo::checkAdmin();
CView::checkin();

$archive = 'modules/dPcim10/base/cim_atih.tar.gz';
$dir = 'tmp/cim10/atih';

$sql = 'cim10_atih.sql';
$csv = 'cim10_atih.csv';

/* Extract the archive */
if (null === $files = CMbPath::extract($archive, $dir)) {
  CAppUI::stepAjax('Impossible d\'extraire l\'archive', UI_MSG_ERROR);
  CApp::rip();
}

CAppUI::stepAjax("Extraction de $files fichiers", UI_MSG_OK);

$ds = CSQLDataSource::get('cim10');
if (null == $lines = $ds->queryDump("$dir/$sql")) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
  CApp::rip();
}

CAppUI::stepAjax("Création de la structure des tables et import des chapitres", UI_MSG_OK);

$chapters = array();

/* We create a data structure for getting the parent category of a code */
$result = $ds->exec("SELECT id, libelle FROM chapters_atih WHERE parent_id = 0;");

while ($chapter = $ds->fetchAssoc($result)) {
  $codes = explode('-', substr($chapter['libelle'], strpos($chapter['libelle'], '(') + 1, 7));
  $chapters[$chapter['id']] = array(0 => $codes[0], 1 => $codes[1], 'categories' => array());
}

/* We get all the categories, add them to the chapters categories */
$result = $ds->exec("SELECT id, code, parent_id FROM chapters_atih WHERE parent_id != 0;");

while ($chapter = $ds->fetchAssoc($result)) {
  $codes = explode('-', str_replace(array('(', ')'), '', $chapter['code']));
  $chapters[$chapter['parent_id']]['categories'][$chapter['id']] = $codes;
}

$file = fopen("$dir/$csv", 'r');

$id = 1;
$entries = array();
$codes_categories = array();
while ($line = fgetcsv($file, null, '|')) {
  $code = trim($line[0]);
  $parent_code = substr($code, 0, 3);

  $ssr_fppec = $line[2][0] == 'O' ? '1' : '0';
  $ssr_mmp = $line[2][1] == 'O' ? '1' : '0';
  $ssr_ae = $line[2][2] == 'O' ? '1' : '0';
  $ssr_das = $line[2][3] == 'O' ? '1' : '0';

  $lib_court = str_replace("'", "\\'", $line[4]);
  $lib_long = str_replace("'", "\\'", $line[5]);

  $category = null;
  if (!array_key_exists($parent_code, $codes_categories)) {
    foreach ($chapters as $chapter) {
      if ($parent_code >= $chapter[0] && $parent_code <= $chapter[1]) {
        foreach ($chapter['categories'] as $_id => $category) {
          if ((count($category) == 2 && $parent_code >= $category[0] && $parent_code <= $category[1])
              || (count($category) == 1 && $parent_code == $category[0])
          ) {
            $category = $_id;
            $codes_categories[$parent_code] = $category;
            break;
          }
        }
        break;
      }
    }
  }
  else {
    $category = $codes_categories[$parent_code];
  }

  if ($category) {
    $entries[] = "($id, '$code', '{$line[1]}', '$ssr_fppec', '$ssr_mmp', '$ssr_ae', '$ssr_das', '{$line[3]}',"
      . "'$lib_court', '$lib_long', $category)";

    $id++;
  }
  else {
    $errors[] = $code;
  }
}

$query = "INSERT INTO codes_atih (id, code, type_mco, ssr_fppec, ssr_mmp, ssr_ae, ssr_das, type_psy,"
  . "libelle_court, libelle, category_id) VALUES\n";
$query .= implode(",\n", $entries) . ';';

if (!$ds->exec($query)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
}
else {
  CAppUI::stepAjax("Import de " . count($entries) . " codes", UI_MSG_OK);
}

CMbPath::remove($dir);
CApp::rip();