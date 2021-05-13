<?php
/**
 * @package Mediboard\SourceCode\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Erp\SourceCode\CParseTestComment;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkAdmin();

$functions = CGroups::loadCurrent()->loadFunctions();

//User test
$userTest = new CUser();
$userTest->user_username = 'PHPUnit';
$userTest->user_last_name = 'XUnit';
$userTest->loadMatchingObject();
if ($userTest->_id) {
  $mediuserTest = $userTest->loadRefMediuser();
}
else {
  $mediuserTest = new CMediusers();
  $mediuserTest->_user_username = 'PHPUnit';
  $mediuserTest->loadRefFunction();
}

// Praticien test
$userPraticientTest = new CUser;
$userPraticientTest->user_username = 'CHIRTEST';
$userPraticientTest->user_last_name = 'CHIR';
$userPraticientTest->user_first_name = 'Test';
$userPraticientTest->loadMatchingObject();
if ($userPraticientTest->_id) {
  $praticientTest = $userPraticientTest->loadRefMediuser();
}
else {
  $praticientTest = new CMediusers();
  $praticientTest->_user_username = 'CHIRTEST';
  $praticientTest->loadRefFunction();
}

// Add objects to array
$objects = array();
$objects[] = $mediuserTest;
$objects[] = $praticientTest;

// Load all profiles
$profile           = new CUser();
$profile->template = 1;
/** @var CUser[] $profiles */
$profiles = $profile->loadMatchingList();

$isOneBrowserSet = in_array('1', CAppUI::conf('sourceCode selenium_browsers'), true);

$parser = new CParseTestComment();

$smarty = new CSmartyDP();
$smarty->assign('functions', $functions);
$smarty->assign('profiles', $profiles);
$smarty->assign('objects', $objects);
$smarty->assign('isOneBrowserSet', $isOneBrowserSet);
$smarty->assign('testsInfos', $parser->testsInfos);
$smarty->assign('functionCount', $parser->functionCount);

$smarty->display('vw_tests.tpl');
