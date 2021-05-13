<?php
/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */


namespace Ox\Core\Tests\Unit;

use Exception;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestFilter;
use Ox\Core\Api\Request\CRequestLimit;
use Ox\Core\Api\Request\CRequestSort;
use Ox\Core\CAppUI;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Search\CSearchHistory;
use Ox\Mediboard\System\CUserLog;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

class CStoredObjectTest extends UnitTestMediboard
{

    /**
     * @var CStoredObject $object
     */
    private static $object;

    /**
     * Set Up
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $object          = new CSearchHistory();
        $object->date    = 'now';
        $object->user_id = CAppUI::$user->_id;
        $object->entry   = uniqid('entry', true);
        $object->hits    = rand(1, 999);
        $object->store();
        static::$object = $object;
        sleep(1);
    }


    /**
     * @return CStoredObject|CSearchHistory
     * @throws Exception
     */
    public function testStoreObject()
    {
        $this->assertNotNull(static::$object->_id);
        $this->assertInstanceOf(CUserLog::class, static::$object->_ref_current_log);
        // update = store
        static::$object->date  = 'now';
        static::$object->hits  = rand(1, 999);
        static::$object->entry = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit';
        $msg                   = static::$object->store();
        $this->assertNull($msg);
        $user_log = static::$object->_ref_current_log;
        $this->assertInstanceOf(CUserLog::class, $user_log);

        $extra = (array)json_decode($user_log->extra);

        $this->assertTrue(count($extra) >= 2);

        return static::$object;
    }


    public function testLoadLogs()
    {
        static::$object->loadLogs();

        $this->assertIsArray(static::$object->_ref_logs);

        $this->assertInstanceOf(CUserLog::class, static::$object->_ref_first_log);
        $this->assertInstanceOf(CUserLog::class, static::$object->_ref_last_log);
    }


    public function testLoadHistory()
    {
        static::$object->loadHistory();
        $this->assertIsArray(static::$object->_history);
        $first = reset(static::$object->_history);
        $this->assertEquals($first['date'], static::$object->date);
    }

    public function testLoadLogForField()
    {
        static::$object->hits = rand(1, 999);
        static::$object->store();

        static::$object->loadHistory();

        $logs = static::$object->loadLogsForField('hits');
        $this->assertIsArray($logs);
        $this->assertInstanceOf(CUserLog::class, reset($logs));

        // first  =  older log
        $first = static::$object->loadFirstLogForField('hits');
        $this->assertInstanceOf(CUserLog::class, $first);

        $last = static::$object->loadLastLogForField('hits');
        $this->assertInstanceOf(CUserLog::class, $last);

        $this->assertGreaterThanOrEqual($first->date, $last->date);
    }

    public function testHasRecentLog()
    {
        $true = (bool)static::$object->hasRecentLog(1);
        $this->assertTrue($true);

        $obj   = new CSearchHistory();
        $false = (bool)$obj->hasRecentLog(1);
        $this->assertFalse($false);
    }

    public function testLoadLog()
    {
        $first = static::$object->loadFirstLog();
        $last  = static::$object->loadLastLog();
        $this->assertGreaterThanOrEqual($first->date, $last->date);
    }

    public function testLoadCreationLog()
    {
        $log = static::$object->loadCreationLog();
        $this->assertInstanceOf(CUserLog::class, $log);
        $this->assertEquals('create', $log->type);
    }

    /**
     * @param string $guid
     * @param string $expected
     *
     * @throws Exception
     */
    public function testLoadFromGuidOk()
    {
        $patient = $this->getRandomObjects(CPatient::class);

        $this->assertEquals($patient, CStoredObject::loadFromGuid("CPatient-{$patient->_id}"));

        $sejour = new CSejour();
        $this->assertEquals($sejour, CStoredObject::loadFromGuid('CSejour-none'));
    }

    /**
     * @param string $guid
     *
     * @throws Exception
     * @dataProvider loadFromGuidKoProvider
     */
    public function testLoadFromGuidKo($guid)
    {
        $this->assertNull(CStoredObject::loadFromGuid($guid));
    }

    /**
     * @param array  $fields
     * @param array  $values
     * @param string $operator
     * @param string $condition
     * @param string $mode
     * @param string $expected_result
     *
     * @dataProvider prepareMatchOkProvider
     */
    public function testPrepareMatchOk(
        $fields,
        $values,
        $operator,
        string $condition,
        string $mode,
        string $expected_result
    ) {
        $result = CStoredObject::prepareMatch($fields, $values, $operator, $condition, $mode);
        $this->assertEquals($expected_result, $result);
    }


