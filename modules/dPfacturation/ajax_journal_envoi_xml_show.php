<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Facturation\CFactureCabinet;
use Ox\Mediboard\Facturation\CJournalEnvoiXml;
use Ox\Mediboard\Tarmed\CEditBill;

CCanDo::checkRead();
$journal_id = CView::get("journal_id", "ref class|CJournalEnvoiXml");
CView::checkin();

$journal = new CJournalEnvoiXml();
$journal->load($journal_id);
$statut = $journal->statut;
$statut_array = json_decode($statut, true);
$statut_xml = new CEditBill();
if ($statut_array) {
  $statut = $statut_array;
  $statut_xml->_logs_erreur = $statut;
}
// Création du template
$smarty = new CSmartyDP();

$smarty->assign("journal",       $journal);
$smarty->assign("statut",        $statut);
$smarty->assign("statut_xml",    $statut_xml);
$smarty->assign("empty_facture", new CFactureCabinet());

$smarty->display("inc_journal_envoi_xml_show");