<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Interop\Hl7\CSourceMLLP;

require_once 'ajax_connexion_mllp.php';

/** @var CSourceMLLP $exchange_source */
$exchange_source->setData("Hello world !\n");

try {
  $exchange_source->send();
  CAppUI::stepAjax("Données transmises au serveur MLLP");
  if ($ack = $exchange_source->getData()) {
    echo "<pre>$ack</pre>";
  }
} catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
} 