    public function prepareMatchOkProvider()
    {
        return [
            'SearchBooleanEnd'        => [
                'adresse',
                'ute de tranche',
                'end',
                'and',
                'boolean',
                "MATCH (adresse) AGAINST('+*ute de tranche' IN BOOLEAN MODE)",
            ],
            'SearchBooleanEqual'      => [
                'adresse',
                '15 route de tranche',
                'equals',
                'and',
                'boolean',
                "MATCH (adresse) AGAINST('+15 route de tranche' IN BOOLEAN MODE)",
            ],
            'SearchBooleanBegin'      => [
                'adresse',
                '15 route de t',
                'begin',
                'and',
                'boolean',
                "MATCH (adresse) AGAINST('+15 route de t*' IN BOOLEAN MODE)",
            ],
            'SearchMultiBooleanBegin' => [
                ['adresse', 'cp', 'ville'],
                ['foobar', 'is', 'Here'],
                'begin',
                'and',
                'boolean',
                "MATCH (adresse,cp,ville) AGAINST('+foobar* +is +Here*' IN BOOLEAN MODE)",
            ],
            'SearchMultiNatural'      => [
                ['adresse', 'cp', 'ville'],
                ['foobar', 'is', 'Here'],
                'begin',
                'and',
                'natural',
                "MATCH (adresse,cp,ville) AGAINST('foobar is Here' IN NATURAL LANGUAGE MODE)",
            ],
            'ConditionOr'             => [
                ['adresse', 'cp', 'ville'],
                ['foobar', 'is', 'Here'],
                'begin',
                'or',
                'boolean',
                "MATCH (adresse,cp,ville) AGAINST('foobar* is Here*' IN BOOLEAN MODE)",
            ],
        ];
    }

    /**
     * @param array  $fields
     * @param array  $values
     * @param string $operator
     * @param string $condition
     * @param string $mode
     * @param string $expected_message
     *
     * @dataProvider prepareMatchExceptionsProvider
     */
    public function testPrepareMatchExceptions(
        ?array $fields,
        ?array $values,
        ?string $operator,
        string $condition,
        string $mode,
        string $expected_message
    ) {
        $this->expectExceptionMessage($expected_message);
        CStoredObject::prepareMatch($fields, $values, $operator, $condition, $mode);
    }

    public function prepareMatchExceptionsProvider()
    {
        return [
            'ModeIsNotValid'      => [
                [],
                [],
                'equal',
                'and',
                'foo',
                'foo is not a valid query language mode. Allowed values are : '
                . implode(', ', array_keys(CStoredObject::$fulltext_query_language_modes)),
            ],
            'ConditionIsNotValid' => [
                [],
                [],
                'equal',
                'bar',
                'boolean',
                'bar is not a valid query condition. Allowed values are : '
                . implode(', ', CStoredObject::$fulltext_query_operators),
            ],
            'No fields'           => [
                null,
                null,
                null,
                'and',
                'boolean',
                'Fields cannot be null',
            ],
            'No values'           => [
                ['test'],
                null,
                null,
                'and',
                'boolean',
                'Values cannot be null',
            ],
        ];
    }

    public function loadFromGuidKoProvider()
    {
        return [
            'object-'                  => ['CSejour-'],
            '-number'                  => ['-150'],
            'object-char'              => ['CConsultation-test'],
            'object not instanciable'  => ['CMbString-10'],
            'object not CStoredObject' => ['CCSVImportPatients-5'],
        ];
    }


    public function testLoadList()
    {
        $user  = new CUser();
        $users = $user->loadList("user_sexe = 'm'", "user_username desc", 5);
        $this->assertCount(5, $users);

        return $users;
    }

    /**
     * @depends testLoadList
     */
    public function testLoadListFormRequestApi(array $users_actual)
    {
        $req = new Request();
        $req->query->set(CRequestLimit::QUERY_KEYWORD_LIMIT, 5);
        $req->query->set(CRequestFilter::QUERY_KEYWORD_FILTER, 'user_sexe.equal.m');
        $req->query->set(CRequestSort::QUERY_KEYWORD_SORT, '-user_username');

        $request_api    = new CRequestApi($req);
        $user           = new CUser();
        $users_expected = $user->loadListFromRequestApi($request_api);
        $this->assertCount(5, $users_expected);
        $this->assertEquals($users_expected, $users_actual);
    }

    public function testCountList()
    {
        $user  = new CUser();
        $count = $user->countList("user_sexe = 'm'");
        $this->assertTrue($count > 0);

        return (int)$count;
    }

    /**
     * @depends testCountList
     */
    public function testCountListFromRequestApi(int $count_actual)
    {
        $req = new Request();
        $req->query->set(CRequestFilter::QUERY_KEYWORD_FILTER, 'user_sexe.equal.m');
        $request_api = new CRequestApi($req);
        $user        = new CUser();
        $this->assertEquals($user->countListFromRequestApi($request_api), $count_actual);
    }

    public function testMerge()
    {
        /** @var CUser $user_1 */
        $user_1      = $this->getRandomObjects(CUser::class);

        /** @var CUser $user_2 */
        $user_2      = $this->getRandomObjects(CUser::class);
        $user_2_id = $user_2->_id;
        $user_2_last_name = $user_2->user_last_name;

        $user_1->user_last_name = $user_2->user_last_name;

        $retour = $user_1->merge([$user_2]);
        $this->assertNull($retour);

        $this->assertEquals($user_1->user_last_name, $user_2_last_name);


        $this->assertNull($user_2->_id);
        $this->assertFalse((new CUser())->load($user_2_id));
    }

    public function testMergeNotAdmin()
    {
        /** @var CUser $user_1 */
        $user_1      = $this->getRandomObjects(CUser::class);
        $users       = $this->getRandomObjects(CUser::class,2);

        $user = CMediusers::get();
        $user->_user_type = 'Dentiste';

        $msg = $user_1->merge($users);
        $this->assertEquals($msg, 'mergeTooFewObjects');

        $user->_user_type = 'Administrator';

    }

}
