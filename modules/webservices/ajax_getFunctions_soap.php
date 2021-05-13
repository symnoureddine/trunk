<?php
/**
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;

/**
 * Get functions
 */
require_once "ajax_connexion_soap.php";

CAppUI::stepAjax("Liste des fonctions SOAP publiées");

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("exchange_source", $exchange_source);
$smarty->assign("functions"      , $soap_client->getFunctions());
$smarty->assign("types"          , $soap_client->getTypes());
$smarty->assign("form_name"      , CValue::get("form_name"));

$smarty->display("inc_soap_functions.tpl");