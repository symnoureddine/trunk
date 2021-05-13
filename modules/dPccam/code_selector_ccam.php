<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Ccam\CCCAM;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Ccam\CFavoriCCAM;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\SalleOp\CActeCCAM;

$chir           = CView::get("chir", 'ref class|CMediusers');
$anesth         = CView::get("anesth", 'ref class|CMediusers');
$_keywords_code = CView::get("_keywords_code", 'str');
$date           = CMbDT::date(null, CView::get("date", 'str'));
$object_class   = CView::get("object_class", 'str');
$only_list      = CView::get("only_list", 'bool default|0');
$tag_id         = CView::get("tag_id", 'ref class|CTagItem');
$access         = CView::get('access', 'str');
$appareil       = CView::get('appareil', 'str');
$system         = CView::get('system', 'str');
$chapter_1      = CView::get('chapter_1', 'str');
$chapter_2      = CView::get('chapter_2', 'str');
$chapter_3      = CView::get('chapter_3', 'str');
$chapter_4      = CView::get('chapter_4', 'str');

CView::checkin();

$user   = CUser::get();
$ds     = CSQLDataSource::get("std");
$profiles = array (
  "chir"   => $chir,
  "anesth" => $anesth,
  "user"   => $user->_id,
);

if ($profiles["user"] == $profiles["anesth"] || $profiles["user"] == $profiles["chir"]) {
  unset($profiles["user"]);
}

if (!$profiles["anesth"]) {
  unset($profiles["anesth"]);
}

$listByProfile = array();
$users = array();


$ds = CSQLDataSource::get('ccamV2');

/* Récupération des voies d'accès */
$result = $ds->exec('SELECT * FROM acces1;');
$access_ways = array();
while ($row = $ds->fetchArray($result)) {
  $access_ways[] = array('code' => $row['CODE'], 'text' => ucfirst(CMbString::lower($row['ACCES'])));
}

/* Récupération des appareils */
$result = $ds->exec('SELECT * FROM topographie1;');
$appareils = array();
while ($row = $ds->fetchArray($result)) {
  $appareils[] = array('code' => $row['CODE'], 'text' => ucfirst(CMbString::lower($row['LIBELLE'])));
}

/* Récupération des systèmes */
$result = $ds->exec('SELECT * FROM topographie2;');
$systems = array();
while ($row = $ds->fetchArray($result)) {
  $parent = $row['PERE'];
  if (!array_key_exists($parent, $systems)) {
    $systems[$parent] = array();
  }

  $systems[$parent][] = array('code' => $row['CODE'], 'text' => ucfirst(CMbString::lower($row['LIBELLE'])));
}

/* Récupération des chapitres de niveau 1 */
$chapters_1 = CCCAM::getChapters();

$chapters_2 = array();
if ($chapter_1) {
  $chapters_2 = CCCAM::getChapters($chapter_1);
}

$chapters_3 = array();
if ($chapter_2) {
  $chapters_3 = CCCAM::getChapters($chapter_2);
}

$chapters_4 = array();
if ($chapter_3) {
  $chapters_4 = CCCAM::getChapters($chapter_3);
}

/* Définition des paramètres de la recherche */
$whereSearch = array();
if ($access) {
  $whereSearch[] = "`CODE` LIKE '___{$access}___'";
}

if ($system) {
  $whereSearch[] = "`CODE` LIKE '{$system}_____'";
}
elseif ($appareil) {
  $whereSearch[] = "`CODE` LIKE '{$appareil}______'";
}

if ($chapter_1) {
  $whereSearch[] = "`ARBORESCENCE1` = '0000{$chapters_1[$chapter_1]['rank']}'";
}

if ($chapter_2) {
  $whereSearch[] = "`ARBORESCENCE2` = '0000{$chapters_2[$chapter_2]['rank']}'";
}

if ($chapter_3) {
  $whereSearch[] = "`ARBORESCENCE3` = '0000{$chapters_3[$chapter_3]['rank']}'";
}

if ($chapter_4) {
  $whereSearch[] = "`ARBORESCENCE4` = '0000{$chapters_4[$chapter_4]['rank']}'";
}

if (count($whereSearch) > 0 || $_keywords_code) {
  $profiles['search'] = null;
}

