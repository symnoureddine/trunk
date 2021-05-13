<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// Column 
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbString;
use Ox\Core\CRequest;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\OpenData\CCommuneFrance;

$column  = CView::get("column", "enum list|code_postal|commune");
$max     = CView::get("max", "num default|30");
$name    = CView::get("name_input", "str");
$keyword = CView::post($name, "str");

$columns = array("code_postal", "commune");
if (!in_array($column, $columns)) {
  trigger_error("Column '$column' is invalid");

  return;
}

CView::checkin();
CView::enableSlave();

$ds      = CSQLDataSource::get("INSEE");
$nbPays  = 0;
$matches = array();

$prefix = ($column === 'code_postal') ? 'P' : 'C';

$needle = $column == "code_postal" ? "$keyword%" : "%$keyword%";

$queries = array(
  'suisse' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Suisse' AS pays"
    ),
    'table'  => array(
      'communes_suisse'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  ),
  'allemagne' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Allemagne' AS pays"
    ),
    'table'  => array(
      'communes_allemagne'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  ),
  'belgique' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Belgique' AS pays"
    ),
    'table'  => array(
      'communes_belgique'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  ),
  'espagne' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Espagne' AS pays"
    ),
    'table'  => array(
      'communes_espagne'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  ),
  'portugal' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Portugal' AS pays"
    ),
    'table'  => array(
      'communes_portugal'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  ),
  'gb' => array(
    'select' => array(
      'commune', 'code_postal', "'' AS INSEE", "'' AS departement", "'Gb' AS pays"
    ),
    'table'  => array(
      'communes_gb'
    ),
    'where'  => array(
      $column => $ds->prepareLike($needle),
    )
  )
);

if ($ds->hasTable('communes_france_new') && $ds->countRows('SELECT * FROM `communes_france_new`') > 0) {
  $pays = array("france" => CCommuneFrance::class, "suisse" => "CCommuneSuisse", "allemagne" => "CCommuneAllemagne",
                "espagne" => "CCommuneEspagne", "portugal" => "CCommunePortugal", "gb" => "CCommuneGb", "belgique" => "CCommuneBelgique");

  foreach ($pays as $_pays => $_class) {
    if (CAppUI::conf("dPpatients INSEE $_pays")) {
      $nbPays++;
      if ($_pays == 'france') {
        $matches = array_merge($matches, $_class::getCommunesForCpName($column, $keyword, $max / $nbPays));
      }
      else {
        $query = new CRequest();
        $query->addSelect($queries[$_pays]['select']);
        $query->addTable($queries[$_pays]['table']);
        $query->addWhere($queries[$_pays]['where']);
        $query->setLimit(round($max / $nbPays));
        $results = $ds->loadList($query->makeSelect());
        $matches = array_merge($matches, $results);
      }
    }
  }
}
else {
  foreach (array("france", "suisse", "allemagne", "espagne", "portugal", "gb", "belgique") as $pays) {
    if (CAppUI::conf("dPpatients INSEE $pays")) {
      $nbPays++;
      $query   = "
SELECT commune, code_postal, "
          . ($pays == "france" ? "departement" : "'' AS departement")
          . ", "
          . ($pays == 'france' ? 'INSEE' : "'' AS INSEE")
          . ", '"
          . ucfirst($pays). "' AS pays
FROM communes_$pays
WHERE $column LIKE '$needle'";
      $results = $ds->loadList($query, intval($max / $nbPays));
      $matches = array_merge($matches, $results);
    }
  }
}


array_multisort(CMbArray::pluck($matches, "code_postal"), SORT_ASC, CMbArray::pluck($matches, "commune"), SORT_ASC, $matches);

foreach ($matches as $key => $_match) {
  $matches[$key]["commune"]     = CMbString::capitalize(CMbString::lower($matches[$key]["commune"]));
  $matches[$key]["departement"] = CMbString::capitalize(CMbString::lower($matches[$key]["departement"]));
  $matches[$key]["pays"]        = CMbString::capitalize(CMbString::lower($matches[$key]["pays"]));
}

// Template
$smarty = new CSmartyDP();
$smarty->assign("keyword", $keyword);
$smarty->assign("matches", $matches);
$smarty->assign("nodebug", true);
$smarty->display("autocomplete_cp_commune");
