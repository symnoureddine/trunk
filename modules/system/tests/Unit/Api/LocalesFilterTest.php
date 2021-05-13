<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Tests\Unit\Api;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Mediboard\System\Api\LocalesFilter;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class LocalesFilterTest extends UnitTestMediboard
{
    public function testNotValideSearchMode()
    {
        $request_api = new CRequestApi(new Request(['search_mode' => 'invalid']));
        $this->expectExceptionMessage(
            'Search mode candidate \'invalid\' is not in ' . implode('|', LocalesFilter::SEARCH_MODES)
        );
        new LocalesFilter($request_api);
    }

    public function testNotValideSearchIn()
    {
        $request_api = new CRequestApi(new Request(['search_in' => 'invalid']));
        $this->expectExceptionMessage(
            'Search in candidate \'invalid\' is not in ' . implode('|', LocalesFilter::SEARCH_IN)
        );
        new LocalesFilter($request_api);
    }

    public function testApplyWithtoutSearchWord()
    {
        $locales = $this->getLocales();

        $request_api = new CRequestApi(new Request());
        $filter      = new LocalesFilter($request_api);
        // No change in the array
        $this->assertEquals($locales, $filter->apply($locales));
    }

    public function testIsEnableTrue()
    {
        $request_api = new CRequestApi(new Request(['search' => 'needle']));
        $filter      = new LocalesFilter($request_api);
        $this->assertTrue($filter->isEnabled());
    }

    public function testIsEnableFalse()
    {
        $request_api = new CRequestApi(new Request(['search_mode' => LocalesFilter::SEARCH_MODE_STARTS_WITH]));
        $filter      = new LocalesFilter($request_api);
        $this->assertFalse($filter->isEnabled());
    }

    /**
     * @dataProvider applyFilterOnKeyProvider
     */
    public function testApplyFilterOnKey(string $needle, string $search_mode, array $expected_result)
    {
        $request_api = new CRequestApi(
            new Request(
                ['search' => $needle, 'search_mode' => $search_mode, 'search_in' => LocalesFilter::SEARCH_IN_KEY]
            )
        );
        $filter      = new LocalesFilter($request_api);
        $this->assertEquals($expected_result, $filter->apply($this->getLocales()));
    }

    /**
     * @dataProvider applyFilterOnValueProvider
     */
    public function testApplyFilterOnValue(string $needle, string $search_mode, array $expected_result)
    {
        $request_api = new CRequestApi(
            new Request(
                ['search' => $needle, 'search_mode' => $search_mode, 'search_in' => LocalesFilter::SEARCH_IN_VALUE]
            )
        );
        $filter      = new LocalesFilter($request_api);
        $this->assertEquals($expected_result, $filter->apply($this->getLocales()));
    }

    public function applyFilterOnKeyProvider()
    {
        return [
            'starts_with_found'     => [
                'CAbo',
                LocalesFilter::SEARCH_MODE_STARTS_WITH,
                [
                    'CAbonnement'                     => 'Abonnement',
                    'CAbonnement-abonnement_id'       => 'Abonnement',
                    'CAbonnement-abonnement_id-court' => 'Abonnement',
                ],
            ],
            'starts_with_not_found' => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_STARTS_WITH,
                [],
            ],
            'ends_with_found'       => [
                '_id',
                LocalesFilter::SEARCH_MODE_ENDS_WITH,
                [
                    'CAbonnement-abonnement_id' => 'Abonnement',
                    'CSourcePOP-error-mail_id'  => 'Identifiant du message incorrect (mail_id)',
                ],
            ],
            'ends_with_not_found'   => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_ENDS_WITH,
                [],
            ],
            'contains_found'        => [
                'co',
                LocalesFilter::SEARCH_MODE_CONTAINS,
                [
                    'CAbonnement-abonnement_id-court' => 'Abonnement',
                    'Menu Icon'                       => 'Menu icones',
                    'CSourcePOP-error-noAccount'      => "Aucun compte lié à l'utilisateur %s",
                ],
            ],
            'contains_not_found'    => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_CONTAINS,
                [],
            ],
            'equals_found'          => [
                'CSourcePOP-error-notInitiated',
                LocalesFilter::SEARCH_MODE_EQUAL,
                [
                    'CSourcePOP-error-notInitiated' => 'Source POP non initialisée (init requis)',
                ],
            ],
            'equals_not_found'      => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_EQUAL,
                [],
            ],
        ];
    }

    public function applyFilterOnValueProvider()
    {
        return [
            'starts_with_found'     => [
                'Abon',
                LocalesFilter::SEARCH_MODE_STARTS_WITH,
                [
                    'CAbonnement'                     => 'Abonnement',
                    'CAbonnement-abonnement_id'       => 'Abonnement',
                    'CAbonnement-abonnement_id-court' => 'Abonnement',
                ],
            ],
            'starts_with_not_found' => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_STARTS_WITH,
                [],
            ],
            'ends_with_found'       => [
                'gation',
                LocalesFilter::SEARCH_MODE_ENDS_WITH,
                [
                    'Aggregation'                     => 'Agrégation',
                    'Aggregation-board'               => "Tableau de bord de l'agrégation",
                ],
            ],
            'ends_with_not_found'   => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_ENDS_WITH,
                [],
            ],
            'contains_found'        => [
                'in',
                LocalesFilter::SEARCH_MODE_CONTAINS,
                [
                    'CSourcePOP-error-mail_id'        => 'Identifiant du message incorrect (mail_id)',
                    'CSourcePOP-error-no_imap_lib'    => 'bibliothèque IMAP PHP non installée',
                    'CSourcePOP-error-notInitiated'   => 'Source POP non initialisée (init requis)',
                ],
            ],
            'contains_not_found'    => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_CONTAINS,
                [],
            ],
            'equals_found'          => [
                'Menu icones',
                LocalesFilter::SEARCH_MODE_EQUAL,
                [
                    'Menu Icon'                       => 'Menu icones',
                ],
            ],
            'equals_not_found'      => [
                uniqid(),
                LocalesFilter::SEARCH_MODE_EQUAL,
                [],
            ],
        ];
    }

    private function getLocales(): array
    {
        return [
            'Aggregation'                     => 'Agrégation',
            'Aggregation-board'               => "Tableau de bord de l'agrégation",
            'CAbonnement'                     => 'Abonnement',
            'CAbonnement-abonnement_id'       => 'Abonnement',
            'CAbonnement-abonnement_id-court' => 'Abonnement',
            'CSourcePOP-error-mail_id'        => 'Identifiant du message incorrect (mail_id)',
            'CSourcePOP-error-noAccount'      => "Aucun compte lié à l'utilisateur %s",
            'CSourcePOP-error-no_imap_lib'    => 'bibliothèque IMAP PHP non installée',
            'CSourcePOP-error-notInitiated'   => 'Source POP non initialisée (init requis)',
            'Menu Icon'                       => 'Menu icones',
            'Menu Status'                     => 'Menu Etat',
            'Menu Text'                       => 'Menu Texte',
        ];
    }
}
