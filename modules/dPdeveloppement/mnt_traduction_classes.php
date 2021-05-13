<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbConfig;
use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CTranslation;
use Ox\Core\CView;
use Ox\Core\FieldSpecs\CEnumSpec;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Mediboard\Addictologie\CNoteSuite;
use Ox\Mediboard\System\CConfigurationModelManager;

CCanDo::checkEdit();

// For function calls below
global $language;

$module    = CView::get("module"   , "str default|system", true);
$language  = CView::get("language" , "str default|" . CAppUI::pref('LOCALE'), true);
$reference = CView::get("reference", "str default|" . CAppUI::pref('FALLBACK_LOCALE'), true);
$start     = CView::get("start", 'num default|0');
$step      = CView::get('step', 'num default|500');

CView::checkin();

$translation = new CTranslation();
$trads = $translation->getTranslationsFor($module, $language, $reference);

// liste des dossiers modules + common et styles
$modules = array_keys(CModule::getInstalled());
$modules[] = "common";
sort($modules);

$items = $translation->getItems();
$counter_total = 0;
if (isset($items['Other'])) {
  foreach ($items['Other'] as $_item) {
    $counter_total += count($_item);
  }
}


// Création du template
$smarty = new CSmartyDP();

$smarty->assign("total_count"  , $translation->getTotalCount());
$smarty->assign("local_count"  , $translation->getLocalCount());
$smarty->assign("completion"   , $translation->getCompletion());
$smarty->assign("items"        , $translation->getItems());
$smarty->assign("archives"     , $translation->getArchives());
$smarty->assign("completions"  , $translation->getCompletions());
$smarty->assign("locales"      , array_keys($translation->getLanguages()));
$smarty->assign("modules"      , $modules);
$smarty->assign("module"       , $module);
$smarty->assign("trans"        , $trads);
$smarty->assign("language"     , $language);
$smarty->assign("reference"    , $reference);
$smarty->assign("ref_items"    , $translation->getRefItems());
$smarty->assign("start"    , $start);
$smarty->assign("step"    , $step);
$smarty->assign("counter_total"    , $counter_total);

$smarty->display("mnt_traduction_classes.tpl");

