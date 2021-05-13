<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Import\Framework\Configuration\Configuration;
use Ox\Import\MbImport\Mapper\SqlMapperBuilder;

CCanDo::checkAdmin();

$config = new Configuration(['a' => ['d' => 1], 'b' => 2, 'c' => 3]);

$mapper_builder = new SqlMapperBuilder('std');
$mapper_builder->setConfiguration($config);

$mappers = [
  'patient'                   => $mapper_builder->build('patient'),
  'medecin'                   => $mapper_builder->build('medecin'),
  'plage_consultation'        => $mapper_builder->build('plage_consultation'),
  'consultation'              => $mapper_builder->build('consultation'),
  'consultation_anesthesique' => $mapper_builder->build('consultation_anesthesique'),
  'sejour'                    => $mapper_builder->build('sejour'),
  'fichier'                   => $mapper_builder->build('fichier'),
  'document'                  => $mapper_builder->build('document'),
];

$smarty = new CSmartyDP();
$smarty->assign('mappers', $mappers);
$smarty->display('vw_import');