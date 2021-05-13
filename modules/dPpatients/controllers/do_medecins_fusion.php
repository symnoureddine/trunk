<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Patients\CMedecin;

$ds       = CSQLDataSource::get("std");
$medecin1 = new CMedecin();
$medecin1->load($_POST["medecin1_id"]);
$medecin2 = new CMedecin();
$medecin2->load($_POST["medecin2_id"]);

$do = new CDoObjectAddEdit("CMedecin", "medecin_id");
$do->doBind();

// Création du nouveau medecin
if (intval(CValue::post("del"))) {
  $do->doDelete();
}
else {
  $do->doStore();
}

/** @var CMedecin $newMedecin */
$newMedecin =& $do->_obj;

// Transfert de toutes les backrefs
if ($msg = $newMedecin->transferBackRefsFrom($medecin1)) {
  $do->errorRedirect($msg);
}

if ($msg = $newMedecin->transferBackRefsFrom($medecin2)) {
  $do->errorRedirect($msg);
}

// Suppression des anciens objets
if ($msg = $medecin1->delete()) {
  $do->errorRedirect($msg);
}

if ($msg = $medecin2->delete()) {
  $do->errorRedirect($msg);
}

$medecin1->delete();
$medecin2->delete();

$do->doRedirect();
