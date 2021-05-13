<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Import\Framework\Configuration\Configuration;
use Ox\Import\Framework\Entity\CImportCampaign;
use Ox\Import\Framework\Entity\Manager;
use Ox\Import\Framework\Exception\ImportException;
use Ox\Import\Framework\Persister\DefaultPersister;
use Ox\Import\Framework\Repository\GenericRepository;
use Ox\Import\Framework\Strategy\BFSStrategy;
use Ox\Import\Framework\Validator\DefaultValidator;
use Ox\Import\MbImport\Mapper\SqlMapperBuilder;
use Ox\Import\MbImport\Matcher\Matcher;
use Ox\Import\MbImport\Transformer\Transformer;

CCanDo::checkAdmin();

$mapper_name = CView::get('mapper_name', 'str notNull');
$last_id     = CView::get('last_id', 'str');
$auto        = CView::get('auto', 'bool notNull');

CView::checkin();

$last_id = ($last_id === '') ? null : $last_id;

$campaign = CImportCampaign::findOrFail(1);

$config = new Configuration(['a' => ['d' => 1], 'b' => 2, 'c' => 3]);

$mapper_builder = new SqlMapperBuilder('std');
$mapper_builder->setConfiguration($config);

$repository = new GenericRepository($mapper_builder, $mapper_name);

$validator   = new DefaultValidator();
$transformer = new Transformer();
$matcher     = new Matcher();
$persister   = new DefaultPersister();
$strategy    = new BFSStrategy($repository, $validator, $transformer, $matcher, $persister, $campaign);

$config = new Configuration(['a' => ['d' => 1], 'b' => 2, 'c' => 3]);
$import = new Manager($strategy, $config);

try {
  $import->import(1, $last_id);

  $last_imported_id = $import->getLastExternalId();
  dump('Last imported Id: ' . $last_imported_id);

  CAppUI::js("nextImport('{$mapper_name}', '{$last_imported_id}', '{$auto}');");

} catch (ImportException $e) {
  dump($e->getMessage());
}

echo CAppUI::getMsg();