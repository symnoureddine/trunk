<?php

/**
 * @package Mediboard\Maidis
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Maidis;

use Ox\Core\Module\CModule;
use Ox\Import\Framework\CFwImport;
use Ox\Import\Framework\Mapper\MapperBuilderInterface;
use Ox\Import\Framework\Matcher\MatcherVisitorInterface;
use Ox\Import\Framework\Transformer\AbstractTransformer;
use Ox\Import\Maidis\Mapper\MySQLMapperBuilder;
use Ox\Mediboard\Galaxie\Matcher\GalaxieMatcher;
use Ox\Mediboard\Galaxie\Transformer\GalaxieTransformer;

/**
 * Description
 */
class MaidisImport extends CFwImport
{
    public const IMPORT_ORDER = [
        'patient',
        'correspondant_medical',
        'consultation',
    ];

    private $galaxie_active = false;

    public function __construct()
    {
        $this->galaxie_active =  CModule::getActive('galaxie');
    }

    protected function getMapperBuilderInstance(): MapperBuilderInterface
    {
        return new MySQLMapperBuilder();
    }

    protected function getUserTable(): string
    {
        return 'utilisateur';
    }

    protected function getMatcherInstance(): MatcherVisitorInterface
    {
        return ($this->galaxie_active) ? new GalaxieMatcher() : parent::getMatcherInstance();
    }

    protected function getTransformerInstance(): AbstractTransformer
    {
        return ($this->galaxie_active) ? new GalaxieTransformer() : parent::getTransformerInstance();
    }

    public function getImportOrder(): array
    {
        $import_order = self::IMPORT_ORDER;

        if ($this->galaxie_active) {
            $import_order = array_merge($import_order, ['solde_patient']);
        }

        return $import_order;
    }
}