foreach ($profiles as $profile => $_user_id) {
  $_user = new CMediusers();
  if ($profile != 'search') {
    $_user->load($_user_id);
  }
  $users[$profile] = $_user;

  $list        = array();
  $codes_stats = array();

  if (!$tag_id && $_user_id) {
    // Statistiques
    $actes       = new CActeCCAM;
    $codes_stats = $actes->getFavoris($_user_id, $object_class);

    foreach ($codes_stats as $key => $_code) {
      $codes_stats[$_code["code_acte"]] = $_code;
      unset($codes_stats[$key]);
    }
  }

  // Favoris
  $codes_favoris = array();
  if ($profile != 'search') {
    $_user = CMediusers::get($_user_id);
    $code                              = new CFavoriCCAM();
    $where                             = array();
    $where[] = "ccamfavoris.favoris_user = '$_user->_id' OR ccamfavoris.favoris_function = $_user->function_id";
    $where["ccamfavoris.object_class"] = " = '$object_class'";

    $ljoin = array();
    if ($tag_id) {
      $where["tag_item.tag_id"] = "= '$tag_id'";
      $ljoin["tag_item"]        = "tag_item.object_id = ccamfavoris.favoris_id AND tag_item.object_class = 'CFavoriCCAM'";
    }

    /** @var CFavoriCCAM[] $codes_favoris */
    // - rang DESC permet de mettre les rang null à la fin de la liste
    $codes_favoris = $code->loadList($where, "rang DESC", 100, null, $ljoin);

    foreach ($codes_favoris as $key => $_code) {
      $codes_favoris[$_code->favoris_code] = $_code;
      unset($codes_favoris[$key]);
    }
  }

  // Seek sur les codes, avec ou non l'inclusion de tous les codes
  $code = new CDatedCodeCCAM("");
  $where = $whereSearch;

  if ($profile != 'search' && (count($codes_stats) || count($codes_favoris))) {
    // Si on a la recherche par tag, on n'utilise pas les stats (les tags sont mis sur les favoris)
    if ($tag_id) {
      $codes_keys = array_keys($codes_favoris);
    }
    else {
      $codes_keys = array_keys(array_merge($codes_stats, $codes_favoris));
    }
    $where[] = "CODE ".$ds->prepareIn($codes_keys);
  }

  if ($profile != 'search' && count($codes_stats) == 0 && count($codes_favoris) == 0) {
    // Si pas de stat et pas de favoris, et que la recherche se fait sur ceux-ci,
    // alors le tableau de résultat est vide
    $codes = array();
  }
  else {
    // Sinon recherche de codes
    $codes = $code->findCodes($_keywords_code, $_keywords_code, null, implode(' AND ', $where));
  }

  $list_fav = $list_fav_no_rang = array();
  foreach ($codes as $value) {
    $val_code = $value["CODE"];
    $code = CDatedCodeCCAM::get($val_code, $date);

    if ($profile != 'search' && $code->code != "-") {
      $list[$val_code] = $code;
      $nb_acte = 0;
      if (isset($codes_favoris[$val_code])) {
        $list[$val_code]->nb_acte = 0.5;
        $list[$val_code]->_favoris = $codes_favoris[$val_code];

        $chapitre =& $code->chapitres[0];
        $list[$val_code]->chap = $chapitre["nom"];

        if (isset($list[$val_code]->_favoris) && !$list[$val_code]->_favoris->rang) {
          $list_fav_no_rang[$val_code] = $list[$val_code];
        }
        else {
          $list_fav[$val_code] = $list[$val_code];
        }
      }
      if (isset($codes_stats[$val_code])) {
        $nb_acte = $codes_stats[$val_code]["nb_acte"];
      }

      $list[$val_code]->nb_acte = $nb_acte;
      if (!$nb_acte) {
        $list_fav_no_rang[$val_code] = $list[$val_code];
        unset($list[$val_code]);
      }
    }
    elseif ($code->code != "-") {
      $list[$val_code] = $code;
    }
  }

  if ($tag_id || $profile == 'search') {
    $sorter = CMbArray::pluck($list, "code");
    array_multisort($sorter, SORT_ASC, $list);
  }
  else {
    $sorter = CMbArray::pluck($list, "nb_acte");
    array_multisort($sorter, SORT_DESC, $list);
  }

  array_multisort(CMbArray::pluck($list_fav, "_favoris", "rang"), SORT_ASC, CMbArray::pluck($list_fav, "code"), SORT_ASC, $list_fav);
  $list_fav = array_merge($list_fav, $list_fav_no_rang);

  $listByProfile[$profile]["favoris"] = $codes_favoris;
  $listByProfile[$profile]["stats"]   = $codes_stats;
  $listByProfile[$profile]["list"]    = array("favoris" => $list_fav, "stats" => $list);
  $listByProfile[$profile]['total']   = count($list_fav) + count($list);
}

$tag_tree = CFavoriCCAM::getTree($user->_id);


$smarty = new CSmartyDP();

$smarty->assign("listByProfile" , $listByProfile);
$smarty->assign("users"         , $users);
$smarty->assign("object_class"  , $object_class);
$smarty->assign("date"          , $date);
$smarty->assign("_keywords_code", $_keywords_code);
$smarty->assign("tag_tree"      , $tag_tree);
$smarty->assign("tag_id"        , $tag_id);
$smarty->assign('curr_user'     , CMediusers::get());
if ($only_list) {
  $smarty->display("inc_code_selector_ccam.tpl");
}
else {
  $smarty->assign("chir"         , $chir);
  $smarty->assign("anesth"       , $anesth);
  $smarty->assign('access'        , $access_ways);
  $smarty->assign('appareils'     , $appareils);
  $smarty->assign('systems'       , $systems);
  $smarty->assign('chapters_1'    , $chapters_1);
  $smarty->assign('chapters_2'    , $chapters_2);
  $smarty->assign('chapters_3'    , $chapters_3);
  $smarty->assign('chapters_4'    , $chapters_4);

  $smarty->display("code_selector_ccam.tpl");
}
