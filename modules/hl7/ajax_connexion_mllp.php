<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Interop\Hl7\CSourceMLLP;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::checkAdmin();

// Check params
if (null == $exchange_source_name = CValue::get("exchange_source_name")) {
  CAppUI::stepAjax("Aucun nom de source d'�change sp�cifi�", UI_MSG_ERROR);
}

/** @var CSourceMLLP $exchange_source */
$exchange_source = CExchangeSource::get($exchange_source_name, "mllp", true, null, false);

if (!$exchange_source) {
  CAppUI::stepAjax("Aucune source d'�change disponible pour ce nom : '$exchange_source_name'", UI_MSG_ERROR);
}

if (!$exchange_source->host) {
  CAppUI::stepAjax("Aucun h�te pour la source d'�change : '$exchange_source_name'", UI_MSG_ERROR);
}

try {
  $exchange_source->getSocketClient();
  CAppUI::stepAjax("Connexion au serveur MLLP r�ussi");
  if ($ack = $exchange_source->getData()) {
    echo "<pre>$ack</pre>";
  }
} catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}




