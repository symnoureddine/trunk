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
  CAppUI::stepAjax("Aucun nom de source d'échange spécifié", UI_MSG_ERROR);
}

/** @var CSourceMLLP $exchange_source */
$exchange_source = CExchangeSource::get($exchange_source_name, "mllp", true, null, false);

if (!$exchange_source) {
  CAppUI::stepAjax("Aucune source d'échange disponible pour ce nom : '$exchange_source_name'", UI_MSG_ERROR);
}

if (!$exchange_source->host) {
  CAppUI::stepAjax("Aucun hôte pour la source d'échange : '$exchange_source_name'", UI_MSG_ERROR);
}

try {
  $exchange_source->getSocketClient();
  CAppUI::stepAjax("Connexion au serveur MLLP réussi");
  if ($ack = $exchange_source->getData()) {
    echo "<pre>$ack</pre>";
  }
} catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}




